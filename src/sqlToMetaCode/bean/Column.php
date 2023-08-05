<?php

namespace SqlToCodeGenerator\sqlToMetaCode\bean;

class Column {

	public function __construct(
			public readonly string $tableCatalog,
			public readonly string $tableSchema,
			public readonly string $tableName,
			public readonly string $columnName,
			public readonly int $ordinalPosition,
			public readonly ?string $columnDefault,
			public readonly string $isNullable,
			public readonly string $dataType,
			public readonly ?int $characterMaximumLength,
			public readonly ?int $characterOctetLength,
			public readonly ?int $numericPrecision,
			public readonly ?int $numericScale,
			public readonly ?int $datetimePrecision,
			public readonly ?string $characterSetName,
			public readonly ?string $collationName,
			public readonly string $columnType,
			public readonly string $columnKey,
			public readonly string $extra,
			public readonly string $privileges,
			public readonly string $columnComment,
			public readonly string $isGenerated,
			public readonly ?string $generationExpression,
	) {}

	public function getTableUniqueIdentifier(): string {
		return (new Table(
				tableCatalog: $this->tableCatalog,
				tableSchema: $this->tableSchema,
				tableName: $this->tableName,
				tableType: '',
				engine: '',
				version: null,
				rowFormat: null,
				tableRows: null,
				avgRowLength: null,
				dataLength: null,
				maxDataLength: null,
				indexLength: null,
				dataFree: null,
				autoIncrement: null,
				createTime: null,
				updateTime: null,
				checkTime: null,
				tableCollation: null,
				checksum: null,
				createOptions: null,
				tableComment: '',
				maxIndexLength: null,
				temporary: null,
		))->getUniqueIdentifier();
	}

	public function getUniqueIdentifier(): string {
		return implode('_', [
				$this->getTableUniqueIdentifier(),
				$this->columnName,
		]);
	}

}
