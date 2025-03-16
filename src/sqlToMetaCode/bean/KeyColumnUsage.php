<?php

namespace SqlToCodeGenerator\sqlToMetaCode\bean;

readonly class KeyColumnUsage {

	public function __construct(
			public string $constraintCatalog,
			public string $constraintSchema,
			public string $constraintName,
			public string $tableCatalog,
			public string $tableSchema,
			public string $tableName,
			public string $columnName,
			public int $ordinalPosition,
			public ?int $positionInUniqueConstraint,
			public ?string $referencedTableSchema,
			public ?string $referencedTableName,
			public ?string $referencedColumnName,
	) {}

	public function getFromColumnUniqueIdentifier(): string {
		return (new Column(
				tableCatalog: $this->tableCatalog,
				tableSchema: $this->tableSchema,
				tableName: $this->tableName,
				columnName: $this->columnName,
				ordinalPosition: 0,
				columnDefault: null,
				isNullable: '',
				dataType: '',
				characterMaximumLength: null,
				characterOctetLength: null,
				numericPrecision: null,
				numericScale: null,
				datetimePrecision: null,
				characterSetName: null,
				collationName: null,
				columnType: '',
				columnKey: '',
				extra: '',
				privileges: '',
				columnComment: '',
				isGenerated: '',
				generationExpression: null,
		))->getUniqueIdentifier();
	}

	public function getFromTableUniqueIdentifier(): string {
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

	public function getToColumnUniqueIdentifier(): string {
		return (new Column(
				tableCatalog: $this->tableCatalog,
				tableSchema: $this->referencedTableSchema,
				tableName: $this->referencedTableName,
				columnName: $this->referencedColumnName,
				ordinalPosition: 0,
				columnDefault: null,
				isNullable: '',
				dataType: '',
				characterMaximumLength: null,
				characterOctetLength: null,
				numericPrecision: null,
				numericScale: null,
				datetimePrecision: null,
				characterSetName: null,
				collationName: null,
				columnType: '',
				columnKey: '',
				extra: '',
				privileges: '',
				columnComment: '',
				isGenerated: '',
				generationExpression: null,
		))->getUniqueIdentifier();
	}

	public function getToTableUniqueIdentifier(): string {
		return (new Table(
				tableCatalog: $this->tableCatalog,
				tableSchema: $this->referencedTableSchema,
				tableName: $this->referencedTableName,
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
				$this->getFromColumnUniqueIdentifier(),
				$this->getToColumnUniqueIdentifier(),
				$this->constraintName,
		]);
	}

}
