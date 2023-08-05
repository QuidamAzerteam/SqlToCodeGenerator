<?php

namespace SqlToCodeGenerator\codeGeneration\builder;

use SqlToCodeGenerator\codeGeneration\utils\CheckUtils;

class ClassBuilder extends FileBuilder {

	/**
	 * Use {@see create} instead
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
			private array $fieldBuilders = [],
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
		foreach ($this->fieldBuilders as $fieldBuilder) {
			CheckUtils::checkIfValueIsAClass($fieldBuilder, FieldBuilder::class);
		}
	}

	/**
	 * @param FieldBuilder[] $fieldBuilders
	 * @see parent::create
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
			array $fieldBuilders = [],
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
				fieldBuilders: $fieldBuilders,
		);
	}

	public function addFieldBuilders(FieldBuilder ...$fieldBuilders): static {
		array_push($this->fieldBuilders, ...$fieldBuilders);
		return $this;
	}

	public function getFileTypeWithName(): string {
		return "class $this->name";
	}

	public function getFieldsPhpFileContent(): string {
		return $this->fieldBuilders
				? implode("\n", array_map(
						static fn(FieldBuilder $fieldBuilder): string => $fieldBuilder->getPhpFileContent("\t"),
						$this->fieldBuilders,
				)) . "\n"
				: '';
	}

	public function getFieldsJsFileContent(): string {
		return $this->fieldBuilders
				? implode("\n", array_map(
						static fn(FieldBuilder $fieldBuilder): string => $fieldBuilder->getJsFileContent("\t"),
						$this->fieldBuilders,
				))
				: '';
	}

}
