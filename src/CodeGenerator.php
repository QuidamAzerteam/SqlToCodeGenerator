<?php

namespace SqlToCodeGenerator;

use LogicException;
use PDO;
use SqlToCodeGenerator\codeGeneration\metadata\Bean;
use SqlToCodeGenerator\codeGeneration\metadata\BeanPropertyType;
use SqlToCodeGenerator\codeGeneration\metadata\Enum;
use SqlToCodeGenerator\log\LoggerInterface;
use SqlToCodeGenerator\sql\PdoContainer;
use SqlToCodeGenerator\sqlToMetaCode\dao\SqlToMetaCodeDao;
use SqlToCodeGenerator\utils\FileUtils;

class CodeGenerator {

	/**
	 * @param SqlToMetaCodeDao $sqlToMetaCodeDao
	 * @param LoggerInterface $log
	 * @param string $basePackage Root package.
	 * For instance, if it's `MyPackage`, generated code will have as namespace
	 * `MyPackage\{@see $genDirName}\{@see $beanDirName}`
	 * @param string $phpSourceDirectory Relative from root directory
	 * @param string|null $jsSourceDirectory Relative from root directory. Set as null to avoid generating JS sources
	 * @param string $genDirName For PHP and JS sources
	 * @param string $beanDirName
	 * @param string $enumsDirName
	 * @param string $daoDirName
	 * @param string|null $testDirName Set as null to avoid generating tests
	 */
	public function __construct(
			private readonly SqlToMetaCodeDao $sqlToMetaCodeDao,
			private readonly LoggerInterface $log,
			private readonly string $basePackage,
			private readonly string $phpSourceDirectory,
			private readonly ?string $jsSourceDirectory = null,
			private readonly string $genDirName = 'gen',
			private readonly string $beanDirName = 'bean',
			private readonly string $enumsDirName = 'enums',
			private readonly string $daoDirName = 'dao',
			private readonly ?string $testDirName = 'tests',
	) {
		self::checkDirectory($phpSourceDirectory);
		if ($jsSourceDirectory !== null) {
			self::checkDirectory($jsSourceDirectory);
		}
		if ($testDirName !== null) {
			self::checkDirectory($testDirName);
		}
	}

	private static function checkDirectory(string $directory): void {
		if ($directory === '') {
			throw new LogicException('Directory cannot be empty');
		}
		if (!file_exists($directory)) {
			throw new LogicException('Directory does not exists');
		}
		if (!is_dir($directory)) {
			throw new LogicException('Directory is not a directory');
		}
	}

	/**
	 * @see __construct
	 */
	public static function create(
			SqlToMetaCodeDao $sqlToMetaCodeDao,
			LoggerInterface $log,
			string $basePackage,
			string $phpSourceDirectory,
			?string $jsSourceDirectory = null,
			string $genDirName = 'gen',
			string $beanDirName = 'bean',
			string $enumsDirName = 'enums',
			string $daoDirName = 'dao',
			?string $testDirName = 'tests',
	): self {
		return new self(
				$sqlToMetaCodeDao,
				$log,
				$basePackage,
				$phpSourceDirectory,
				$jsSourceDirectory,
				$genDirName,
				$beanDirName,
				$enumsDirName,
				$daoDirName,
				$testDirName,
		);
	}

	/**
	 * @param string $host For {@see PDO} connection only
	 * @param string $port For {@see PDO} connection only
	 * @param string $user For {@see PDO} connection only. Need to be able to SELECT information_schema
	 * @param string $password For {@see PDO} connection only
	 * @param LoggerInterface $log
	 * @param string $bdd
	 * @param string $basePackage
	 * @param string $phpDirName
	 * @param string|null $jsDirName
	 * @param array $tablesToIgnore
	 * @param string $genDirName
	 * @param string $beanDirName
	 * @param string $enumsDirName
	 * @param string $daoDirName
	 * @param string|null $testDirName
	 * @param int $waitTimeout
	 * @return static
	 * @see __construct
	 */
	public static function createFromScratch(
			string $host,
			string $port,
			string $user,
			string $password,
			LoggerInterface $log,
			string $bdd,
			string $basePackage,
			string $phpDirName,
			?string $jsDirName = null,
			array $tablesToIgnore = [],
			string $genDirName = 'gen',
			string $beanDirName = 'bean',
			string $enumsDirName = 'enums',
			string $daoDirName = 'dao',
			?string $testDirName = 'tests',
			int $waitTimeout = 60,
	): self {
		return new self(
				new SqlToMetaCodeDao(
						new PdoContainer(
								$bdd,
								$host,
								$port,
								$user,
								$password,
								$waitTimeout,
						),
						$bdd,
						$tablesToIgnore,
				),
				$log,
				$basePackage,
				$phpDirName,
				$jsDirName,
				$genDirName,
				$beanDirName,
				$enumsDirName,
				$daoDirName,
				$testDirName,
		);
	}

	/**
	 * @return Bean[]
	 */
	private function retrieveBeansFromSql(): array {
		$beans = $this->sqlToMetaCodeDao->getBeansFromSql();

		foreach ($beans as $bean) {
			$bean->basePackage = $this->basePackage;
			$bean->beanNamespace = $this->genDirName . '\\' . $this->beanDirName;
			$bean->daoNamespace = $this->genDirName . '\\' . $this->daoDirName;

			foreach ($bean->properties as $property) {
				$enums = array_filter([$property->enum], ...$property->enums);
				foreach ($enums as $enum) {
					$enum->basePackage = $this->basePackage;
					$enum->namespace = "$this->genDirName\\$this->enumsDirName";
				}
				if (
						$property->enum
						&& $property->defaultValueAsString !== null
						&& $property->defaultValueAsString !== 'null'
				) {
					// Watch out, in local, default value have no "'" but in prod they do
					// defaultValueAsString when Enum is string of the Enum
					$property->defaultValueAsString = '\\' . $this->basePackage
							. '\\' . $this->genDirName
							. '\\' . $this->enumsDirName
							. '\\' . $property->enum->name . '::'
							. str_replace("'", '', $property->defaultValueAsString);
				}
			}
		}

		return $beans;
	}

	public function generate(): void {
		/** @var Enum[] $enums */
		$enums = [];
		$beans = $this->retrieveBeansFromSql();
		foreach ($beans as $bean) {
			foreach ($bean->properties as $property) {
				if ($property->propertyType === BeanPropertyType::ENUM) {
					$enums[] = $property->enum;
				} else if ($property->propertyType === BeanPropertyType::ENUM_LIST) {
					array_push($enums, ...$property->enums);
				}
			}
		}

		// Create architecture if does not exists
		$this->log->info("Recreating generated directories");

		$phpGenPath = $this->phpSourceDirectory . '/' . $this->genDirName;
		FileUtils::recursiveDelete($phpGenPath);
		FileUtils::createDir($phpGenPath);
		FileUtils::createDir($phpGenPath . '/' . $this->beanDirName);
		FileUtils::createDir($phpGenPath . '/' . $this->daoDirName);
		FileUtils::createDir($phpGenPath . '/' . $this->enumsDirName);

		$testsGenPath = $this->testDirName !== null
				? $this->testDirName . '/' . $this->genDirName
				: null;
		if ($testsGenPath !== null) {
			FileUtils::recursiveDelete($testsGenPath);
			FileUtils::createDir($testsGenPath);
			FileUtils::createDir($testsGenPath . '/' . $this->beanDirName);
			FileUtils::createDir($testsGenPath . '/' . $this->daoDirName);
			FileUtils::createDir($testsGenPath . '/' . $this->enumsDirName);
		}

		$jsGenPath = $this->jsSourceDirectory !== null
				? $this->jsSourceDirectory . '/' . $this->genDirName
				: null;
		if ($jsGenPath !== null) {
			FileUtils::recursiveDelete($jsGenPath);
			FileUtils::createDir($jsGenPath);
			FileUtils::createDir($jsGenPath . '/' . $this->beanDirName);
			FileUtils::createDir($jsGenPath . '/' . $this->enumsDirName);
		}

		if (!$beans) {
			$this->log->info('Nothing has been retrieved as beans');
			return;
		}
		$sOrNotS = count($beans) === 1 ? '' : 's';
		$this->log->info(count($beans) . " table$sOrNotS retrieved as bean$sOrNotS: "
				. implode(', ', array_map(static fn(Bean $bean) => $bean->getClassName(), $beans)));

		foreach ($beans as $bean) {
			file_put_contents(
					$phpGenPath . '/' . $this->beanDirName . '/' . $bean->getClassName() . '.php',
					$bean->getPhpClassFileContent(),
			);
			file_put_contents(
					$phpGenPath . '/' . $this->daoDirName . '/' . $bean->getDaoName() . '.php',
					$bean->getPhpDaoFileContent(),
			);

			if ($testsGenPath !== null) {
				file_put_contents(
						$testsGenPath . '/' . $this->beanDirName . '/' . $bean->getClassName() . 'Test.php',
						$bean->getPhpTestFileContent(),
				);
				file_put_contents(
						$testsGenPath . '/' . $this->daoDirName . '/' . $bean->getDaoName() . 'Test.php',
						$bean->getPhpDaoTestFileContent(),
				);
			}

			if ($jsGenPath !== null) {
				file_put_contents(
						$jsGenPath . '/' . $this->beanDirName . '/' . $bean->getClassName() . '.js',
						$bean->getJsClassFileContent(),
				);
			}
		}

		if (!$enums) {
			$this->log->info('Nothing has been retrieved as enums');
			return;
		}
		$sOrNotS = count($enums) === 1 ? '' : 's';
		$this->log->info("Enum$sOrNotS found: "
				. implode(', ', array_map(static fn(Enum $enum) => $enum->name, $enums)));

		foreach ($enums as $enum) {
			file_put_contents(
					$phpGenPath . '/' . $this->enumsDirName . '/' . $enum->name . '.php',
					$enum->getPhpFileContent(),
			);

			if ($testsGenPath !== null) {
				file_put_contents(
						$testsGenPath . '/' . $this->enumsDirName . '/' . $enum->name . 'Test.php',
						$enum->getPhpTestFileContent($this->testDirName),
				);
			}

			if ($jsGenPath !== null) {
				file_put_contents(
						$jsGenPath . '/' . $this->enumsDirName . '/' . $enum->name . '.js',
						$enum->getJsFileContent(),
				);
			}
		}
	}

}
