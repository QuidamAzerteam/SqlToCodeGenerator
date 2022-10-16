<?php

namespace SqlToCodeGenerator\test\codeGeneration\builder;

use PHPUnit\Framework\TestCase;
use SqlToCodeGenerator\codeGeneration\builder\FunctionParameterBuilder;

class FunctionParameterBuilderJsTest extends TestCase {

	public function testMinimal(): void {
		$functionParameterBuilder = FunctionParameterBuilder::create(
				type: 'type',
				name: 'name',
		);

		$expectedDoc = ' * @param {type} name';
		$this->assertSame(
				$expectedDoc,
				$functionParameterBuilder->getJsDocFileContent('')
		);

		$expectedVar = 'name';
		$this->assertSame(
				$expectedVar,
				$functionParameterBuilder->getJsParamFileContent('')
		);
	}

	/**
	 * @depends testMinimal
	 */
	public function testDefaultValue(): void {
		$functionParameterBuilder = FunctionParameterBuilder::create(
				type: 'type',
				name: 'name',
				defaultValue: 'hello world',
		);

		$expectedDoc = ' * @param {type} [name=hello world]';
		$this->assertSame(
				$expectedDoc,
				$functionParameterBuilder->getJsDocFileContent('')
		);

		$expectedVar = 'name = hello world';
		$this->assertSame(
				$expectedVar,
				$functionParameterBuilder->getJsParamFileContent('')
		);
	}

}
