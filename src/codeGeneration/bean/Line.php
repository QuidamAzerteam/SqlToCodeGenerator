<?php

namespace SqlToCodeGenerator\codeGeneration\bean;

class Line {

	public string $content;
	public int $incrementModifierFromPreviousLine = 0;

	public static function create(string $content, int $incrementModifierFromPreviousLine = 0): self {
		$instance = new self();
		$instance->content = $content;
		$instance->incrementModifierFromPreviousLine = $incrementModifierFromPreviousLine;
		return $instance;
	}

}
