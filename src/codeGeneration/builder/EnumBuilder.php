<?php

namespace SqlToCodeGenerator\codeGeneration\builder;

use SqlToCodeGenerator\codeGeneration\utils\CheckUtils;

class EnumBuilder extends FileBuilder {

	/** @var string[] */
	private array $fields;

	/**
	 * @param string[] $fields
	 * @see parent::__construct
	 */
	public function __construct(
			string $basePackage,
			string $namespace,
			string $name,
			array $imports = array(),
			?string $extends = null,
			?string $implements = null,
			array $phpFunctionBuilders = array(),
			array $jsFunctionBuilders = array(),
			array $docLines = array(),
			array $fields = array()
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
		$this->fields = $fields;
	}

	/**
	 * @see __construct
	 */
	public static function create(
			string $basePackage,
			string $namespace,
			string $name,
			array $imports = array(),
			?string $extends = null,
			?string $implements = null,
			array $phpFunctionBuilders = array(),
			array $jsFunctionBuilders = array(),
			array $docLines = array(),
			array $fields = array()
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

	/**
	 * @return string[]
	 */
	public function getFields(): array {
		return $this->fields;
	}

	/**
	 * @param string[] $fields
	 */
	public function setFields(array $fields): static {
		$this->fields = $fields;
		return $this;
	}

	public function addFields(string ...$fields): static {
		foreach ($fields as $field) {
			CheckUtils::checkPhpType($field);
			$this->fields[] = $field;
		}
		CheckUtils::checkUniqueFields($this->fields);
		return $this;
	}

	public function getFileTypeWithName(): string {
		return "enum $this->name: string";
	}

	public function getFieldsPhpFileContent(): string {
		$fileContent = '';
		if ($this->fields) {
			foreach ($this->fields as $field) {
				$fileContent .= "	case $field = '$field';\n";
			}
			$fileContent .= "\n";
		}
		return $fileContent;
	}

	public function getFieldsJsFileContent(): string {
		$fileContent = '';

		foreach ($this->fields as $index => $field) {
			$fileContent .= "	$field: " . ($index + 1) . ",";
			if ($index !== count($this->fields) - 1) {
				$fileContent .= "\n";
			}
		}

		return $fileContent;
	}

}
