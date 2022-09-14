<?php

namespace SqlToCodeGenerator\generation\builder;

class PhpClassBuilder {
	public string $basePackage;
	public string $namespace;
	public string $className;
	/** @var string[] */
	public array $imports = array();
	public string|null $extends = null;
	public string|null $implements = null;
	/** @var PhpFieldBuilder[] */
	public array $fieldBuilders = array();
	/** @var PhpFunctionBuilder[] */
	public array $functionBuilders = array();

	public function getFileName(): string {
		return $this->className;
	}

	public function getFileContent(): string {
		$fileContent = "<?php\n\n";
		$fileContent .= "namespace $this->basePackage\\$this->namespace;\n";
		$fileContent .= "\n";

		if ($this->imports) {
			foreach ($this->imports as $import) {
				$fileContent .= "use $import;\n";
			}
			$fileContent .= "\n";
		}

		$fileContent .= "/**\n";
		$fileContent .= " * This code is generated. Do not edit it\n";
		$fileContent .= " */\n";

		$extendsString = $this->extends ? ' extends ' . $this->extends : '';
		$implementString = $this->implements ? ' implement ' . $this->implements : '';
		$fileContent .= "class " . $this->className . "$extendsString$implementString {\n\n";

		if ($this->fieldBuilders) {
			foreach ($this->fieldBuilders as $fieldBuilder) {
				$defaultAsString = $fieldBuilder->defaultValue ? ' = ' . $fieldBuilder->defaultValue : '';
				$isNullableString = $fieldBuilder->isNullable ? '|null' : '';

				if ($fieldBuilder->customTypeHint) {
					$fileContent .= "	/** @type $fieldBuilder->customTypeHint */\n";
				}
				if ($fieldBuilder->isConst) {
					$fileContent .= "	final $fieldBuilder->visibility const $fieldBuilder->fieldName$defaultAsString;";
				} else {
					$fileContent .= "	$fieldBuilder->visibility $fieldBuilder->type$isNullableString \$$fieldBuilder->fieldName$defaultAsString;";
				}
				if ($fieldBuilder->comments) {
					$fileContent .= ' // ' . implode('. ', $fieldBuilder->comments);
				}
				$fileContent .= "\n";
			}
			$fileContent .= "\n";
		}

		foreach ($this->functionBuilders as $functionBuilder) {
			if ($functionBuilder->documentationLines) {
				$fileContent .= "	/**\n";
				foreach ($functionBuilder->documentationLines as $documentationLine) {
					$fileContent .= "	 * $documentationLine\n";
				}
				$fileContent .= "	 */\n";
			}
			$isFinalString = $functionBuilder->isFinal ? 'final ' : '';
			$isStaticString = $functionBuilder->isStatic ? ' static' : '';

			$functionDeclaration = "	$isFinalString$functionBuilder->visibility$isStaticString function $functionBuilder->name(";

			if ($functionBuilder->parameterBuilders) {
				$functionDeclaration .= "\n";
				foreach ($functionBuilder->parameterBuilders as $parameterIndex => $parameterBuilder) {
					$defaultAsString = $parameterBuilder->defaultValue ? ' = ' . $parameterBuilder->defaultValue : '';
					$functionDeclaration .= "		$parameterBuilder->type \$$parameterBuilder->name$defaultAsString";
					if ($parameterIndex < count($functionBuilder->parameterBuilders) - 1) {
						$functionDeclaration .= ",\n";
					} else {
						$functionDeclaration .= "\n	";
					}
				}
			}

			$fileContent .= "$functionDeclaration): $functionBuilder->returnType {\n";

			$increment = 2;
			foreach ($functionBuilder->lineBuilders as $lineBuilder) {
				$increment += $lineBuilder->incrementModifier;
				$fileContent .= str_repeat("	", $increment) . $lineBuilder->content . "\n";
			}

			$fileContent .= "	}\n\n";
		}

		$fileContent .= "}\n";

		return $fileContent;
	}
}
