<?php

namespace SqlToCodeGenerator\test\codeGeneration\builder;

use PHPUnit\Framework\TestCase;
use SqlToCodeGenerator\codeGeneration\bean\Line;
use SqlToCodeGenerator\codeGeneration\builder\FunctionBuilder;
use SqlToCodeGenerator\codeGeneration\enums\Visibility;

class FunctionBuilderJsTest extends TestCase {


	public function testMinimal(): void {
		$functionBuilder = FunctionBuilder::create(
				name: 'name',
				returnType: 'returnType',
		);

		$expected = "/**
 * @return {returnType}
 */
name() {
}";
		$this->assertSame(
				$expected,
				$functionBuilder->getJsFileContent('')
		);
	}

	/**
	 * @depends testMinimal
	 */
	public function testIndent(): void {
		$functionBuilder = FunctionBuilder::create(
				name: 'name',
				returnType: 'returnType',
		);

		$expectedOneIndent = "	/**
	 * @return {{$functionBuilder->getReturnType()}}
	 */
	{$functionBuilder->getName()}() {
	}";
		$this->assertSame(
				$expectedOneIndent,
				$functionBuilder->getJsFileContent("\t")
		);

		$expectedTwoIndents = "		/**
		 * @return {{$functionBuilder->getReturnType()}}
		 */
		{$functionBuilder->getName()}() {
		}";
		$this->assertSame(
				$expectedTwoIndents,
				$functionBuilder->getJsFileContent("\t\t")
		);
	}

	/**
	 * @depends testMinimal
	 */
	public function testDocumentationLines(): void {
		$functionBuilder = FunctionBuilder::create(
				name: 'name',
				returnType: 'returnType',
				documentationLines: array(
					'test',
				),
		);

		$expected = "/**
 * test
 * @return {{$functionBuilder->getReturnType()}}
 */
{$functionBuilder->getName()}() {
}";
		$this->assertSame(
				$expected,
				$functionBuilder->getJsFileContent('')
		);
	}

	/**
	 * @depends testMinimal
	 */
	public function testIsStatic(): void {
		$functionBuilder = FunctionBuilder::create(
				name: 'name',
				returnType: 'returnType',
				isStatic: true,
		);

		$expected = "/**
 * @return {{$functionBuilder->getReturnType()}}
 */
static {$functionBuilder->getName()}() {
}";
		$this->assertSame(
				$expected,
				$functionBuilder->getJsFileContent('')
		);
	}

	/**
	 * @depends testMinimal
	 */
	public function testLines(): void {
		$functionBuilder = FunctionBuilder::create(
				name: 'name',
				returnType: 'returnType',
				lines: array(
					Line::create('test'),
					Line::create('test2', 1),
				),
		);

		$expected = "/**
 * @return {{$functionBuilder->getReturnType()}}
 */
{$functionBuilder->getName()}() {
	test
		test2
}";
		$this->assertSame(
				$expected,
				$functionBuilder->getJsFileContent('')
		);
	}

}
