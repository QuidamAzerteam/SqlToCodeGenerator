<?php

namespace SqlToCodeGenerator\generation\builder;

class PhpFunctionBuilder {
	public string $visibility = 'public';
	public bool $isStatic = false;
	public bool $isFinal = false;
	public string $name;
	public string $returnType;
	/** @var PhpFunctionParameterBuilder[] */
	public array $parameterBuilders = array();
	/** @var PhpFunctionLineBuilder[] */
	public array $lineBuilders = array();
	/** @var string[] */
	public array $documentationLines = array();
}
