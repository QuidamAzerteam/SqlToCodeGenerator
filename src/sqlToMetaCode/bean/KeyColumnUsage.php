<?php

namespace SqlToCodeGenerator\sqlToMetaCode\bean;

class KeyColumnUsage {

	public function __construct(
			public readonly string $constraintCatalog,
			public readonly string $constraintSchema,
			public readonly string $constraintName,
			public readonly string $tableCatalog,
			public readonly string $tableSchema,
			public readonly string $tableName,
			public readonly string $columnName,
			public readonly int $ordinalPosition,
			public readonly ?int $positionInUniqueConstraint,
			public readonly ?string $referencedTableSchema,
			public readonly ?string $referencedTableName,
			public readonly ?string $referencedColumnName,
	) {}

}
