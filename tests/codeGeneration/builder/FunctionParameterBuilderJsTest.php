<?php

namespace SqlToCodeGenerator\test\codeGeneration\builder;

use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;
use SqlToCodeGenerator\codeGeneration\builder\FunctionParameterBuilder;

class FunctionParameterBuilderJsTest extends TestCase {

	public function testMinimal(): void {
		$functionParameterBuilder = FunctionParameterBuilder::create(
				type: 'type',
				name: 'name',
		);

		$this->assertSame(
				' * @param {type} name',
				$functionParameterBuilder->getJsDocFileContent(),
		);

		$this->assertSame(
				'name',
				$functionParameterBuilder->getJsParamFileContent(),
		);
	}

	#[Depends('testMinimal')]
	public function testDefaultValue(): void {
		$functionParameterBuilder = FunctionParameterBuilder::create(
				type: 'type',
				name: 'name',
				defaultValue: 'hello world',
		);

		$this->assertSame(
				' * @param {type} [name=hello world]',
				$functionParameterBuilder->getJsDocFileContent(),
		);

		$this->assertSame(
				'name = hello world',
				$functionParameterBuilder->getJsParamFileContent(),
		);
	}

}
