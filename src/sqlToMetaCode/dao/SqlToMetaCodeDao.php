<?php

namespace SqlToCodeGenerator\sqlToMetaCode\dao;

use DateTime;
use PDO;
use SqlToCodeGenerator\codeGeneration\metadata\Bean;
use SqlToCodeGenerator\sql\PdoContainer;
use SqlToCodeGenerator\sqlToMetaCode\bean\Column;
use SqlToCodeGenerator\sqlToMetaCode\bean\KeyColumnUsage;
use SqlToCodeGenerator\sqlToMetaCode\bean\Table;
use SqlToCodeGenerator\sqlToMetaCode\SqlToMetaCodeUtils;

class SqlToMetaCodeDao {

	/**
	 * @param PdoContainer $pdoContainer
	 * @param string $bdd Database to generate sources from
	 * @param string[] $tablesToIgnore SQL tables to ignore
	 */
	public function __construct(
			private readonly PdoContainer $pdoContainer,
			private readonly string $bdd,
			private readonly array $tablesToIgnore = array(),
	) {}

	private function getTableNameNoIntSql(string $tableNameCol): string {
		$tableNameNoIntSql = '';
		if ($this->tablesToIgnore) {
			$tableNameNoIntSql = 'AND ' . $tableNameCol . ' NOT IN ("' . implode('", "', $this->tablesToIgnore) . '")';
		}
		return $tableNameNoIntSql;
	}

	/**
	 * @return Table[]
	 */
	public function getTables(): array {
		$statement = $this->pdoContainer->getPdo()->prepare("
			SELECT *
			FROM information_schema.TABLES t
			WHERE t.TEMPORARY = 'N'
				AND t.TABLE_SCHEMA = :bdd
				{$this->getTableNameNoIntSql('t.TABLE_NAME')}
		");
		$statement->bindValue(':bdd', $this->bdd);
		$statement->execute();

		return array_map(
				static fn (array $sqlRow): Table => new Table(
						tableCatalog: $sqlRow['TABLE_CATALOG'],
						tableSchema: $sqlRow['TABLE_SCHEMA'],
						tableName: $sqlRow['TABLE_NAME'],
						tableType: $sqlRow['TABLE_TYPE'],
						engine: $sqlRow['ENGINE'],
						version: $sqlRow['VERSION'],
						rowFormat: $sqlRow['ROW_FORMAT'],
						tableRows: $sqlRow['TABLE_ROWS'],
						avgRowLength: $sqlRow['AVG_ROW_LENGTH'],
						dataLength: $sqlRow['DATA_LENGTH'],
						maxDataLength: $sqlRow['MAX_DATA_LENGTH'],
						indexLength: $sqlRow['INDEX_LENGTH'],
						dataFree: $sqlRow['DATA_FREE'],
						autoIncrement: $sqlRow['AUTO_INCREMENT'],
						createTime: $sqlRow['CREATE_TIME'] ? new DateTime($sqlRow['CREATE_TIME']) : null,
						updateTime: $sqlRow['UPDATE_TIME'] ? new DateTime($sqlRow['UPDATE_TIME']) : null,
						checkTime: $sqlRow['CHECK_TIME'] ? new DateTime($sqlRow['CHECK_TIME']) : null,
						tableCollation: $sqlRow['TABLE_COLLATION'],
						checksum: $sqlRow['CHECKSUM'],
						createOptions: $sqlRow['CREATE_OPTIONS'],
						tableComment: $sqlRow['TABLE_COMMENT'],
						maxIndexLength: $sqlRow['MAX_INDEX_LENGTH'],
						temporary: $sqlRow['TEMPORARY'],
				),
				$statement->fetchAll(PDO::FETCH_ASSOC)
		);
	}

	/**
	 * @param Table[] $fromTables
	 * @return Column[]
	 */
	public function getColumns(array $fromTables): array {
		$fromTablesSql = '';
		if ($this->tablesToIgnore) {
			$fromTablesSql = 'AND c.TABLE_NAME IN ("'
					. implode(
							'", "',
							array_map(
									static fn (Table $table): string => $table->tableName,
									$fromTables
							)
					) . '")';
		}

		$statement = $this->pdoContainer->getPdo()->prepare("
			SELECT *
			FROM information_schema.COLUMNS c
			WHERE c.TABLE_SCHEMA = :bdd
				{$this->getTableNameNoIntSql('c.TABLE_NAME')}
				$fromTablesSql
		");
		$statement->bindValue(':bdd', $this->bdd);
		$statement->execute();

		return array_map(
				static fn (array $sqlRow): Column => new Column(
						tableCatalog: $sqlRow['TABLE_CATALOG'],
						tableSchema: $sqlRow['TABLE_SCHEMA'],
						tableName: $sqlRow['TABLE_NAME'],
						columnName: $sqlRow['COLUMN_NAME'],
						ordinalPosition: $sqlRow['ORDINAL_POSITION'],
						columnDefault: $sqlRow['COLUMN_DEFAULT'],
						isNullable: $sqlRow['IS_NULLABLE'],
						dataType: $sqlRow['DATA_TYPE'],
						characterMaximumLength: $sqlRow['CHARACTER_MAXIMUM_LENGTH'],
						characterOctetLength: $sqlRow['CHARACTER_OCTET_LENGTH'],
						numericPrecision: $sqlRow['NUMERIC_PRECISION'],
						numericScale: $sqlRow['NUMERIC_SCALE'],
						datetimePrecision: $sqlRow['DATETIME_PRECISION'],
						characterSetName: $sqlRow['CHARACTER_SET_NAME'],
						collationName: $sqlRow['COLLATION_NAME'],
						columnType: $sqlRow['COLUMN_TYPE'],
						columnKey: $sqlRow['COLUMN_KEY'],
						extra: $sqlRow['EXTRA'],
						privileges: $sqlRow['PRIVILEGES'],
						columnComment: $sqlRow['COLUMN_COMMENT'],
						isGenerated: $sqlRow['IS_GENERATED'],
						generationExpression: $sqlRow['GENERATION_EXPRESSION'],
				),
				$statement->fetchAll(PDO::FETCH_ASSOC)
		);
	}

	/**
	 * @return KeyColumnUsage[]
	 */
	public function getKeyColumnUsages(): array {
		$statement = $this->pdoContainer->getPdo()->prepare("
			SELECT DISTINCT kcu.*
			FROM information_schema.COLUMNS c
				INNER JOIN information_schema.TABLES t ON t.TABLE_NAME = c.TABLE_NAME
				INNER JOIN information_schema.TABLE_CONSTRAINTS tc ON tc.TABLE_SCHEMA = c.TABLE_SCHEMA
					AND tc.TABLE_NAME = c.TABLE_NAME
				INNER JOIN information_schema.KEY_COLUMN_USAGE kcu ON kcu.CONSTRAINT_NAME = tc.CONSTRAINT_NAME

			WHERE c.TABLE_SCHEMA = :bdd
				AND kcu.TABLE_SCHEMA = c.TABLE_SCHEMA
				{$this->getTableNameNoIntSql('t.TABLE_NAME')}
		");
		$statement->bindValue(':bdd', $this->bdd);
		$statement->execute();

		return array_map(
				static fn (array $sqlRow): KeyColumnUsage => new KeyColumnUsage(
						constraintCatalog: $sqlRow['CONSTRAINT_CATALOG'],
						constraintSchema: $sqlRow['CONSTRAINT_SCHEMA'],
						constraintName: $sqlRow['CONSTRAINT_NAME'],
						tableCatalog: $sqlRow['TABLE_CATALOG'],
						tableSchema: $sqlRow['TABLE_SCHEMA'],
						tableName: $sqlRow['TABLE_NAME'],
						columnName: $sqlRow['COLUMN_NAME'],
						ordinalPosition: $sqlRow['ORDINAL_POSITION'],
						positionInUniqueConstraint: $sqlRow['POSITION_IN_UNIQUE_CONSTRAINT'],
						referencedTableSchema: $sqlRow['REFERENCED_TABLE_SCHEMA'],
						referencedTableName: $sqlRow['REFERENCED_TABLE_NAME'],
						referencedColumnName: $sqlRow['REFERENCED_COLUMN_NAME'],
				),
				$statement->fetchAll(PDO::FETCH_ASSOC)
		);
	}

	/**
	 * @return Bean[]
	 */
	public function getBeansFromSql(): array {
		$tables = $this->getTables();
		return SqlToMetaCodeUtils::getBeansFromMetaCodeBeans(
				$tables,
				$this->getColumns($tables),
				$this->getKeyColumnUsages()
		);
	}

}
