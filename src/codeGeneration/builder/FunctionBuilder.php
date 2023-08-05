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
			private readonly string $name,
			private readonly string $returnType,
			private readonly Visibility $visibility = Visibility::PUBLIC,
			private readonly bool $isStatic = false,
			private readonly bool $isFinal = false,
			private array $parameterBuilders = [],
			private array $lines = [],
			private array $documentationLines = [],
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
			array $parameterBuilders = [],
			array $lines = [],
			array $documentationLines = [],
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

	public function addParameterBuilders(FunctionParameterBuilder ...$parameterBuilders): static {
		array_push($this->parameterBuilders, ...$parameterBuilders);
		return $this;
	}

	public function addLines(Line ...$lines): static {
		array_push($this->lines, ...$lines);
		return $this;
	}

	public function addDocumentationLines(string ...$documentationLines): static {
		array_push($this->documentationLines, ...$documentationLines);
		return $this;
	}

	public function getPhpFileContent(string $baseIndentation = ''): string {
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

		$fileContent .= "): $this->returnType {";

		if ($this->lines) {
			$fileContent .= "\n";
			$increment = substr_count($baseIndentation, "\t") + 1;
			foreach ($this->lines as $lineBuilder) {
				$increment += $lineBuilder->incrementModifierFromPreviousLine;
				$fileContent .= str_repeat("\t", $increment) . $lineBuilder->content . "\n";
			}
			return "$fileContent$baseIndentation}";
		}
		return $fileContent . "}";
	}

	public function getJsFileContent(string $baseIndentation = ''): string {
		$fileContent = $baseIndentation . "/**\n";

		foreach ($this->documentationLines as $documentationLine) {
			$fileContent .= $baseIndentation . " * $documentationLine\n";
		}
		foreach ($this->parameterBuilders as $parameterBuilder) {
			$fileContent .= $baseIndentation . "{$parameterBuilder->getJsDocFileContent()}\n";
		}
		$fileContent .= $baseIndentation . " * @return " . '{' . $this->returnType . '}' . "\n";
		$fileContent .= $baseIndentation . " */\n";

		$parametersAsStrings = [];
		foreach ($this->parameterBuilders as $parameterBuilder) {
			$parametersAsString = "{$parameterBuilder->getJsParamFileContent()}";
			if (count($this->parameterBuilders) > 1) {
				$parametersAsString .= "\n";
			}
			$parametersAsStrings[] = $parametersAsString;
		}
		$postParametersIndentation = count($parametersAsStrings) > 1 ? $baseIndentation : '';
		$fileContent .= $baseIndentation . ($this->isStatic ? 'static ' : '')
				. "$this->name(" . implode(', ', $parametersAsStrings) . "$postParametersIndentation) {";

		if ($this->lines) {
			$fileContent .= "\n";
			$increment = substr_count($baseIndentation, "\t") + 1;
			foreach ($this->lines as $lineBuilder) {
				$increment += $lineBuilder->incrementModifierFromPreviousLine;
				$fileContent .= str_repeat("\t", $increment) . $lineBuilder->content . "\n";
			}
			return "$fileContent$baseIndentation}";
		}

		return $fileContent . "}";
	}

}
