<?php

namespace SqlToCodeGenerator\test\codeGeneration\attribute;

use PHPUnit\Framework\TestCase;
use SqlToCodeGenerator\codeGeneration\attribute\ClassField;
use SqlToCodeGenerator\codeGeneration\attribute\ClassFieldEnum;

class ClassFieldTest extends TestCase {

	public function testConstructor(): void {
		foreach (ClassFieldEnum::cases() as $classFieldEnum) {
			$this->assertSame(
					$classFieldEnum,
					(new ClassField($classFieldEnum))->classFieldEnum,
			);
		}
	}

}