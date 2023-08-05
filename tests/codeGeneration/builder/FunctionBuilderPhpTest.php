<?php

namespace SqlToCodeGenerator\test\codeGeneration\builder;

use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;
use SqlToCodeGenerator\codeGeneration\bean\Line;
use SqlToCodeGenerator\codeGeneration\builder\FunctionBuilder;
use SqlToCodeGenerator\codeGeneration\enums\Visibility;

class FunctionBuilderPhpTest extends TestCase {

	public function testMinimal(): void {
		$functionBuilder = FunctionBuilder::create(
				name: 'name',
				returnType: 'returnType',
		);

		$expected = <<<PHP
			public function name(): returnType {}
			PHP;
		$this->assertSame(
				$expected,
				$functionBuilder->getPhpFileContent(''),
		);
	}

	#[Depends('testMinimal')]
	public function testIndent(): void {
		$functionBuilder = FunctionBuilder::create(
				name: 'name',
				returnType: 'returnType',
		);

		$expectedOneIndent = <<<PHP
				public function name(): returnType {}
			PHP;
		$this->assertSame(
				$expectedOneIndent,
				$functionBuilder->getPhpFileContent("\t"),
		);

		$expectedTwoIndents = <<<PHP
					public function name(): returnType {}
			PHP;
		$this->assertSame(
				$expectedTwoIndents,
				$functionBuilder->getPhpFileContent("\t\t"),
		);
	}

	#[Depends('testMinimal')]
	public function testDocumentationLines(): void {
		$functionBuilder = FunctionBuilder::create(
				name: 'name',
				returnType: 'returnType',
				documentationLines: ['test'],
		);

		$expected = <<<PHP
			/**
			 * test
			 */
			public function name(): returnType {}
			PHP;
		$this->assertSame(
				$expected,
				$functionBuilder->getPhpFileContent(''),
		);
	}

	#[Depends('testMinimal')]
	public function testIsFinal(): void {
		$functionBuilder = FunctionBuilder::create(
				name: 'name',
				returnType: 'returnType',
				isFinal: true,
		);

		$expected = <<<PHP
			final public function name(): returnType {}
			PHP;
		$this->assertSame(
				$expected,
				$functionBuilder->getPhpFileContent(''),
		);
	}

	#[Depends('testMinimal')]
	public function testIsStatic(): void {
		$functionBuilder = FunctionBuilder::create(
				name: 'name',
				returnType: 'returnType',
				isStatic: true,
		);

		$expected = <<<PHP
			public static function name(): returnType {}
			PHP;
		$this->assertSame(
				$expected,
				$functionBuilder->getPhpFileContent(''),
		);
	}

	#[Depends('testMinimal')]
	public function testIsStaticAndIsFinal(): void {
		$functionBuilder = FunctionBuilder::create(
				name: 'name',
				returnType: 'returnType',
				isStatic: true,
				isFinal: true,
		);

		$expected = <<<PHP
			final public static function name(): returnType {}
			PHP;
		$this->assertSame(
				$expected,
				$functionBuilder->getPhpFileContent(''),
		);
	}

	#[Depends('testMinimal')]
	public function testVisibilityDefault(): void {
		$functionBuilder = FunctionBuilder::create(
				name: 'name',
				returnType: 'returnType',
		);

		$expected = <<<PHP
			public function name(): returnType {}
			PHP;
		$this->assertSame(
				$expected,
				$functionBuilder->getPhpFileContent(''),
		);
	}

	#[Depends('testMinimal')]
	public function testVisibilityPrivate(): void {
		$functionBuilder = FunctionBuilder::create(
				name: 'name',
				returnType: 'returnType',
				visibility: Visibility::PRIVATE,
		);

		$expectedPrivate = <<<PHP
			private function name(): returnType {}
			PHP;
		$this->assertSame(
				$expectedPrivate,
				$functionBuilder->getPhpFileContent(''),
		);
	}

	#[Depends('testMinimal')]
	public function testVisibilityProtected(): void {
		$functionBuilder = FunctionBuilder::create(
				name: 'name',
				returnType: 'returnType',
				visibility: Visibility::PROTECTED,
		);

		$expectedProtected = <<<PHP
			protected function name(): returnType {}
			PHP;
		$this->assertSame(
				$expectedProtected,
				$functionBuilder->getPhpFileContent(''),
		);
	}

	#[Depends('testMinimal')]
	public function testVisibility(): void {
		$functionBuilder = FunctionBuilder::create(
				name: 'name',
				returnType: 'returnType',
				visibility: Visibility::PUBLIC,
		);

		$expectedDefault = <<<PHP
			public function name(): returnType {}
			PHP;
		$this->assertSame(
				$expectedDefault,
				$functionBuilder->getPhpFileContent(''),
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

		$expected = <<<PHP
			public function name(): returnType {
				test
					test2
			}
			PHP;
		$this->assertSame(
				$expected,
				$functionBuilder->getPhpFileContent(''),
		);
	}

}
