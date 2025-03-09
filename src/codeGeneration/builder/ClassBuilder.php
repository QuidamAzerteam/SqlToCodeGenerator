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
			private array $functionBuilders = [],
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
		foreach ($this->functionBuilders as $functionBuilder) {
			CheckUtils::checkIfValueIsAClass($functionBuilder, FunctionBuilder::class);
		}
	}

	/**
	 * @param FieldBuilder[] $fieldBuilders
	 * @param FunctionBuilder[] $functionBuilders
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
			array $functionBuilders = [],
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
				functionBuilders: $functionBuilders,
		);
	}

	public function addFieldBuilders(FieldBuilder ...$fieldBuilders): static {
		array_push($this->fieldBuilders, ...$fieldBuilders);
		return $this;
	}

	public function addFunctionBuilders(FunctionBuilder ...$functionBuilders): static {
		array_push($this->functionBuilders, ...$functionBuilders);
		return $this;
	}

	public function getFileTypeWithName(): string {
		return "class $this->name";
	}

	public function getFieldsPhpFileContent(string $baseIndentation = ''): string {
		$fieldBuildersAsPhp = $this->fieldBuilders
				? implode("\n", array_map(
						static fn(FieldBuilder $fieldBuilder): string => $fieldBuilder->getPhpFileContent($baseIndentation),
						$this->fieldBuilders,
				)) . "\n"
				: '';

		$functionBuildersAsPhp = $this->functionBuilders
				? implode("\n", array_map(
						static fn(FunctionBuilder $functionBuilder): string => $functionBuilder->getPhpFileContent($baseIndentation),
						$this->functionBuilders,
				))
				: '';

		return $fieldBuildersAsPhp . ($functionBuildersAsPhp ? "\n" . $functionBuildersAsPhp : '');
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
