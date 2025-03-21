<?php

namespace SqlToCodeGenerator\codeGeneration\builder;

use SqlToCodeGenerator\codeGeneration\utils\CheckUtils;

readonly class FunctionParameterBuilder {

	public function __construct(
			private string $type,
			private string $name,
			private ?string $defaultValue = null,
	) {
		CheckUtils::checkPhpType($type);
		CheckUtils::checkPhpFieldName($name);
	}

	public static function create(string $type, string $name, ?string $defaultValue = null): static {
		return new static(
				type: $type,
				name: $name,
				defaultValue: $defaultValue,
		);
	}

	public function getPhpFileContent(): string {
		$defaultAsString = $this->defaultValue ? ' = ' . $this->defaultValue : '';
		return "$this->type \$$this->name$defaultAsString";
	}

	public function getJsDocFileContent(string $baseIndentation = ''): string {
		if ($this->defaultValue) {
			return "$baseIndentation * @param {{$this->type}} [$this->name=$this->defaultValue]";
		}
		return "$baseIndentation * @param {{$this->type}} $this->name";
	}

	public function getJsParamFileContent(): string {
		$defaultAsString = $this->defaultValue ? ' = ' . $this->defaultValue : '';
		return "$this->name$defaultAsString";
	}

}
