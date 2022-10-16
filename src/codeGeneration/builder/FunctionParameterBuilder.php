<?php

namespace SqlToCodeGenerator\codeGeneration\builder;

use SqlToCodeGenerator\codeGeneration\utils\CheckUtils;

class FunctionParameterBuilder {

	public function __construct(
			private readonly string $type,
			private readonly string $name,
			private readonly ?string $defaultValue = null
	) {
		CheckUtils::checkPhpType($type);
		CheckUtils::checkPhpFieldName($name);
	}

	public static function create(string $type, string $name, ?string $defaultValue = null): self {
		return new self(
				type: $type,
				name: $name,
				defaultValue: $defaultValue,
		);
	}

	public function getType(): string {
		return $this->type;
	}

	public function getName(): string {
		return $this->name;
	}

	public function getDefaultValue(): ?string {
		return $this->defaultValue;
	}

	public function getPhpFileContent(): string {
		$defaultAsString = $this->defaultValue ? ' = ' . $this->defaultValue : '';
		return "$this->type \$$this->name$defaultAsString";
	}

	public function getJsDocFileContent(string $baseIndentation): string {
		if ($this->defaultValue) {
			return $baseIndentation . " * @param " . '{' . $this->type . '}' . " [$this->name=$this->defaultValue]";
		}
		return $baseIndentation . " * @param " . '{' . $this->type . '}' . " $this->name";
	}

	public function getJsParamFileContent(): string {
		$defaultAsString = $this->defaultValue ? ' = ' . $this->defaultValue : '';
		return "$this->name$defaultAsString";
	}

}
