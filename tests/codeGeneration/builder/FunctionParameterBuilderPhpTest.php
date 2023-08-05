<?php

namespace SqlToCodeGenerator\test\codeGeneration\builder;

use LogicException;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;
use SqlToCodeGenerator\codeGeneration\builder\FunctionParameterBuilder;

class FunctionParameterBuilderPhpTest extends TestCase {

	public function testMinimal(): void {
		$functionParameterBuilder = FunctionParameterBuilder::create(
				type: 'type',
				name: 'name',
		);

		$this->assertSame(
				'type $name',
				$functionParameterBuilder->getPhpFileContent(),
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

	#[Depends('testMinimal')]
	public function testDefaultValue(): void {
		$functionParameterBuilder = FunctionParameterBuilder::create(
				type: 'type',
				name: 'name',
				defaultValue: 'hello world',
		);

		$this->assertSame(
				"type \$name = hello world",
				$functionParameterBuilder->getPhpFileContent(),
		);
	}

}
