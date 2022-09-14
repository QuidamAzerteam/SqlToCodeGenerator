<?php

namespace SqlToCodeGenerator;

use InvalidArgumentException;
use PDO;
use SqlToCodeGenerator\generation\metadata\Bean;
use SqlToCodeGenerator\generation\metadata\BeanProperty;
use SqlToCodeGenerator\generation\metadata\BeanPropertyColKey;
use SqlToCodeGenerator\generation\metadata\BeanPropertyType;
use SqlToCodeGenerator\generation\metadata\Enum;
use SqlToCodeGenerator\generation\metadata\ForeignBean;
use RuntimeException;
use SqlToCodeGenerator\sql\PdoContainer;
use SqlToCodeGenerator\sql\SqlDao;

class FromSqlCodeGenerator {

	private PdoContainer $pdoContainer;

	/**
	 * @param string $host For {@see PDO} connection only
	 * @param string $port For {@see PDO} connection only
	 * @param string $user For {@see PDO} connection only. Need to be able to SELECT information_schema
	 * @param string $password For {@see PDO} connection only
	 * @param string $bdd Database to generate sources from
	 * @param string $basePackage Root package. For instance, if it's `MyPackage`, generated code will have as namespace `MyPackage\{@see $genDirName}\{@see $beanDirName}`
	 * @param string[] $tablesToIgnore SQL tables to ignore
	 * @param string $phpDirName Relative from root directory
	 * @param string $jsDirName Relative from root directory
	 * @param string $genDirName For PHP and JS sources
	 * @param string $beanDirName
	 * @param string $enumsDirName
	 * @param string $daoDirName
	 * @param string|null $testDirName Set as null to avoid generating tests
	 */
	public function __construct(
			string $host,
			string $port,
			string $user,
			string $password,
			private readonly string $bdd,
			private readonly string $basePackage,
			private readonly array $tablesToIgnore,
			private readonly string $phpDirName,
			private readonly string $jsDirName,
			private readonly string $genDirName = 'gen',
			private readonly string $beanDirName = 'bean',
			private readonly string $enumsDirName = 'enums',
			private readonly string $daoDirName = 'dao',
			private readonly ?string $testDirName = 'tests',
	) {
		$this->pdoContainer = new PdoContainer($bdd, $host, $port, $user, $password);
	}

	public function generate(): never {
		$tableNameNoIntSql = '';
		if ($this->tablesToIgnore) {
			$tableNameNoIntSql = 'AND t.TABLE_NAME NOT IN ("' . implode('", "', $this->tablesToIgnore) . '")';
		}

		$multiUniqueKeyStatement = $this->pdoContainer->getPdo()->prepare("
			SELECT DISTINCT
				kcu.TABLE_NAME,
				kcu.COLUMN_NAME,
				tc.CONSTRAINT_NAME

			FROM information_schema.COLUMNS c
				INNER JOIN information_schema.TABLES t ON t.TABLE_NAME = c.TABLE_NAME
				INNER JOIN information_schema.TABLE_CONSTRAINTS tc ON tc.TABLE_SCHEMA = c.TABLE_SCHEMA
					AND tc.TABLE_NAME = c.TABLE_NAME
				INNER JOIN information_schema.KEY_COLUMN_USAGE kcu ON kcu.CONSTRAINT_NAME = tc.CONSTRAINT_NAME

			WHERE c.TABLE_SCHEMA = :bdd 
				AND tc.CONSTRAINT_TYPE = 'UNIQUE'
				$tableNameNoIntSql
		");
		$multiUniqueKeyStatement->bindValue(':bdd', $this->bdd);
		$multiUniqueKeyStatement->execute();

		/** @var string[][][] $colNamesByUniqueConstraintNameByTableName */
		$colNamesByUniqueConstraintNameByTableName = array();
		foreach ($multiUniqueKeyStatement->fetchAll(PDO::FETCH_ASSOC) as $row) {
			$tableName = $row['TABLE_NAME'];
			$constraintName = $row['CONSTRAINT_NAME'];
			$columnName = $row['COLUMN_NAME'];

			if (!array_key_exists($tableName, $colNamesByUniqueConstraintNameByTableName)) {
				$colNamesByUniqueConstraintNameByTableName[$tableName]= array();
			}
			if (!array_key_exists($constraintName, $colNamesByUniqueConstraintNameByTableName[$tableName])) {
				$colNamesByUniqueConstraintNameByTableName[$tableName][$constraintName]= array();
			}
			$colNamesByUniqueConstraintNameByTableName[$tableName][$constraintName][] = $columnName;
		}

		$colsInfoStatement = $this->pdoContainer->getPdo()->prepare("
			SELECT 
				c.TABLE_NAME,
				c.COLUMN_NAME,
				c.COLUMN_DEFAULT,
				c.COLUMN_TYPE,
				c.IS_NULLABLE,
				c.DATA_TYPE,
				c.COLUMN_KEY,
				c.COLUMN_COMMENT,

				GROUP_CONCAT(DISTINCT c_fk.TABLE_NAME) AS FK_TABLE_NAME,
				GROUP_CONCAT(DISTINCT c_fk.COLUMN_NAME) AS FK_COLUMN_NAME

			FROM information_schema.COLUMNS c
				INNER JOIN information_schema.TABLES t ON t.TABLE_NAME = c.TABLE_NAME
				LEFT JOIN information_schema.KEY_COLUMN_USAGE kcu ON kcu.TABLE_SCHEMA = c.TABLE_SCHEMA
					AND kcu.TABLE_NAME = c.TABLE_NAME
					AND kcu.COLUMN_NAME = c.COLUMN_NAME
				LEFT JOIN information_schema.COLUMNS c_fk ON c_fk.TABLE_SCHEMA = kcu.REFERENCED_TABLE_SCHEMA
					AND c_fk.TABLE_NAME = kcu.REFERENCED_TABLE_NAME
					AND c_fk.COLUMN_NAME = kcu.REFERENCED_COLUMN_NAME
			WHERE c.TABLE_SCHEMA = :bdd 
				$tableNameNoIntSql

			GROUP BY c.TABLE_SCHEMA, c.TABLE_NAME, c.COLUMN_NAME
		");
		$colsInfoStatement->bindValue(':bdd', $this->bdd);
		$colsInfoStatement->execute();

		/** @var Bean[] $beansByClassName */
		$beansByClassName = array();
		/** @var BeanProperty[] $beansByClassName */
		$beanPropertiesByUniqueKey = array();
		/** @var Enum[] $beansByClassName */
		$enums = array();
		foreach ($colsInfoStatement->fetchAll(PDO::FETCH_ASSOC) as $item) {
			$tableName = $item['TABLE_NAME'];
			$className = SqlDao::sqlToCamelCase($tableName);
			$bean = new Bean();
			$bean->basePackage = $this->basePackage;
			$bean->sqlTable = $tableName;
			$bean->className = $className;
			$bean->beanNamespace = $this->genDirName . '\\' . $this->beanDirName;
			$bean->daoNamespace = $this->genDirName . '\\' . $this->daoDirName;
			$bean->colNamesByUniqueConstraintName
					= $colNamesByUniqueConstraintNameByTableName[$tableName] ?? [];
			$bean = $beansByClassName[$bean->className] ?? $bean;
			$beansByClassName[$className] = $bean;

			$property = new BeanProperty();

			$property->sqlName = $item['COLUMN_NAME'];
			$property->sqlComment = $item['COLUMN_COMMENT'];
			$property->name = lcfirst(SqlDao::sqlToCamelCase($property->sqlName));
			$property->belongsToClass = $className;
			$property = $beanPropertiesByUniqueKey[$property->getUniqueKey()] ?? $property;
			$beanPropertiesByUniqueKey[$property->getUniqueKey()] = $property;

			$property->isNullable = $item['IS_NULLABLE'] === 'YES';
			$property->propertyType = BeanPropertyType::getPropertyTypeFromSql($item['DATA_TYPE'], $item['COLUMN_TYPE']);
			$property->columnKey = BeanPropertyColKey::getFromString($item['COLUMN_KEY']);
			$defaultValue = $item['COLUMN_DEFAULT'];
			$defaultValue = $defaultValue === 'NULL' ? 'null' : $defaultValue;
			switch ($property->propertyType) {
				case BeanPropertyType::INT:
				case BeanPropertyType::FLOAT:
				case BeanPropertyType::STRING:
					if ($defaultValue !== null) {
						$property->defaultValueAsString = $defaultValue;
					}
					break;
				case BeanPropertyType::DATE:
					if ($defaultValue === 'current_timestamp()') {
//						$property->defaultValueAsString = "new \DateTime()";
					}
					break;
				case BeanPropertyType::ENUM:
					$enum = new Enum();
					$enum->basePackage = $this->basePackage;
					$enum->namespace = $this->genDirName . "\\" . $this->enumsDirName;
					$enum->sqlComment = $item['COLUMN_COMMENT'];
					$enum->name = SqlDao::sqlToCamelCase($bean->sqlTable . '_' . $property->sqlName . '_enum');
					$colType = $item['COLUMN_TYPE'];
					$re = '/\'([^\']+)\'/m';
					preg_match_all($re, $colType, $matches);

					$enum->values = $matches[1];

					$property->enumAsString = "\\$enum->basePackage\\" . $this->genDirName
							. "\\" . $this->enumsDirName . "\\$enum->name";
					if ($defaultValue !== null) {
						if ($defaultValue === 'null') {
							$property->defaultValueAsString = 'null';
						} else {
							// Watch out, in local, default value have no "'" but in prod they do
							$property->defaultValueAsString = '\\' . $this->basePackage
									. '\\' . $this->genDirName
									. '\\' . $this->enumsDirName
									. '\\' . $enum->name . '::' . str_replace("'", '', $defaultValue);
						}
					}

					$enums[] = $enum;
					break;
				case BeanPropertyType::BOOL:
					$property->defaultValueAsString =  match ($defaultValue) {
						'0' => 'false',
						'1' => 'true',
						null => $property->isNullable ? 'null' : null,
					};
					break;
				case BeanPropertyType::JSON:
					break;
			}

			$fkTableName = $item['FK_TABLE_NAME'];
			if ($fkTableName) {
				$fkBean = new ForeignBean();
				$bean->foreignBeans[] = $fkBean;

				$fkBean->toBean = new Bean();
				$fkBean->toBean->basePackage = $this->basePackage;
				$fkBean->toBean->beanNamespace = $this->genDirName . '\\' . $this->beanDirName;
				$fkBean->toBean->daoNamespace = $this->genDirName . '\\' . $this->daoDirName;
				$fkBean->toBean->className = SqlDao::sqlToCamelCase($fkTableName);
				$fkBean->toBean->sqlTable = $fkTableName;
				$fkBean->toBean->colNamesByUniqueConstraintName
						= $colNamesByUniqueConstraintNameByTableName[$fkTableName] ?? [];
				$fkBean->toBean = $beansByClassName[$fkBean->toBean->className] ?? $fkBean->toBean;
				$beansByClassName[$fkBean->toBean->className] = $fkBean->toBean;

				$fkBean->withProperty = $property;

				$fkBean->onProperty = new BeanProperty();
				$fkBean->onProperty->sqlName = $item['FK_COLUMN_NAME'];
				$fkBean->onProperty->name = lcfirst(SqlDao::sqlToCamelCase($fkBean->onProperty->sqlName));
				$fkBean->onProperty->belongsToClass = $fkBean->toBean->className;
				$fkBean->onProperty = $beanPropertiesByUniqueKey[$fkBean->onProperty->getUniqueKey()] ?? $fkBean->onProperty;
				$beanPropertiesByUniqueKey[$fkBean->onProperty->getUniqueKey()] = $fkBean->onProperty;

				$reverseFkBean = new ForeignBean();
				$reverseFkBean->isArray = true;
				$reverseFkBean->toBean = $bean;
				$reverseFkBean->onProperty = $fkBean->withProperty;
				$reverseFkBean->withProperty = $fkBean->onProperty;
				$fkBean->toBean->foreignBeans[] = $reverseFkBean;
			}

			$property = $beanPropertiesByUniqueKey[$property->getUniqueKey()] ?? $property;
			$beanPropertiesByUniqueKey[$property->getUniqueKey()] = $property;

			$bean->properties[] = $property;
		}

		// Create architecture if does not exists
		echo "Recreating generated directories\n";

		$phpGenPath = $this->phpDirName . '/' . $this->genDirName;
		$this->recursiveDelete($phpGenPath);
		$this->createDir($phpGenPath);
		$this->createDir($phpGenPath . '/' . $this->beanDirName);
		$this->createDir($phpGenPath . '/' . $this->daoDirName);
		$this->createDir($phpGenPath . '/' . $this->enumsDirName);

		if ($this->testDirName !== null) {
			$testsGenPath = $this->testDirName . '/' . $this->genDirName;
			$this->recursiveDelete($testsGenPath);
			$this->createDir($testsGenPath);
			$this->createDir($testsGenPath . '/' . $this->beanDirName);
			$this->createDir($testsGenPath . '/' . $this->daoDirName);
			$this->createDir($testsGenPath . '/' . $this->enumsDirName);
		}

		$jsGenPath = $this->jsDirName . '/' . $this->genDirName;
		$this->recursiveDelete($jsGenPath);
		$this->createDir($jsGenPath);
		$this->createDir($jsGenPath . '/' . $this->beanDirName);
		$this->createDir($jsGenPath . '/' . $this->enumsDirName);

		echo 'Beans found: ' . implode(', ', array_keys($beansByClassName)) . "\n";

		foreach ($beansByClassName as $bean) {
			file_put_contents(
					$phpGenPath . '/' . $this->beanDirName . '/' . $bean->className . '.php',
					$bean->getPhpClassFileContent($this->basePackage . '\core\utils')
			);
			file_put_contents(
					$phpGenPath . '/' . $this->daoDirName . '/' . $bean->getDaoName() . '.php',
					$bean->getPhpDaoFileContent()
			);

			if ($this->testDirName !== null) {
				$testsGenPath = $this->testDirName . '/' . $this->genDirName;
				file_put_contents(
						$testsGenPath . '/' . $this->beanDirName . '/' . $bean->className . 'Test.php',
						$bean->getPhpTestFileContent($this->testDirName)
				);
				file_put_contents(
						$testsGenPath . '/' . $this->daoDirName . '/' . $bean->getDaoName() . 'Test.php',
						$bean->getPhpDaoTestFileContent($this->testDirName)
				);
			}

			file_put_contents(
					$jsGenPath . '/' . $this->beanDirName . '/' . $bean->className . '.js',
					$bean->getJsClassFileContent()
			);
		}

		echo 'Enums found: ' . implode(', ', array_map(static fn (Enum $enum) => $enum->name, $enums)) . "\n";

		foreach ($enums as $enum) {
			file_put_contents(
					$phpGenPath . '/' . $this->enumsDirName . '/' . $enum->name . '.php',
					$enum->getPhpFileContent()
			);

			if ($this->testDirName !== null) {
				$testsGenPath = $this->testDirName . '/' . $this->genDirName;
				file_put_contents(
						$testsGenPath . '/' . $this->enumsDirName . '/' . $enum->name . 'Test.php',
						$enum->getPhpTestFileContent($this->testDirName)
				);
			}

			file_put_contents(
					$jsGenPath . '/' . $this->enumsDirName . '/' . $enum->name . '.js',
					$enum->getJsFileContent()
			);
		}

		die();
	}

	private function recursiveDelete($dirPath): void {
		if (!file_exists($dirPath)) {
			return;
		}
		if (!is_dir($dirPath)) {
			throw new InvalidArgumentException("$dirPath must be a directory");
		}
		if (!str_ends_with($dirPath, '/')) {
			$dirPath .= '/';
		}
		$files = glob($dirPath . '*', GLOB_MARK);
		foreach ($files as $file) {
			if (is_dir($file)) {
				$this->recursiveDelete($file);
			} else {
				unlink($file);
			}
		}
		rmdir($dirPath);
	}

	private function createDir(string $dirPath): void {
		if (!mkdir($dirPath) && !is_dir($dirPath)) {
			throw new RuntimeException(sprintf('Directory "%s" was not created', $dirPath));
		}
	}

}
