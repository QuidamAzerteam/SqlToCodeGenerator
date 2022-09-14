<?php

namespace SqlToCodeGenerator\generation\builder;

class PhpFunctionParameterBuilder {
	public string $type;
	public string $name;
	public string|null $defaultValue = null;

	public static function create(string $type, string $name, ?string $defaultValue = null): self {
		$instance = new self();
		$instance->type = $type;
		$instance->name = $name;
		$instance->defaultValue = $defaultValue;
		return $instance;
	}
}
