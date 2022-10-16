<?php

namespace SqlToCodeGenerator\codeGeneration\builder;

use SqlToCodeGenerator\codeGeneration\utils\CheckUtils;

abstract class FileBuilder {

	/**
	 * @param string $basePackage
	 * @param string $namespace
	 * @param string $name
	 * @param string[] $imports
	 * @param string|null $extends
	 * @param string|null $implements
	 * @param FunctionBuilder[] $phpFunctionBuilders
	 * @param FunctionBuilder[] $jsFunctionBuilders
	 * @param string[] $docLines
	 */
	public function __construct(
			readonly protected string $basePackage,
			readonly protected string $namespace,
			readonly protected string $name,
			protected array $imports = array(),
			protected ?string $extends = null,
			protected ?string $implements = null,
			protected array $phpFunctionBuilders = array(),
			protected array $jsFunctionBuilders = array(),
			protected array $docLines = array()
	) {
		CheckUtils::checkPhpFullNamespace($this->getFullNamespace());
		if ($extends) {
			CheckUtils::checkPhpType($extends);
		}
		if ($implements) {
			CheckUtils::checkPhpType($implements);
		}
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
			array $docLines = array()
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
		);
	}

	public function getFullNamespace(): string {
		return $this->basePackage . '\\' . $this->namespace;
	}

	public function getBasePackage(): string {
		return $this->basePackage;
	}

	public function getNamespace(): string {
		return $this->namespace;
	}

	public function getName(): string {
		return $this->name;
	}

	/**
	 * @return string[]
	 */
	public function getImports(): array {
		return $this->imports;
	}

	/**
	 * @param string[] $imports
	 * @return static
	 */
	public function setImports(array $imports): static {
		$this->imports = $imports;
		return $this;
	}

	public function addImports(string ...$imports): static {
		array_push($this->imports, ...$imports);
		return $this;
	}

	public function getExtends(): ?string {
		return $this->extends;
	}

	public function setExtends(?string $extends): static {
		if ($extends) {
			CheckUtils::checkPhpType($extends);
		}
		$this->extends = $extends;
		return $this;
	}

	public function getImplements(): ?string {
		return $this->implements;
	}

	public function setImplements(?string $implements): static {
		if ($implements) {
			CheckUtils::checkPhpType($implements);
		}
		$this->implements = $implements;
		return $this;
	}

	/**
	 * @return FunctionBuilder[]
	 */
	public function getPhpFunctionBuilders(): array {
		return $this->phpFunctionBuilders;
	}

	/**
	 * @param FunctionBuilder[] $phpFunctionBuilders
	 * @return static
	 */
	public function setPhpFunctionBuilders(array $phpFunctionBuilders): static {
		$this->phpFunctionBuilders = $phpFunctionBuilders;
		return $this;
	}

	public function addPhpFunctionBuilders(FunctionBuilder ...$functionBuilders): static {
		array_push($this->phpFunctionBuilders, ...$functionBuilders);
		return $this;
	}

	/**
	 * @return FunctionBuilder[]
	 */
	public function getJsFunctionBuilders(): array {
		return $this->jsFunctionBuilders;
	}

	/**
	 * @param FunctionBuilder[] $jsFunctionBuilders
	 * @return static
	 */
	public function setJsFunctionBuilders(array $jsFunctionBuilders): static {
		$this->jsFunctionBuilders = $jsFunctionBuilders;
		return $this;
	}

	public function addJsFunctionBuilders(FunctionBuilder ...$functionBuilders): static {
		array_push($this->jsFunctionBuilders, ...$functionBuilders);
		return $this;
	}

	/**
	 * @return string[]
	 */
	public function getDocLines(): array {
		return $this->docLines;
	}

	/**
	 * @param string[] $docLines
	 * @return static
	 */
	public function setDocLines(array $docLines): static {
		$this->docLines = $docLines;
		return $this;
	}

	public function addDocLines(string ...$docLines): static {
		array_push($this->docLines, ...$docLines);
		return $this;
	}

	private function getBeforeFileTypeDeclarationPhpFileContent(): string {
		$fileContent = "<?php\n\n";
		$fileContent .= "namespace {$this->getFullNamespace()};\n";
		$fileContent .= "\n";

		if ($this->imports) {
			sort($this->imports);
			foreach (array_unique($this->imports) as $import) {
				$fileContent .= "use $import;\n";
			}
			$fileContent .= "\n";
		}

		$fileContent .= "/**\n";
		$fileContent .= " * This code is generated. Do not edit it\n";
		foreach ($this->docLines as $docLine) {
			$fileContent .= " * $docLine\n";
		}
		$fileContent .= " */\n";

		return $fileContent;
	}

	abstract function getFileTypeWithName(): string;

	function getFieldsPhpFileContent(): string {
		return '';
	}

	function getFieldsJsFileContent(): string {
		return '';
	}

	public function getPhpFileContent(): string {
		$fileContent = $this->getBeforeFileTypeDeclarationPhpFileContent();

		$name = $this->getFileTypeWithName();
		if ($this->extends) {
			$name .= " extends $this->extends";
		}
		if ($this->implements) {
			$name .= " implements $this->implements";
		}
		$fileContent .= "$name {\n\n";
		$fileContent .= $this->getFieldsPhpFileContent();

		foreach ($this->phpFunctionBuilders as $functionBuilder) {
			$fileContent .= "{$functionBuilder->getPhpFileContent("\t")}\n\n";
		}

		$fileContent .= "}\n";

		return $fileContent;
	}

	public function getJsFileContent(): string {
		$fileContent = "export class $this->name {\n";

		$jsFieldsContent = $this->getFieldsJsFileContent();
		if ($jsFieldsContent) {
			$fileContent .= "\n$jsFieldsContent\n";
		}

		if ($this->jsFunctionBuilders) {
			$fileContent .= "\n";
			foreach ($this->jsFunctionBuilders as $functionBuilder) {
				$fileContent .= "{$functionBuilder->getJsFileContent("\t")}\n\n";
			}
		}

		$fileContent .= "\n}";
		$fileContent .= "\n";

		return $fileContent;
	}

}
