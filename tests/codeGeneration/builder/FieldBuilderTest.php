<?php

namespace SqlToCodeGenerator\test\codeGeneration\builder;

use PHPUnit\Framework\TestCase;
use SqlToCodeGenerator\codeGeneration\attribute\ClassFieldEnum;
use SqlToCodeGenerator\codeGeneration\builder\FieldBuilder;

class FieldBuilderTest extends TestCase {

	public function testSetPhpTypeChain(): void {
		$fieldBuilder = FieldBuilder::create('test');
		$this->assertSame($fieldBuilder, $fieldBuilder->setPhpType('test'));
	}

	public function testSetJsTypeChain(): void {
		$fieldBuilder = FieldBuilder::create('test');
		$this->assertSame($fieldBuilder, $fieldBuilder->setJsType('test'));
	}

	public function testSetDefaultValueChain(): void {
		$fieldBuilder = FieldBuilder::create('test');
		$this->assertSame($fieldBuilder, $fieldBuilder->setDefaultValue(null));
	}

	public function testSetIsNullableChain(): void {
		$fieldBuilder = FieldBuilder::create('test');
		$this->assertSame($fieldBuilder, $fieldBuilder->setIsNullable(true));
	}

	public function testSetIsConstChain(): void {
		$fieldBuilder = FieldBuilder::create('test');
		$this->assertSame($fieldBuilder, $fieldBuilder->setIsConst(true));
	}

	public function testSetCustomTypeHintChain(): void {
		$fieldBuilder = FieldBuilder::create('test');
		$this->assertSame($fieldBuilder, $fieldBuilder->setCustomTypeHint('test'));
	}

	public function testSetCommentsChain(): void {
		$fieldBuilder = FieldBuilder::create('test');
		$this->assertSame($fieldBuilder, $fieldBuilder->setComments(['test']));
	}

	public function testAddCommentsChain(): void {
		$fieldBuilder = FieldBuilder::create('test');
		$this->assertSame($fieldBuilder, $fieldBuilder->addComments('test'));
	}

	public function testSetClassFieldEnumChain(): void {
		$fieldBuilder = FieldBuilder::create('test');
		$this->assertSame($fieldBuilder, $fieldBuilder->addClassFieldEnums(ClassFieldEnum::PRIMARY));
	}

	public function testIsNullable(): void {
		$fieldBuilderNullable = FieldBuilder::create(fieldName: 'test', isNullable: true);
		$fieldBuilderNotNullable = FieldBuilder::create(fieldName: 'test', isNullable: false);

		$this->assertTrue($fieldBuilderNullable->isNullable());
		$this->assertFalse($fieldBuilderNotNullable->isNullable());
	}

	public function testGetFieldName(): void {
		$fieldBuilder = FieldBuilder::create('test');
		$this->assertSame('test', $fieldBuilder->getFieldName());
	}

	public function testSetFieldNameChain(): void {
		$fieldBuilder = FieldBuilder::create('test');
		$this->assertSame($fieldBuilder, $fieldBuilder->setFieldName('test2'));
		$this->assertSame('test2', $fieldBuilder->getFieldName());
	}

}
