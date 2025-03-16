<?php

namespace SqlToCodeGenerator\sqlToMetaCode\bean;

readonly class Column {

	public function __construct(
			public string $tableCatalog,
			public string $tableSchema,
			public string $tableName,
			public string $columnName,
			public int $ordinalPosition,
			public ?string $columnDefault,
			public string $isNullable,
			public string $dataType,
			public ?int $characterMaximumLength,
			public ?int $characterOctetLength,
			public ?int $numericPrecision,
			public ?int $numericScale,
			public ?int $datetimePrecision,
			public ?string $characterSetName,
			public ?string $collationName,
			public string $columnType,
			public string $columnKey,
			public string $extra,
			public string $privileges,
			public string $columnComment,
			public string $isGenerated,
			public ?string $generationExpression,
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

	public function isNullable(): bool {
		return $this->isNullable === 'YES';
	}

	public function isGenerated(): bool {
		return $this->isGenerated === 'ALWAYS';
	}

}
