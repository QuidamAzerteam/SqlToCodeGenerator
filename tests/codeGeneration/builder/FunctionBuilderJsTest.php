<?php

namespace SqlToCodeGenerator\test\codeGeneration\builder;

use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;
use SqlToCodeGenerator\codeGeneration\bean\Line;
use SqlToCodeGenerator\codeGeneration\builder\FunctionBuilder;

class FunctionBuilderJsTest extends TestCase {


	public function testMinimal(): void {
		$functionBuilder = FunctionBuilder::create(
				name: 'name',
				returnType: 'returnType',
		);

		$expected = <<<JS
			/**
			 * @return {returnType}
			 */
			name() {}
			JS;
		$this->assertSame(
				$expected,
				$functionBuilder->getJsFileContent(''),
		);
	}

	#[Depends('testMinimal')]
	public function testIndent(): void {
		$functionBuilder = FunctionBuilder::create(
				name: 'name',
				returnType: 'returnType',
		);

		$expectedOneIndent = <<<JS
				/**
				 * @return {returnType}
				 */
				name() {}
			JS;
		$this->assertSame(
				$expectedOneIndent,
				$functionBuilder->getJsFileContent("\t"),
		);

		$expectedTwoIndents = <<<JS
					/**
					 * @return {returnType}
					 */
					name() {}
			JS;
		$this->assertSame(
				$expectedTwoIndents,
				$functionBuilder->getJsFileContent("\t\t"),
		);
	}

	#[Depends('testMinimal')]
	public function testDocumentationLines(): void {
		$functionBuilder = FunctionBuilder::create(
				name: 'name',
				returnType: 'returnType',
				documentationLines: ['test'],
		);

		$expected = <<<JS
			/**
			 * test
			 * @return {returnType}
			 */
			name() {}
			JS;
		$this->assertSame(
				$expected,
				$functionBuilder->getJsFileContent(''),
		);
	}

	#[Depends('testMinimal')]
	public function testIsStatic(): void {
		$functionBuilder = FunctionBuilder::create(
				name: 'name',
				returnType: 'returnType',
				isStatic: true,
		);

		$expected = <<<JS
			/**
			 * @return {returnType}
			 */
			static name() {}
			JS;
		$this->assertSame(
				$expected,
				$functionBuilder->getJsFileContent(''),
		);
	}

	#[Depends('testMinimal')]
	public function testLines(): void {
		$functionBuilder = FunctionBuilder::create(
				name: 'name',
				returnType: 'returnType',
				lines: [
					Line::create('test'),
					Line::create('test2', 1),
				],
		);

		$expected = <<<JS
		/**
		 * @return {returnType}
		 */
		name() {
			test
				test2
		}
		JS;
		$this->assertSame(
				$expected,
				$functionBuilder->getJsFileContent(''),
		);
	}

}
