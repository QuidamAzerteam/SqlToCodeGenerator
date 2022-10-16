<?php

namespace SqlToCodeGenerator\codeGeneration\builder;

class ClassBuilder extends FileBuilder {

	/** @var FieldBuilder[] */
	private array $fieldBuilders;

	/**
	 * @param FieldBuilder[] $fieldBuilders
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
			array $fieldBuilders = array()
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
		$this->fieldBuilders = $fieldBuilders;
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
			array $fieldBuilders = array()
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

	/**
	 * @return FieldBuilder[]
	 */
	public function getFieldBuilders(): array {
		return $this->fieldBuilders;
	}

	/**
	 * @param FieldBuilder[] $fieldBuilders
	 */
	public function setFieldBuilders(array $fieldBuilders): static {
		$this->fieldBuilders = $fieldBuilders;
		return $this;
	}

	public function addFieldBuilders(FieldBuilder ...$fieldBuilders): static {
		array_push($this->fieldBuilders, ...$fieldBuilders);
		return $this;
	}

	public function getFileTypeWithName(): string {
		return "class $this->name";
	}

	public function getFieldsPhpFileContent(): string {
		$fileContent = '';
		if ($this->fieldBuilders) {
			foreach ($this->fieldBuilders as $fieldBuilder) {
				$fileContent .= $fieldBuilder->getPhpFileContent("\t") . "\n";
			}
			$fileContent .= "\n";
		}

		return $fileContent;
	}

	public function getFieldsJsFileContent(): string {
		$fileContent = '';
		if ($this->fieldBuilders) {
			foreach ($this->fieldBuilders as $fieldBuilder) {
				$fileContent .= $fieldBuilder->getJsFileContent('	') . "\n";
			}
			$fileContent .= "\n";
		}

		return $fileContent;
	}

}
