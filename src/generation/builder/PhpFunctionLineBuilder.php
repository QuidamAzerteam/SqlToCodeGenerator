<?php

namespace SqlToCodeGenerator\generation\builder;

class PhpFunctionLineBuilder {
	public string $content;
	public int $incrementModifier = 0;

	public static function create(string $content, int $incrementModifier = 0): self {
		$instance = new self();
		$instance->content = $content;
		$instance->incrementModifier = $incrementModifier;
		return $instance;
	}
}
