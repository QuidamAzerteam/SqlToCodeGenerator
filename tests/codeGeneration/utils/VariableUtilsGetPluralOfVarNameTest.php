<?php

namespace SqlToCodeGenerator\test\codeGeneration\utils;

use PHPUnit\Framework\TestCase;
use SqlToCodeGenerator\codeGeneration\utils\VariableUtils;

class VariableUtilsGetPluralOfVarNameTest extends TestCase {

	public function testDefault(): void {
		$this->assertSame('things', VariableUtils::getPluralOfVarName('thing'));
	}

	public function testVowels(): void {
		$this->assertSame('orchestras', VariableUtils::getPluralOfVarName('orchestra'));
		$this->assertSame('projectiles', VariableUtils::getPluralOfVarName('projectile'));
		$this->assertSame('confettis', VariableUtils::getPluralOfVarName('confetti'));
		$this->assertSame('zoos', VariableUtils::getPluralOfVarName('zoo'));
		$this->assertSame('vertus', VariableUtils::getPluralOfVarName('vertu'));
	}

	public function testEndingWithY(): void {
		$this->assertSame('properties', VariableUtils::getPluralOfVarName('property'));
	}

	public function testEndingWithYButWithVowelBefore(): void {
		$this->assertSame('arrays', VariableUtils::getPluralOfVarName('array'));
	}

	public function testList(): void {
		// Yes, it should be "asses" but the algorithm does not handle it yet ^^'
		$this->assertSame('assList', VariableUtils::getPluralOfVarName('ass'));
	}

	public function testExceptions(): void {
		$this->assertSame('dataList', VariableUtils::getPluralOfVarName('data'));
		$this->assertSame('informationList', VariableUtils::getPluralOfVarName('information'));
		$this->assertSame('scenarii', VariableUtils::getPluralOfVarName('scenario'));
	}

	public function testStringToEnumCompliantValue(): void {
		$this->assertSame('1', VariableUtils::stringToEnumCompliantValue('1'));
		$this->assertSame('AA', VariableUtils::stringToEnumCompliantValue('aa'));
		$this->assertSame('VALUE_A', VariableUtils::stringToEnumCompliantValue('Value A'));
		$this->assertSame('VALUE_B', VariableUtils::stringToEnumCompliantValue("Value\n\tB"));
	}

}
