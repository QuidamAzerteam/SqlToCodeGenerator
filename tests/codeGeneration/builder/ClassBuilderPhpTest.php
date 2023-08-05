<?php

namespace SqlToCodeGenerator\test\codeGeneration\builder;

use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;
use SqlToCodeGenerator\codeGeneration\builder\ClassBuilder;
use SqlToCodeGenerator\codeGeneration\builder\FieldBuilder;
use SqlToCodeGenerator\codeGeneration\builder\FunctionBuilder;

class ClassBuilderPhpTest extends TestCase {

	public function testMinimal(): void {
		$fileBuilder = ClassBuilder::create(
				basePackage: 'basePackage',
				namespace: 'namespace',
				name: 'name',
		);

		$expected = <<<PHP
			<?php

			namespace basePackage\\namespace;

			/**
			 * This code is generated. Do not edit it
			 */
			class name {

			}

			PHP;
		$this->assertSame(
				$expected,
				$fileBuilder->getPhpFileContent(),
		);
	}

	#[Depends('testMinimal')]
	public function testOneImport(): void {
		$fileBuilder = ClassBuilder::create(
				basePackage: 'basePackage',
				namespace: 'namespace',
				name: 'name',
		);

		$fileBuilder->addImports('test');
		$expected = <<<PHP
			<?php

			namespace basePackage\\namespace;

			use test;

			/**
			 * This code is generated. Do not edit it
			 */
			class name {

			}

			PHP;
		$this->assertSame(
				$expected,
				$fileBuilder->getPhpFileContent(),
		);
	}

	#[Depends('testMinimal')]
	public function testUniqueImport(): void {
		$fileBuilder = ClassBuilder::create(
				basePackage: 'basePackage',
				namespace: 'namespace',
				name: 'name',
		);

		$fileBuilder->addImports('test', 'test');
		$expected = <<<PHP
			<?php

			namespace basePackage\\namespace;

			use test;

			/**
			 * This code is generated. Do not edit it
			 */
			class name {

			}

			PHP;
		$this->assertSame(
				$expected,
				$fileBuilder->getPhpFileContent(),
		);
	}

	#[Depends('testMinimal')]
	public function testTwoImports(): void {
		$fileBuilder = ClassBuilder::create(
				basePackage: 'basePackage',
				namespace: 'namespace',
				name: 'name',
		);

		$fileBuilder->addImports('test', 'test2');
		$expected = <<<PHP
			<?php

			namespace basePackage\\namespace;
			
			use test;
			use test2;

			/**
			 * This code is generated. Do not edit it
			 */
			class name {

			}

			PHP;
		$this->assertSame(
				$expected,
				$fileBuilder->getPhpFileContent(),
		);
	}

	#[Depends('testMinimal')]
	public function testImportsOrders(): void {
		$fileBuilder = ClassBuilder::create(
				basePackage: 'basePackage',
				namespace: 'namespace',
				name: 'name',
		);

		$fileBuilder->addImports('aTest', 'cTest', 'bTest');
		$expected = <<<PHP
			<?php

			namespace basePackage\\namespace;

			use aTest;
			use bTest;
			use cTest;

			/**
			 * This code is generated. Do not edit it
			 */
			class name {

			}

			PHP;
		$this->assertSame(
				$expected,
				$fileBuilder->getPhpFileContent(),
		);
	}

	#[Depends('testMinimal')]
	public function testExtends(): void {
		$fileBuilder = ClassBuilder::create(
				basePackage: 'basePackage',
				namespace: 'namespace',
				name: 'name',
				extends: 'coucou',
		);

		$expected = <<<PHP
			<?php

			namespace basePackage\\namespace;

			/**
			 * This code is generated. Do not edit it
			 */
			class name extends coucou {

			}

			PHP;
		$this->assertSame(
				$expected,
				$fileBuilder->getPhpFileContent(),
		);
	}

	#[Depends('testMinimal')]
	public function testImplements(): void {
		$fileBuilder = ClassBuilder::create(
				basePackage: 'basePackage',
				namespace: 'namespace',
				name: 'name',
				implements: 'coucou',
		);

		$expected = <<<PHP
			<?php

			namespace basePackage\\namespace;

			/**
			 * This code is generated. Do not edit it
			 */
			class name implements coucou {

			}

			PHP;
		$this->assertSame(
				$expected,
				$fileBuilder->getPhpFileContent(),
		);
	}

	#[Depends('testMinimal')]
	public function testExtendsAndImplements(): void {
		$fileBuilder = ClassBuilder::create(
				basePackage: 'basePackage',
				namespace: 'namespace',
				name: 'name',
				extends: 'coucouExtends',
				implements: 'coucouImplements',
		);

		$expected = <<<PHP
			<?php

			namespace basePackage\\namespace;

			/**
			 * This code is generated. Do not edit it
			 */
			class name extends coucouExtends implements coucouImplements {

			}

			PHP;
		$this->assertSame(
				$expected,
				$fileBuilder->getPhpFileContent(),
		);
	}

	public function testNoFieldsPhpFileContent(): void {
		$classBuilder = ClassBuilder::create(
				basePackage: 'basePackage',
				namespace: 'namespace',
				name: 'name',
		);

		$this->assertEmpty($classBuilder->getFieldsPhpFileContent());
	}

	#[Depends('testNoFieldsPhpFileContent')]
	public function testFieldsPhpFileContent(): void {
		$fieldBuilder = $this->getMockBuilder(FieldBuilder::class)
				->disableOriginalConstructor()
				->getMock();
		$fieldBuilder->method('getPhpFileContent')->willReturn('test');

		$classBuilder = ClassBuilder::create(
				basePackage: 'basePackage',
				namespace: 'namespace',
				name: 'name',
		)->addFieldBuilders($fieldBuilder);

		$this->assertSame(
				"test\n",
				$classBuilder->getFieldsPhpFileContent(),
		);
	}

	#[Depends('testMinimal')]
	public function testPhpFieldBuilders(): void {
		$fieldBuilder = $this->getMockBuilder(FieldBuilder::class)
				->disableOriginalConstructor()
				->getMock();
		$fieldBuilder->method('getPhpFileContent')->willReturn('testPhpFieldBuilders');

		$classBuilder = ClassBuilder::create(
				basePackage: 'basePackage',
				namespace: 'namespace',
				name: 'name',
		)->addFieldBuilders($fieldBuilder);

		$expected = <<<PHP
			<?php

			namespace basePackage\\namespace;

			/**
			 * This code is generated. Do not edit it
			 */
			class name {

				testPhpFieldBuilders

			}

			PHP;
		$this->assertSame(
				$expected,
				$classBuilder->getPhpFileContent(),
		);
	}

	#[Depends('testMinimal')]
	public function testPhpFunctionBuilders(): void {
		$functionBuilder = $this->getMockBuilder(FunctionBuilder::class)
				->disableOriginalConstructor()
				->getMock();
		$functionBuilder->method('getPhpFileContent')->willReturn('testPhpFunctionBuilders');

		$classBuilder = ClassBuilder::create(
				basePackage: 'basePackage',
				namespace: 'namespace',
				name: 'name',
		)->addPhpFunctionBuilders($functionBuilder);

		$expected = <<<PHP
			<?php

			namespace basePackage\\namespace;

			/**
			 * This code is generated. Do not edit it
			 */
			class name {

			testPhpFunctionBuilders

			}

			PHP;
		$this->assertSame(
				$expected,
				$classBuilder->getPhpFileContent(),
		);
	}

	#[Depends('testMinimal')]
	#[Depends('testPhpFieldBuilders')]
	public function testPhpFunctionAndFieldBuilders(): void {
		$fieldBuilder = $this->getMockBuilder(FieldBuilder::class)
				->disableOriginalConstructor()
				->getMock();
		$fieldBuilder->method('getPhpFileContent')->willReturn('testPhpFieldBuilders');

		$functionBuilder = $this->getMockBuilder(FunctionBuilder::class)
				->disableOriginalConstructor()
				->getMock();
		$functionBuilder->method('getPhpFileContent')->willReturn('testPhpFunctionBuilders');

		$classBuilder = ClassBuilder::create(
				basePackage: 'basePackage',
				namespace: 'namespace',
				name: 'name',
		)->addFieldBuilders($fieldBuilder)->addPhpFunctionBuilders($functionBuilder);

		$expected = <<<PHP
			<?php

			namespace basePackage\\namespace;

			/**
			 * This code is generated. Do not edit it
			 */
			class name {

				testPhpFieldBuilders

			testPhpFunctionBuilders

			}

			PHP;
		$this->assertSame(
				$expected,
				$classBuilder->getPhpFileContent(),
		);
	}

	#[Depends('testMinimal')]
	public function testDocLine(): void {
		$fileBuilder = ClassBuilder::create(
				basePackage: 'basePackage',
				namespace: 'namespace',
				name: 'name',
				docLines: ['hello world'],
		);

		$expected = <<<PHP
			<?php

			namespace basePackage\\namespace;

			/**
			 * This code is generated. Do not edit it
			 * hello world
			 */
			class name {

			}

			PHP;
		$this->assertSame(
				$expected,
				$fileBuilder->getPhpFileContent(),
		);
	}

	#[Depends('testDocLine')]
	public function testDocLines(): void {
		$fileBuilder = ClassBuilder::create(
				basePackage: 'basePackage',
				namespace: 'namespace',
				name: 'name',
				docLines: ['hello'],
		)->addDocLines('world');

		$expected = <<<PHP
			<?php

			namespace basePackage\\namespace;

			/**
			 * This code is generated. Do not edit it
			 * hello
			 * world
			 */
			class name {

			}

			PHP;
		$this->assertSame(
				$expected,
				$fileBuilder->getPhpFileContent(),
		);
	}

}
