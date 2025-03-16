<?php

namespace SqlToCodeGenerator\codeGeneration\attribute;

use Attribute;

#[Attribute] readonly class ClassField {

	/** @var ClassFieldEnum[] */
	public array $classFieldEnums;

	public function __construct(
			ClassFieldEnum ...$classFieldEnums,
	) {
		$this->classFieldEnums = $classFieldEnums;
	}

}
