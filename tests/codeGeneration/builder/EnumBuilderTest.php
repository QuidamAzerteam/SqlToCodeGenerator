<?php

namespace SqlToCodeGenerator\test\codeGeneration\builder;

use LogicException;
use PHPUnit\Framework\TestCase;
use SqlToCodeGenerator\codeGeneration\builder\EnumBuilder;

class EnumBuilderTest extends TestCase {

	public function testConstructor(): void {
		EnumBuilder::create(
				basePackage: 'basePackage',
				namespace: 'namespace',
				name: 'name',
				fields: ['string'],
		);
		$this->assertTrue(true);
	}

	public function testBadFields(): void {
		$this->expectException(LogicException::class);
		EnumBuilder::create(
				basePackage: 'basePackage',
				namespace: 'namespace',
				name: 'name',
				fields: ['123'],
		);
	}

	public function testDuplicateFieldsError(): void {
		$this->expectException(LogicException::class);
		EnumBuilder::create(
				basePackage: 'basePackage',
				namespace: 'namespace',
				name: 'name',
				fields: ['test', 'test'],
		);
	}

	public function testNullField(): void {
		$this->expectException(LogicException::class);
		EnumBuilder::create(
				basePackage: 'basePackage',
				namespace: 'namespace',
				name: 'name',
				fields: [null],
		);
	}

	public function testEmptyField(): void {
		$this->expectException(LogicException::class);
		EnumBuilder::create(
				basePackage: 'basePackage',
				namespace: 'namespace',
				name: 'name',
				fields: [''],
		);
	}
}