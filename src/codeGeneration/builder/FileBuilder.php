<?php

namespace SqlToCodeGenerator\codeGeneration\builder;

use SqlToCodeGenerator\codeGeneration\utils\CheckUtils;

abstract class FileBuilder {

	/**
	 * Use {@see create} instead
	 * @see create
	 */
	protected function __construct(
			readonly protected string $basePackage,
			readonly protected string $namespace,
			readonly protected string $name,
			protected array $imports = [],
			protected ?string $extends = null,
			protected ?string $implements = null,
			protected array $phpFunctionBuilders = [],
			protected array $jsFunctionBuilders = [],
			protected array $docLines = [],
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
	 * @param string $basePackage
	 * @param string $namespace
	 * @param string $name
	 * @param string[] $imports
	 * @param string|null $extends
	 * @param string|null $implements
	 * @param FunctionBuilder[] $phpFunctionBuilders
	 * @param FunctionBuilder[] $jsFunctionBuilders
	 * @param string[] $docLines
	 * @return static
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

	public function addImports(string ...$imports): static {
		array_push($this->imports, ...$imports);
		return $this;
	}

	public function addPhpFunctionBuilders(FunctionBuilder ...$functionBuilders): static {
		array_push($this->phpFunctionBuilders, ...$functionBuilders);
		return $this;
	}

	public function addJsFunctionBuilders(FunctionBuilder ...$functionBuilders): static {
		array_push($this->jsFunctionBuilders, ...$functionBuilders);
		return $this;
	}

	public function addDocLines(string ...$docLines): static {
		array_push($this->docLines, ...$docLines);
		return $this;
	}

	private function getBeforeFileTypeDeclarationPhpFileContent(): string {
		sort($this->imports);
		$importsAsString = $this->imports
				? "\n\n" . implode(
						"\n",
						array_map(
								static fn(string $import): string => "use $import;",
								array_unique($this->imports),
						),
				)
				: '';

		$docLinesAsString = $this->docLines
				? "\n" . implode(
						"\n",
						array_map(
								static fn(string $docLine): string => " * $docLine",
								$this->docLines,
						),
				)
				: '';

		return <<<PHP
			<?php

			namespace {$this->getFullNamespace()};$importsAsString

			/**
			 * This code is generated. Do not edit it$docLinesAsString
			 */

			PHP;
	}

	abstract public function getFileTypeWithName(): string;

	abstract public function getFieldsPhpFileContent(string $baseIndentation = ''): string;

	abstract public function getFieldsJsFileContent(): string;

	public function getPhpFileContent(): string {
		$extends = $this->extends ? " extends $this->extends" : '';
		$implements = $this->implements ? " implements $this->implements" : '';

		$fieldsAndFunctionsFileContent = '';
		if ($this->getFieldsPhpFileContent()) {
			$fieldsAndFunctionsFileContent .= "\n" . $this->getFieldsPhpFileContent("\t");
		}
		if ($this->phpFunctionBuilders) {
			$phpFunctionBuilders = implode("\n\n", array_map(
					static fn(FunctionBuilder $functionBuilder): string => $functionBuilder->getPhpFileContent("\t"),
					$this->phpFunctionBuilders,
			));
			$fieldsAndFunctionsFileContent .= "\n" . $phpFunctionBuilders . "\n";
		}

		return $this->getBeforeFileTypeDeclarationPhpFileContent() . <<<PHP
			{$this->getFileTypeWithName()}$extends$implements {
			$fieldsAndFunctionsFileContent
			}

			PHP;
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
