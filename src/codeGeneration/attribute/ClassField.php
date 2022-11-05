<?php

namespace SqlToCodeGenerator\codeGeneration\attribute;

use Attribute;

#[Attribute] class ClassField {

	public function __construct(
			public readonly ClassFieldEnum $classFieldEnum,
	) {}

}
