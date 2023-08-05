<?php

namespace SqlToCodeGenerator\test\codeGeneration\builder;

use LogicException;
use PHPUnit\Framework\TestCase;
use SqlToCodeGenerator\codeGeneration\builder\ClassBuilder;
use SqlToCodeGenerator\codeGeneration\builder\FieldBuilder;
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

}
