<?php

namespace SqlToCodeGenerator\test\codeGeneration\builder;

use LogicException;
use PHPUnit\Framework\TestCase;
use SqlToCodeGenerator\codeGeneration\builder\ClassBuilder;
use SqlToCodeGenerator\codeGeneration\builder\FieldBuilder;
use SqlToCodeGenerator\codeGeneration\builder\FunctionBuilder;
use stdClass;

class ClassBuilderTest extends TestCase {

	public function testBadClassFieldBuilderInConstruct(): void {
		$fieldBuilder = $this->createMock(stdClass::class);
		$this->expectException(LogicException::class);
		ClassBuilder::create(
				basePackage: 'basePackage',
				namespace: 'namespace',
				name: 'name',
				fieldBuilders: [$fieldBuilder],
		);
	}

	public function testBadTypeFieldBuilderInConstruct(): void {
		$this->expectException(LogicException::class);
		ClassBuilder::create(
				basePackage: 'basePackage',
				namespace: 'namespace',
				name: 'name',
				fieldBuilders: ['test'],
		);
	}

	public function testOkFieldBuilderInConstruct(): void {
		ClassBuilder::create(
				basePackage: 'basePackage',
				namespace: 'namespace',
				name: 'name',
				fieldBuilders: [$this->createMock(FieldBuilder::class)],
		);
		$this->assertTrue(true);
	}

	public function testFieldBuilders(): void {
		$fieldBuilder = $this->createMock(FieldBuilder::class);
		$classBuilder = ClassBuilder::create(
				basePackage: 'basePackage',
				namespace: 'namespace',
				name: 'name',
		);

		$this->assertSame(
				$classBuilder,
				$classBuilder->addFieldBuilders($fieldBuilder),
		);
	}

	public function testBadClassFunctionBuilderInConstruct(): void {
		$functionBuilder = $this->createMock(stdClass::class);
		$this->expectException(LogicException::class);
		ClassBuilder::create(
				basePackage: 'basePackage',
				namespace: 'namespace',
				name: 'name',
				functionBuilders: [$functionBuilder],
		);
	}

	public function testBadTypeFunctionBuilderInConstruct(): void {
		$this->expectException(LogicException::class);
		ClassBuilder::create(
				basePackage: 'basePackage',
				namespace: 'namespace',
				name: 'name',
				functionBuilders: ['test'],
		);
	}

	public function testOkFunctionBuilderInConstruct(): void {
		ClassBuilder::create(
				basePackage: 'basePackage',
				namespace: 'namespace',
				name: 'name',
				functionBuilders: [$this->createMock(FunctionBuilder::class)],
		);
		$this->assertTrue(true);
	}

	public function testFunctionBuilders(): void {
		$functionBuilder = $this->createMock(FunctionBuilder::class);
		$classBuilder = ClassBuilder::create(
				basePackage: 'basePackage',
				namespace: 'namespace',
				name: 'name',
		);

		$this->assertSame(
				$classBuilder,
				$classBuilder->addFunctionBuilders($functionBuilder),
		);
	}

}
