<?php

namespace SqlToCodeGenerator\test\sqlToMetaCode;

use DateTime;
use SqlToCodeGenerator\sqlToMetaCode\bean\Column;
use SqlToCodeGenerator\sqlToMetaCode\bean\KeyColumnUsage;
use SqlToCodeGenerator\sqlToMetaCode\bean\Table;

final class SqlToMetaTestUtils {

	public static function getTable(
			string $tableCatalog = '',
			string $tableSchema = '',
			string $tableName = '',
			string $tableType = '',
			string $engine = '',
			?int $version = null,
			?string $rowFormat = null,
			?int $tableRows = null,
			?int $avgRowLength = null,
			?int $dataLength = null,
			?int $maxDataLength = null,
			?int $indexLength = null,
			?int $dataFree = null,
			?int $autoIncrement = null,
			?DateTime $createTime = null,
			?DateTime $updateTime = null,
			?DateTime $checkTime = null,
			?string $tableCollation = null,
			?int $checksum = null,
			?string $createOptions = null,
			string $tableComment = '',
			?int $maxIndexLength = null,
			?string $temporary = null,
	): Table {
		return new Table(
				tableCatalog: $tableCatalog,
				tableSchema: $tableSchema,
				tableName: $tableName,
				tableType: $tableType,
				engine: $engine,
				version: $version,
				rowFormat: $rowFormat,
				tableRows: $tableRows,
				avgRowLength: $avgRowLength,
				dataLength: $dataLength,
				maxDataLength: $maxDataLength,
				indexLength: $indexLength,
				dataFree: $dataFree,
				autoIncrement: $autoIncrement,
				createTime: $createTime,
				updateTime: $updateTime,
				checkTime: $checkTime,
				tableCollation: $tableCollation,
				checksum: $checksum,
				createOptions: $createOptions,
				tableComment: $tableComment,
				maxIndexLength: $maxIndexLength,
				temporary: $temporary,
		);
	}

	public static function getColumn(
			string $tableCatalog = '',
			string $tableSchema = '',
			string $tableName = '',
			string $columnName = '',
			int $ordinalPosition = 0,
			?string $columnDefault = null,
			string $isNullable = '',
			string $dataType = '',
			?int $characterMaximumLength = null,
			?int $characterOctetLength = null,
			?int $numericPrecision = null,
			?int $numericScale = null,
			?int $datetimePrecision = null,
			?string $characterSetName = null,
			?string $collationName = null,
			string $columnType = '',
			string $columnKey = '',
			string $extra = '',
			string $privileges = '',
			string $columnComment = '',
			string $isGenerated = '',
			?string $generationExpression = null,
	): Column {
		return new Column(
				tableCatalog: $tableCatalog,
				tableSchema: $tableSchema,
				tableName: $tableName,
				columnName: $columnName,
				ordinalPosition: $ordinalPosition,
				columnDefault: $columnDefault,
				isNullable: $isNullable,
				dataType: $dataType,
				characterMaximumLength: $characterMaximumLength,
				characterOctetLength: $characterOctetLength,
				numericPrecision: $numericPrecision,
				numericScale: $numericScale,
				datetimePrecision: $datetimePrecision,
				characterSetName: $characterSetName,
				collationName: $collationName,
				columnType: $columnType,
				columnKey: $columnKey,
				extra: $extra,
				privileges: $privileges,
				columnComment: $columnComment,
				isGenerated: $isGenerated,
				generationExpression: $generationExpression,
		);
	}

	public static function getKeyColumnUsage(
			string $constraintCatalog = '',
			string $constraintSchema = '',
			string $constraintName = '',
			string $tableCatalog = '',
			string $tableSchema = '',
			string $tableName = '',
			string $columnName = '',
			int $ordinalPosition = 0,
			?int $positionInUniqueConstraint = null,
			?string $referencedTableSchema = null,
			?string $referencedTableName = null,
			?string $referencedColumnName = null,
	): KeyColumnUsage {
		return new KeyColumnUsage(
				constraintCatalog: $constraintCatalog,
				constraintSchema: $constraintSchema,
				constraintName: $constraintName,
				tableCatalog: $tableCatalog,
				tableSchema: $tableSchema,
				tableName: $tableName,
				columnName: $columnName,
				ordinalPosition: $ordinalPosition,
				positionInUniqueConstraint: $positionInUniqueConstraint,
				referencedTableSchema: $referencedTableSchema,
				referencedTableName: $referencedTableName,
				referencedColumnName: $referencedColumnName,
		);
	}
}
