<?php

namespace SqlToCodeGenerator\test\sqlToMetaCode;

use PDO;
use PDOStatement;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;
use SqlToCodeGenerator\sql\PdoContainer;
use SqlToCodeGenerator\sqlToMetaCode\dao\SqlToMetaCodeDao;

class SqlToMetaCodeDaoTest extends TestCase {

	public function testGetTablesFull(): void {
		$pdoStatement = $this->createMock(PDOStatement::class);
		$row = [
			'TABLE_CATALOG' => 'TABLE_CATALOG',
			'TABLE_SCHEMA' => 'TABLE_SCHEMA',
			'TABLE_NAME' => 'TABLE_NAME',
			'TABLE_TYPE' => 'TABLE_TYPE',
			'ENGINE' => 'ENGINE',
			'VERSION' => 1,
			'ROW_FORMAT' => 'ROW_FORMAT',
			'TABLE_ROWS' => 2,
			'AVG_ROW_LENGTH' => 3,
			'DATA_LENGTH' => 4,
			'MAX_DATA_LENGTH' => 5,
			'INDEX_LENGTH' => 6,
			'DATA_FREE' => 7,
			'AUTO_INCREMENT' => 8,
			'CREATE_TIME' => '1970-01-01',
			'UPDATE_TIME' => '1970-01-02',
			'CHECK_TIME' => '1970-01-03',
			'TABLE_COLLATION' => 'TABLE_COLLATION',
			'CHECKSUM' => 9,
			'CREATE_OPTIONS' => 'CREATE_OPTIONS',
			'TABLE_COMMENT' => 'TABLE_COMMENT',
			'MAX_INDEX_LENGTH' => 10,
			'TEMPORARY' => 'TEMPORARY',
		];
		$pdoStatement->method('fetchAll')->willReturn([$row]);

		$pdo = $this->createMock(PDO::class);
		$pdo->method('prepare')->willReturn($pdoStatement);

		$pdoContainer = $this->createMock(PdoContainer::class);
		$pdoContainer->method('getPdo')->willReturn($pdo);

		$dao = new SqlToMetaCodeDao($pdoContainer, 'test');
		[$table] = $dao->getTables();

		$this->assertSame($row['TABLE_CATALOG'], $table->tableCatalog);
		$this->assertSame($row['TABLE_SCHEMA'], $table->tableSchema);
		$this->assertSame($row['TABLE_NAME'], $table->tableName);
		$this->assertSame($row['TABLE_TYPE'], $table->tableType);
		$this->assertSame($row['ENGINE'], $table->engine);
		$this->assertSame($row['VERSION'], $table->version);
		$this->assertSame($row['ROW_FORMAT'], $table->rowFormat);
		$this->assertSame($row['TABLE_ROWS'], $table->tableRows);
		$this->assertSame($row['AVG_ROW_LENGTH'], $table->avgRowLength);
		$this->assertSame($row['DATA_LENGTH'], $table->dataLength);
		$this->assertSame($row['MAX_DATA_LENGTH'], $table->maxDataLength);
		$this->assertSame($row['INDEX_LENGTH'], $table->indexLength);
		$this->assertSame($row['DATA_FREE'], $table->dataFree);
		$this->assertSame($row['AUTO_INCREMENT'], $table->autoIncrement);
		$this->assertSame($row['CREATE_TIME'], $table->createTime->format('Y-m-d'));
		$this->assertSame($row['UPDATE_TIME'], $table->updateTime->format('Y-m-d'));
		$this->assertSame($row['CHECK_TIME'], $table->checkTime->format('Y-m-d'));
		$this->assertSame($row['TABLE_COLLATION'], $table->tableCollation);
		$this->assertSame($row['CHECKSUM'], $table->checksum);
		$this->assertSame($row['CREATE_OPTIONS'], $table->createOptions);
		$this->assertSame($row['TABLE_COMMENT'], $table->tableComment);
		$this->assertSame($row['MAX_INDEX_LENGTH'], $table->maxIndexLength);
		$this->assertSame($row['TEMPORARY'], $table->temporary);
	}

	#[Depends('testGetTablesFull')]
	public function testGetTablesMinimal(): void {
		$pdoStatement = $this->createMock(PDOStatement::class);
		$row = [
			'TABLE_CATALOG' => 'TABLE_CATALOG',
			'TABLE_SCHEMA' => 'TABLE_SCHEMA',
			'TABLE_NAME' => 'TABLE_NAME',
			'TABLE_TYPE' => 'TABLE_TYPE',
			'ENGINE' => 'ENGINE',
			'VERSION' => null,
			'ROW_FORMAT' => null,
			'TABLE_ROWS' => null,
			'AVG_ROW_LENGTH' => null,
			'DATA_LENGTH' => null,
			'MAX_DATA_LENGTH' => null,
			'INDEX_LENGTH' => null,
			'DATA_FREE' => null,
			'AUTO_INCREMENT' => null,
			'CREATE_TIME' => null,
			'UPDATE_TIME' => null,
			'CHECK_TIME' => null,
			'TABLE_COLLATION' => null,
			'CHECKSUM' => null,
			'CREATE_OPTIONS' => null,
			'TABLE_COMMENT' => 'TABLE_COMMENT',
			'MAX_INDEX_LENGTH' => null,
			'TEMPORARY' => null,
		];
		$pdoStatement->method('fetchAll')->willReturn([$row]);

		$pdo = $this->createMock(PDO::class);
		$pdo->method('prepare')->willReturn($pdoStatement);

		$pdoContainer = $this->createMock(PdoContainer::class);
		$pdoContainer->method('getPdo')->willReturn($pdo);

		$dao = new SqlToMetaCodeDao($pdoContainer, 'test');
		[$table] = $dao->getTables();

		$this->assertNull($table->version);
		$this->assertNull($table->rowFormat);
		$this->assertNull($table->tableRows);
		$this->assertNull($table->avgRowLength);
		$this->assertNull($table->dataLength);
		$this->assertNull($table->maxDataLength);
		$this->assertNull($table->indexLength);
		$this->assertNull($table->dataFree);
		$this->assertNull($table->autoIncrement);
		$this->assertNull($table->createTime);
		$this->assertNull($table->updateTime);
		$this->assertNull($table->checkTime);
		$this->assertNull($table->tableCollation);
		$this->assertNull($table->checksum);
		$this->assertNull($table->createOptions);
		$this->assertNull($table->maxIndexLength);
		$this->assertNull($table->temporary);
	}

	public function testGetColumnsFull(): void {
		$pdoStatement = $this->createMock(PDOStatement::class);
		$row = [
			'TABLE_CATALOG' => 'TABLE_CATALOG',
			'TABLE_SCHEMA' => 'TABLE_SCHEMA',
			'TABLE_NAME' => 'TABLE_NAME',
			'COLUMN_NAME' => 'COLUMN_NAME',
			'ORDINAL_POSITION' => 1,
			'COLUMN_DEFAULT' => 'COLUMN_DEFAULT',
			'IS_NULLABLE' => 'IS_NULLABLE',
			'DATA_TYPE' => 'DATA_TYPE',
			'CHARACTER_MAXIMUM_LENGTH' => 2,
			'CHARACTER_OCTET_LENGTH' => 3,
			'NUMERIC_PRECISION' => 4,
			'NUMERIC_SCALE' => 5,
			'DATETIME_PRECISION' => 6,
			'CHARACTER_SET_NAME' => 'CHARACTER_SET_NAME',
			'COLLATION_NAME' => 'COLLATION_NAME',
			'COLUMN_TYPE' => 'COLUMN_TYPE',
			'COLUMN_KEY' => 'COLUMN_KEY',
			'EXTRA' => 'EXTRA',
			'PRIVILEGES' => 'PRIVILEGES',
			'COLUMN_COMMENT' => 'COLUMN_COMMENT',
			'IS_GENERATED' => 'IS_GENERATED',
			'GENERATION_EXPRESSION' => 'GENERATION_EXPRESSION',
		];
		$pdoStatement->method('fetchAll')->willReturn([$row]);

		$pdo = $this->createMock(PDO::class);
		$pdo->method('prepare')->willReturn($pdoStatement);

		$pdoContainer = $this->createMock(PdoContainer::class);
		$pdoContainer->method('getPdo')->willReturn($pdo);

		$dao = new SqlToMetaCodeDao($pdoContainer, 'test');
		[$column] = $dao->getColumns([]);

		$this->assertSame($row['TABLE_CATALOG'], $column->tableCatalog);
		$this->assertSame($row['TABLE_SCHEMA'], $column->tableSchema);
		$this->assertSame($row['TABLE_NAME'], $column->tableName);
		$this->assertSame($row['COLUMN_NAME'], $column->columnName);
		$this->assertSame($row['ORDINAL_POSITION'], $column->ordinalPosition);
		$this->assertSame($row['COLUMN_DEFAULT'], $column->columnDefault);
		$this->assertSame($row['IS_NULLABLE'], $column->isNullable);
		$this->assertSame($row['DATA_TYPE'], $column->dataType);
		$this->assertSame($row['CHARACTER_MAXIMUM_LENGTH'], $column->characterMaximumLength);
		$this->assertSame($row['CHARACTER_OCTET_LENGTH'], $column->characterOctetLength);
		$this->assertSame($row['NUMERIC_PRECISION'], $column->numericPrecision);
		$this->assertSame($row['NUMERIC_SCALE'], $column->numericScale);
		$this->assertSame($row['DATETIME_PRECISION'], $column->datetimePrecision);
		$this->assertSame($row['CHARACTER_SET_NAME'], $column->characterSetName);
		$this->assertSame($row['COLLATION_NAME'], $column->collationName);
		$this->assertSame($row['COLUMN_TYPE'], $column->columnType);
		$this->assertSame($row['COLUMN_KEY'], $column->columnKey);
		$this->assertSame($row['EXTRA'], $column->extra);
		$this->assertSame($row['PRIVILEGES'], $column->privileges);
		$this->assertSame($row['COLUMN_COMMENT'], $column->columnComment);
		$this->assertSame($row['IS_GENERATED'], $column->isGenerated);
		$this->assertSame($row['GENERATION_EXPRESSION'], $column->generationExpression);
	}

	#[Depends('testGetColumnsFull')]
	public function testGetColumnsMinimal(): void {
		$pdoStatement = $this->createMock(PDOStatement::class);
		$row = [
			'TABLE_CATALOG' => 'TABLE_CATALOG',
			'TABLE_SCHEMA' => 'TABLE_SCHEMA',
			'TABLE_NAME' => 'TABLE_NAME',
			'COLUMN_NAME' => 'COLUMN_NAME',
			'ORDINAL_POSITION' => 1,
			'COLUMN_DEFAULT' => null,
			'IS_NULLABLE' => 'IS_NULLABLE',
			'DATA_TYPE' => 'DATA_TYPE',
			'CHARACTER_MAXIMUM_LENGTH' => null,
			'CHARACTER_OCTET_LENGTH' => null,
			'NUMERIC_PRECISION' => null,
			'NUMERIC_SCALE' => null,
			'DATETIME_PRECISION' => null,
			'CHARACTER_SET_NAME' => null,
			'COLLATION_NAME' => 'COLLATION_NAME',
			'COLUMN_TYPE' => 'COLUMN_TYPE',
			'COLUMN_KEY' => 'COLUMN_KEY',
			'EXTRA' => 'EXTRA',
			'PRIVILEGES' => 'PRIVILEGES',
			'COLUMN_COMMENT' => 'COLUMN_COMMENT',
			'IS_GENERATED' => 'IS_GENERATED',
			'GENERATION_EXPRESSION' => null,
		];
		$pdoStatement->method('fetchAll')->willReturn([$row]);

		$pdo = $this->createMock(PDO::class);
		$pdo->method('prepare')->willReturn($pdoStatement);

		$pdoContainer = $this->createMock(PdoContainer::class);
		$pdoContainer->method('getPdo')->willReturn($pdo);

		$dao = new SqlToMetaCodeDao($pdoContainer, 'test');
		[$column] = $dao->getColumns([]);

		$this->assertNull($column->columnDefault);
		$this->assertNull($column->characterMaximumLength);
		$this->assertNull($column->characterOctetLength);
		$this->assertNull($column->numericPrecision);
		$this->assertNull($column->numericScale);
		$this->assertNull($column->datetimePrecision);
		$this->assertNull($column->characterSetName);
		$this->assertNull($column->generationExpression);
	}

	public function testGetKeyColumnUsagesFull(): void {
		$pdoStatement = $this->createMock(PDOStatement::class);
		$row = [
			'CONSTRAINT_CATALOG' => 'CONSTRAINT_CATALOG',
			'CONSTRAINT_SCHEMA' => 'CONSTRAINT_SCHEMA',
			'CONSTRAINT_NAME' => 'CONSTRAINT_NAME',
			'TABLE_CATALOG' => 'TABLE_CATALOG',
			'TABLE_SCHEMA' => 'TABLE_SCHEMA',
			'TABLE_NAME' => 'TABLE_NAME',
			'COLUMN_NAME' => 'COLUMN_NAME',
			'ORDINAL_POSITION' => 1,
			'POSITION_IN_UNIQUE_CONSTRAINT' => 2,
			'REFERENCED_TABLE_SCHEMA' => 'REFERENCED_TABLE_SCHEMA',
			'REFERENCED_TABLE_NAME' => 'REFERENCED_TABLE_NAME',
			'REFERENCED_COLUMN_NAME' => 'REFERENCED_COLUMN_NAME',
		];
		$pdoStatement->method('fetchAll')->willReturn([$row]);

		$pdo = $this->createMock(PDO::class);
		$pdo->method('prepare')->willReturn($pdoStatement);

		$pdoContainer = $this->createMock(PdoContainer::class);
		$pdoContainer->method('getPdo')->willReturn($pdo);

		$dao = new SqlToMetaCodeDao($pdoContainer, 'test');
		[$keyColumnUsage] = $dao->getKeyColumnUsages();

		$this->assertSame($row['CONSTRAINT_CATALOG'], $keyColumnUsage->constraintCatalog);
		$this->assertSame($row['CONSTRAINT_SCHEMA'], $keyColumnUsage->constraintSchema);
		$this->assertSame($row['CONSTRAINT_NAME'], $keyColumnUsage->constraintName);
		$this->assertSame($row['TABLE_CATALOG'], $keyColumnUsage->tableCatalog);
		$this->assertSame($row['TABLE_SCHEMA'], $keyColumnUsage->tableSchema);
		$this->assertSame($row['TABLE_NAME'], $keyColumnUsage->tableName);
		$this->assertSame($row['COLUMN_NAME'], $keyColumnUsage->columnName);
		$this->assertSame($row['ORDINAL_POSITION'], $keyColumnUsage->ordinalPosition);
		$this->assertSame($row['POSITION_IN_UNIQUE_CONSTRAINT'], $keyColumnUsage->positionInUniqueConstraint);
		$this->assertSame($row['REFERENCED_TABLE_SCHEMA'], $keyColumnUsage->referencedTableSchema);
		$this->assertSame($row['REFERENCED_TABLE_NAME'], $keyColumnUsage->referencedTableName);
		$this->assertSame($row['REFERENCED_COLUMN_NAME'], $keyColumnUsage->referencedColumnName);
	}

	#[Depends('testGetKeyColumnUsagesFull')]
	public function testGetKeyColumnUsagesMinimal(): void {
		$pdoStatement = $this->createMock(PDOStatement::class);
		$row = [
			'CONSTRAINT_CATALOG' => 'CONSTRAINT_CATALOG',
			'CONSTRAINT_SCHEMA' => 'CONSTRAINT_SCHEMA',
			'CONSTRAINT_NAME' => 'CONSTRAINT_NAME',
			'TABLE_CATALOG' => 'TABLE_CATALOG',
			'TABLE_SCHEMA' => 'TABLE_SCHEMA',
			'TABLE_NAME' => 'TABLE_NAME',
			'COLUMN_NAME' => 'COLUMN_NAME',
			'ORDINAL_POSITION' => 1,
			'POSITION_IN_UNIQUE_CONSTRAINT' => null,
			'REFERENCED_TABLE_SCHEMA' => null,
			'REFERENCED_TABLE_NAME' => null,
			'REFERENCED_COLUMN_NAME' => null,
		];
		$pdoStatement->method('fetchAll')->willReturn([$row]);

		$pdo = $this->createMock(PDO::class);
		$pdo->method('prepare')->willReturn($pdoStatement);

		$pdoContainer = $this->createMock(PdoContainer::class);
		$pdoContainer->method('getPdo')->willReturn($pdo);

		$dao = new SqlToMetaCodeDao($pdoContainer, 'test');
		[$keyColumnUsage] = $dao->getKeyColumnUsages();

		$this->assertNull($keyColumnUsage->positionInUniqueConstraint);
		$this->assertNull($keyColumnUsage->referencedTableSchema);
		$this->assertNull($keyColumnUsage->referencedTableName);
		$this->assertNull($keyColumnUsage->referencedColumnName);
	}

}
