<?php

namespace SqlToCodeGenerator\test\codeGeneration\metadata;

use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;
use SqlToCodeGenerator\codeGeneration\metadata\Enum;

class EnumTest extends TestCase {

	public function testGetFullName(): void {
		$enum = new Enum();
		$enum->basePackage = 'basePackage';
		$enum->namespace = 'namespace';
		$enum->name = 'name';

		$this->assertSame(
				"\\basePackage\\namespace\\name",
				$enum->getFullName(),
		);
	}

	public function testPhpFileContent(): void {
		$enum = new Enum();
		$enum->basePackage = 'basePackage';
		$enum->namespace = 'namespace';
		$enum->name = 'name';

		$this->assertStringContainsString(
				'final public function getShortText',
				$enum->getPhpFileContent(),
		);
	}

	public function testJsFileContent(): void {
		$enum = new Enum();
		$enum->basePackage = 'basePackage';
		$enum->namespace = 'namespace';
		$enum->name = 'name';

		$this->assertNotEmpty($enum->getJsFileContent());
	}

	#[Depends('testPhpFileContent')]
	public function testPhpFileContentValues(): void {
		$enum = new Enum();
		$enum->basePackage = 'basePackage';
		$enum->namespace = 'namespace';
		$enum->name = 'name';
		$enum->values[] = 'value';

		$this->assertStringContainsString(
				"self::value => 'Value',",
				$enum->getPhpFileContent(),
		);
	}

	public function testPhpTestFileContent(): void {
		$enum = new Enum();
		$enum->basePackage = 'basePackage';
		$enum->namespace = 'namespace';
		$enum->name = 'name';

		$phpTestFileContent = $enum->getPhpTestFileContent('hello');
		$this->assertStringContainsString(
				'namespace basePackage\\hello\\namespace;',
				$phpTestFileContent,
		);
		$this->assertStringContainsString(
				'use ' . TestCase::class,
				$phpTestFileContent,
		);
		$this->assertStringContainsString(
				"use basePackage\\namespace\\name",
				$phpTestFileContent,
		);
		$this->assertStringContainsString(
				'class NameTest extends TestCase',
				$phpTestFileContent,
		);
	}

	#[Depends('testPhpTestFileContent')]
	public function testPhpTestFileContentValues(): void {
		$enum = new Enum();
		$enum->basePackage = 'basePackage';
		$enum->namespace = 'namespace';
		$enum->name = 'name';
		$enum->values[] = 'value';

		$phpTestFileContent = $enum->getPhpTestFileContent('hello');
		$this->assertStringContainsString(
				'testValue',
				$phpTestFileContent,
		);
	}

}
