<?php

namespace SqlToCodeGenerator\codeGeneration\builder;

use SqlToCodeGenerator\codeGeneration\utils\CheckUtils;

class EnumBuilder extends FileBuilder {

	/**
	 * Use {@see create} instead
	 * @param string[] $fields
	 * @see create
	 */
	protected function __construct(
			string $basePackage,
			string $namespace,
			string $name,
			array $imports = [],
			?string $extends = null,
			?string $implements = null,
			array $phpFunctionBuilders = [],
			array $jsFunctionBuilders = [],
			array $docLines = [],
			private readonly array $fields = [],
	) {
		parent::__construct(
				basePackage: $basePackage,
				namespace: $namespace,
				name: $name,
				imports: $imports,
				extends: $extends,
				implements: $implements,
				phpFunctionBuilders: $phpFunctionBuilders,
				jsFunctionBuilders: $jsFunctionBuilders,
				docLines: $docLines,
		);
		CheckUtils::checkUniqueFields($fields);
		foreach ($fields as $field) {
			CheckUtils::checkPhpType($field);
		}
	}

	/**
	 * @see __construct
	 */
	public static function create(
			string $basePackage,
			string $namespace,
			string $name,
			array $imports = [],
			?string $extends = null,
			?string $implements = null,
			array $phpFunctionBuilders = [],
			array $jsFunctionBuilders = [],
			array $docLines = [],
			array $fields = [],
	): static {
		return new static(
				basePackage: $basePackage,
				namespace: $namespace,
				name: $name,
				imports: $imports,
				extends: $extends,
				implements: $implements,
				phpFunctionBuilders: $phpFunctionBuilders,
				jsFunctionBuilders: $jsFunctionBuilders,
				docLines: $docLines,
				fields: $fields,
		);
	}

	public function getFileTypeWithName(): string {
		return "enum $this->name";
	}

	public function getFieldsPhpFileContent(): string {
		return $this->fields
				? implode("\n", array_map(
						static fn(string $field): string => "case $field;",
						$this->fields,
				)) . "\n"
				: '';
	}

	public function getFieldsJsFileContent(): string {
		return $this->fields
				? implode("\n", array_map(
						static fn(int $index, string $field): string => "\tstatic get $field() {\n\t\treturn " . ($index + 1) . ";\n\t}",
						array_keys($this->fields),
						$this->fields,
				))
				: '';
	}

}
