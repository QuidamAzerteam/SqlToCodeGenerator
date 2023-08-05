<?php

namespace SqlToCodeGenerator\test\codeGeneration\builder;

use LogicException;
use PHPUnit\Framework\TestCase;

class FileBuilderTest extends TestCase {

	public function testEmptyNamespace(): void {
		$this->expectException(LogicException::class);
		ForTestFileBuilder::create(
				basePackage: '',
				namespace: '',
				name: 'name',
		);
	}

	public function testBadNamespace(): void {
		$this->expectException(LogicException::class);
		ForTestFileBuilder::create(
				basePackage: '\\',
				namespace: '\\',
				name: 'name',
		);
	}

	public function testNumericNamespace(): void {
		ForTestFileBuilder::create(
				basePackage: '1',
				namespace: '2',
				name: 'name',
		);
		$this->assertTrue(true);
	}

	public function testBadExtends(): void {
		$this->expectException(LogicException::class);
		ForTestFileBuilder::create(
				basePackage: 'basePackage',
				namespace: 'namespace',
				name: 'name',
				extends: 'hello world',
		);
	}

	public function testBadImplements(): void {
		$this->expectException(LogicException::class);
		ForTestFileBuilder::create(
				basePackage: 'basePackage',
				namespace: 'namespace',
				name: 'name',
				implements: 'hello world',
		);
	}

}
