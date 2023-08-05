<?php

namespace SqlToCodeGenerator\test\codeGeneration\builder;

use PHPUnit\Framework\TestCase;
use SqlToCodeGenerator\codeGeneration\builder\EnumBuilder;

class EnumBuilderJsTest extends TestCase {

	public function testNoFields(): void {
		$fileBuilder = EnumBuilder::create(
				basePackage: 'basePackage',
				namespace: 'namespace',
				name: 'name',
		);
		$this->assertSame(
				'',
				$fileBuilder->getFieldsJsFileContent(),
		);
	}

	public function testField(): void {
		$fileBuilder = EnumBuilder::create(
				basePackage: 'basePackage',
				namespace: 'namespace',
				name: 'name',
				fields: ['test'],
		);

		$expected = <<<JS
			static get test() {
				return 1;
			}
		JS;
		$this->assertSame(
				$expected,
				$fileBuilder->getFieldsJsFileContent(),
		);
	}

	public function testFields(): void {
		$fileBuilder = EnumBuilder::create(
				basePackage: 'basePackage',
				namespace: 'namespace',
				name: 'name',
				fields: ['test', 'test2'],
		);

		$expected = <<<JS
			static get test() {
				return 1;
			}
			static get test2() {
				return 2;
			}
		JS;
		$this->assertSame(
				$expected,
				$fileBuilder->getFieldsJsFileContent(),
		);
	}

}
