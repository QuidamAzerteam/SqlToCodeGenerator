<?php

namespace SqlToCodeGenerator\test\codeGeneration\builder;

use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;
use SqlToCodeGenerator\codeGeneration\builder\ClassBuilder;
use SqlToCodeGenerator\codeGeneration\builder\FieldBuilder;

class ClassBuilderJsTest extends TestCase {

	public function testMinimal(): void {
		$fileBuilder = ClassBuilder::create(
				basePackage: 'basePackage',
				namespace: 'namespace',
				name: 'name',
		);

		$expected = <<<JS
			export class name {
			
			}
			
			JS;
		$this->assertSame(
				$expected,
				$fileBuilder->getJsFileContent(),
		);
	}

	#[Depends('testMinimal')]
	public function testWithFieldsJsFileContent(): void {
		$fileBuilder = ClassBuilder::create(
				basePackage: 'basePackage',
				namespace: 'namespace',
				name: 'name',
		)->addFieldBuilders(new FieldBuilder(fieldName: 'test', jsType: 'test'));

		$expected = <<<JS
			export class name {
			
				/** @type {test} */
				test
			
			}
			
			JS;
		$this->assertSame(
				$expected,
				$fileBuilder->getJsFileContent(),
		);
	}

	public function testGetFieldsJsFileContent(): void {
		$fieldBuilder = $this->getMockBuilder(FieldBuilder::class)
				->disableOriginalConstructor()
				->getMock();
		$fieldBuilder->method('getJsFileContent')->willReturn('test');

		$classBuilder = ClassBuilder::create(
				basePackage: 'basePackage',
				namespace: 'namespace',
				name: 'name',
		)->addFieldBuilders($fieldBuilder);

		$this->assertSame(
				"test",
				$classBuilder->getFieldsJsFileContent(),
		);
	}

}
