<?php

namespace SqlToCodeGenerator\test\codeGeneration\builder;

use LogicException;
use PHPUnit\Framework\TestCase;
use SqlToCodeGenerator\codeGeneration\builder\FunctionParameterBuilder;

class FunctionParameterBuilderPhpTest extends TestCase {

	public function testMinimal(): void {
		$functionParameterBuilder = FunctionParameterBuilder::create(
				type: 'type',
				name: 'name',
		);

		$expected = 'type $name';
		$this->assertSame(
				$expected,
				$functionParameterBuilder->getPhpFileContent()
		);
	}

	public function testBadType(): void {
		$this->expectException(LogicException::class);
		FunctionParameterBuilder::create(
				type: '123',
				name: 'name',
		);
	}

	public function testNoName(): void {
		$this->expectException(LogicException::class);
		FunctionParameterBuilder::create(
				type: 'type',
				name: '',
		);
	}

	public function testBadName(): void {
		$this->expectException(LogicException::class);
		FunctionParameterBuilder::create(
				type: 'type',
				name: '123',
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

		$expected = "{$functionParameterBuilder->getType()} \${$functionParameterBuilder->getName()} = hello world";
		$this->assertSame(
				$expected,
				$functionParameterBuilder->getPhpFileContent()
		);
	}

}
