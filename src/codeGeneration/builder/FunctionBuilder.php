<?php

namespace SqlToCodeGenerator\codeGeneration\builder;

use SqlToCodeGenerator\codeGeneration\bean\Line;
use SqlToCodeGenerator\codeGeneration\enums\Visibility;

class FunctionBuilder {

	/**
	 * @param string $name
	 * @param string $returnType
	 * @param Visibility $visibility
	 * @param bool $isStatic
	 * @param bool $isFinal
	 * @param FunctionParameterBuilder[] $parameterBuilders
	 * @param Line[] $lines
	 * @param string[] $documentationLines
	 */
	public function __construct(
			readonly private string $name,
			readonly private string $returnType,
			private Visibility $visibility = Visibility::PUBLIC,
			private bool $isStatic = false,
			private bool $isFinal = false,
			private array $parameterBuilders = array(),
			private array $lines = array(),
			private array $documentationLines = array()
	) {}

	/**
	 * @see __construct
	 */
	public static function create(
			string $name,
			string $returnType,
			Visibility $visibility = Visibility::PUBLIC,
			bool $isStatic = false,
			bool $isFinal = false,
			array $parameterBuilders = array(),
			array $lines = array(),
			array $documentationLines = array()
	): static {
		return new static(
				name: $name,
				returnType: $returnType,
				visibility: $visibility,
				isStatic: $isStatic,
				isFinal: $isFinal,
				parameterBuilders: $parameterBuilders,
				lines: $lines,
				documentationLines: $documentationLines,
		);
	}

	public function getName(): string {
		return $this->name;
	}

	public function getReturnType(): string {
		return $this->returnType;
	}

	public function getVisibility(): Visibility {
		return $this->visibility;
	}

	public function setVisibility(Visibility $visibility): static {
		$this->visibility = $visibility;
		return $this;
	}

	public function isStatic(): bool {
		return $this->isStatic;
	}

	public function setIsStatic(bool $isStatic): static {
		$this->isStatic = $isStatic;
		return $this;
	}

	public function isFinal(): bool {
		return $this->isFinal;
	}

	public function setIsFinal(bool $isFinal): static {
		$this->isFinal = $isFinal;
		return $this;
	}

	/**
	 * @return FunctionParameterBuilder[]
	 */
	public function getParameterBuilders(): array {
		return $this->parameterBuilders;
	}

	/**
	 * @param FunctionParameterBuilder[] $parameterBuilders
	 */
	public function setParameterBuilders(array $parameterBuilders): static {
		$this->parameterBuilders = $parameterBuilders;
		return $this;
	}

	public function addParameterBuilders(FunctionParameterBuilder ...$parameterBuilders): static {
		array_push($this->parameterBuilders, ...$parameterBuilders);
		return $this;
	}

	/**
	 * @return Line[]
	 */
	public function getLines(): array {
		return $this->lines;
	}

	/**
	 * @param Line[] $lines
	 */
	public function setLines(array $lines): static {
		$this->lines = $lines;
		return $this;
	}

	public function addLines(Line ...$lines): static {
		array_push($this->lines, ...$lines);
		return $this;
	}

	/**
	 * @return string[]
	 */
	public function getDocumentationLines(): array {
		return $this->documentationLines;
	}

	/**
	 * @param string[] $documentationLines
	 */
	public function setDocumentationLines(array $documentationLines): static {
		$this->documentationLines = $documentationLines;
		return $this;
	}

	public function addDocumentationLines(string ...$documentationLines): static {
		array_push($this->documentationLines, ...$documentationLines);
		return $this;
	}

	public function getPhpFileContent(string $baseIndentation): string {
		$fileContent = '';
		
		if ($this->documentationLines) {
			$fileContent .= $baseIndentation . "/**\n";
			foreach ($this->documentationLines as $documentationLine) {
				$fileContent .= $baseIndentation . " * $documentationLine\n";
			}
			$fileContent .= $baseIndentation . " */\n";
		}
		$isFinalString = $this->isFinal ? 'final ' : '';
		$isStaticString = $this->isStatic ? ' static' : '';

		$fileContent .= $baseIndentation . "$isFinalString{$this->visibility->value}$isStaticString function $this->name(";

		if ($this->parameterBuilders) {
			$functionDeclaration = "\n";
			foreach ($this->parameterBuilders as $parameterIndex => $parameterBuilder) {
				$functionDeclaration .= $baseIndentation . "\t" . $parameterBuilder->getPhpFileContent();
				if ($parameterIndex < count($this->parameterBuilders) - 1) {
					$functionDeclaration .= ",\n";
				} else {
					$functionDeclaration .= "\n";
				}
			}
			$fileContent .= "$functionDeclaration$baseIndentation";
		}

		$fileContent .= "): $this->returnType {\n";

		$increment = substr_count($baseIndentation, "\t") + 1;
		foreach ($this->lines as $lineBuilder) {
			$increment += $lineBuilder->incrementModifierFromPreviousLine;
			$fileContent .= str_repeat("\t", $increment) . $lineBuilder->content . "\n";
		}

		return "$fileContent$baseIndentation}";
	}

	public function getJsFileContent(string $baseIndentation): string {
		$fileContent = $baseIndentation . "/**\n";

		foreach ($this->documentationLines as $documentationLine) {
			$fileContent .= $baseIndentation . " * $documentationLine\n";
		}
		foreach ($this->parameterBuilders as $parameterBuilder) {
			$fileContent .= $baseIndentation . "{$parameterBuilder->getJsDocFileContent("\t")}\n";
		}
		$fileContent .= $baseIndentation . " * @return " . '{' . $this->returnType . '}' . "\n";
		$fileContent .= $baseIndentation . " */\n";

		$parametersAsStrings = array();
		foreach ($this->parameterBuilders as $parameterBuilder) {
			$parametersAsStrings[] = "{$parameterBuilder->getJsParamFileContent()}\n";
		}
		$fileContent .= $baseIndentation . ($this->isStatic ? 'static ' : '')
				. "$this->name(" . implode(', ', $parametersAsStrings) . ") {\n";

		$increment = substr_count($baseIndentation, "\t") + 1;
		foreach ($this->lines as $lineBuilder) {
			$increment += $lineBuilder->incrementModifierFromPreviousLine;
			$fileContent .= str_repeat("\t", $increment) . $lineBuilder->content . "\n";
		}
		$fileContent .= $baseIndentation . "}";

		return $fileContent;
	}

}
