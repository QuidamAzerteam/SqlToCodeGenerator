<?php

namespace SqlToCodeGenerator\test\common\bean;

use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\TestCase;
use SqlToCodeGenerator\codeGeneration\attribute\ClassField;
use SqlToCodeGenerator\codeGeneration\attribute\ClassFieldEnum;
use SqlToCodeGenerator\common\bean\CommonBean;

class CommonBeanTest extends TestCase {

	public function testCastAsChildClassSuccessfully(): void {
		$original = new PlaceholderClass();
		$result = $original->castAsChildClass(PlaceholderSubClass::class);
		$this->assertInstanceOf(PlaceholderClass::class, $result);
		$this->assertNotSame($original, $result);
	}

	public function testCastAsChildClassThrowsException(): void {
		$original = new PlaceholderSubClass();
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('Can\'t change class hierarchy, you must cast to a child class');

		$original->castAsChildClass(PlaceholderClass::class);
	}

	public function testToStringPrimary(): void {
		$classWithPrimary = new class extends CommonBean {
			#[ClassField(ClassFieldEnum::PRIMARY)]
			public int $primary = 1;
		};
		$this->assertSame('1', $classWithPrimary->__toString());
	}

	public function testToStringUnique(): void {
		$classWithUnique = new class extends CommonBean {
			#[ClassField(ClassFieldEnum::UNIQUE)]
			public int $unique = 2;
		};
		$this->assertSame('2', $classWithUnique->__toString());
	}

	public function testToStringPrimaryAndUnique(): void {
		$classWithPrimary = new class extends CommonBean {
			#[ClassField(ClassFieldEnum::PRIMARY)]
			public int $primary = 1;
			#[ClassField(ClassFieldEnum::UNIQUE)]
			public int $unique = 2;
		};
		$this->assertSame('1', $classWithPrimary->__toString());
	}

	public function testFailToString(): void {
		$classWithPrimary = new class extends CommonBean {
			public int $primary;
		};
		$this->expectException(LogicException::class);
		$this->expectExceptionMessage('No unique field, so no way to uniquely toString the object');
		$classWithPrimary->__toString();
	}

}
