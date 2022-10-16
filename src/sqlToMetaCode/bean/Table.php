<?php

namespace SqlToCodeGenerator\sqlToMetaCode\bean;

use DateTime;

class Table {

	public function __construct(
			public readonly string $tableCatalog,
			public readonly string $tableSchema,
			public readonly string $tableName,
			public readonly string $tableType,
			public readonly string $engine,
			public readonly ?int $version,
			public readonly ?string $rowFormat,
			public readonly ?int $tableRows,
			public readonly ?int $avgRowLength,
			public readonly ?int $dataLength,
			public readonly ?int $maxDataLength,
			public readonly ?int $indexLength,
			public readonly ?int $dataFree,
			public readonly ?int $autoIncrement,
			public readonly ?DateTime $createTime,
			public readonly ?DateTime $updateTime,
			public readonly ?DateTime $checkTime,
			public readonly ?string $tableCollation,
			public readonly ?int $checksum,
			public readonly ?string $createOptions,
			public readonly string $tableComment,
			public readonly ?int $maxIndexLength,
			public readonly ?string $temporary,
	) {}

}
