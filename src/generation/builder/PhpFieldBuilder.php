<?php

namespace SqlToCodeGenerator\generation\builder;

class PhpFieldBuilder {
	public string $visibility = 'public';
	public string $type = '';
	public string $fieldName;
	public string|null $defaultValue = null;
	public bool $isNullable = false;
	public bool $isConst = false;
	public string|null $customTypeHint = null;
	/** @var string[] */
	public array $comments = array();
}
