<?php

namespace SqlToCodeGenerator\test\codeGeneration\utils;

use PHPUnit\Framework\TestCase;
use SqlToCodeGenerator\codeGeneration\utils\VariableUtils;

class VariableUtilsGetPluralOfVarNameTest extends TestCase {

	public function testDefault(): void {
		$this->assertSame('things', VariableUtils::getPluralOfVarName('thing'));
	}

	public function testEndingWithY(): void {
		$this->assertSame('properties', VariableUtils::getPluralOfVarName('property'));
	}

	public function testEndingWithYButWithVowelBefore(): void {
		$this->assertSame('arrays', VariableUtils::getPluralOfVarName('array'));
	}

}
