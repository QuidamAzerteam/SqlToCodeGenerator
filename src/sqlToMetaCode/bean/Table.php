<?php

namespace SqlToCodeGenerator\sqlToMetaCode\bean;

use DateTime;

readonly class Table {

	public function __construct(
			public string $tableCatalog,
			public string $tableSchema,
			public string $tableName,
			public string $tableType,
			public string $engine,
			public ?int $version,
			public ?string $rowFormat,
			public ?int $tableRows,
			public ?int $avgRowLength,
			public ?int $dataLength,
			public ?int $maxDataLength,
			public ?int $indexLength,
			public ?int $dataFree,
			public ?int $autoIncrement,
			public ?DateTime $createTime,
			public ?DateTime $updateTime,
			public ?DateTime $checkTime,
			public ?string $tableCollation,
			public ?int $checksum,
			public ?string $createOptions,
			public string $tableComment,
			public ?int $maxIndexLength,
			public ?string $temporary,
	) {}

	public function getUniqueIdentifier(): string {
		return implode('_', [$this->tableSchema, $this->tableName]);
	}

}
