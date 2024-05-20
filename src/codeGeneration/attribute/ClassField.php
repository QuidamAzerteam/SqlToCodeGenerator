<?php

namespace SqlToCodeGenerator\codeGeneration\attribute;

use Attribute;

#[Attribute] class ClassField {

	/** @var ClassFieldEnum[] */
	public readonly array $classFieldEnums;

	public function __construct(
			ClassFieldEnum ...$classFieldEnums,
	) {
		$this->classFieldEnums = $classFieldEnums;
	}

}
