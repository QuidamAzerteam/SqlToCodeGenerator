<?php

namespace SqlToCodeGenerator\test\codeGeneration\builder;

use LogicException;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;
use SqlToCodeGenerator\codeGeneration\attribute\ClassFieldEnum;
use SqlToCodeGenerator\codeGeneration\builder\FieldBuilder;
use SqlToCodeGenerator\codeGeneration\enums\Visibility;

class FieldBuilderPhpTest extends TestCase {

	#[Depends('testBadFieldNameEmpty')]
	#[Depends('testBadFieldNameBadPhpVar')]
	#[Depends('testBadPhpTypeClass')]
	#[Depends('testBadPhpTypeDollar')]
	#[Depends('testBadPhpType123')]
	public function testFieldNameAndPhpType(): void {
		$fieldBuilder = FieldBuilder::create(
				fieldName: 'test',
				phpType: 'hello',
		);

		$this->assertSame(
				Visibility::PUBLIC->value . ' hello $test;',
				$fieldBuilder->getPhpFileContent(),
		);
	}

	public function testBadFieldNameEmpty(): void {
		$this->expectException(LogicException::class);
		FieldBuilder::create('');
	}

	public function testBadFieldNameBadPhpVar(): void {
		$this->expectException(LogicException::class);
		FieldBuilder::create('123');
	}

	public function testBadPhpTypeClass(): void {
		$this->expectException(LogicException::class);
		FieldBuilder::create('test')->setPhpType('class');
	}

	public function testBadPhpTypeDollar(): void {
		$this->expectException(LogicException::class);
		FieldBuilder::create('test')->setPhpType('$hello');
	}

	public function testBadPhpType123(): void {
		$this->expectException(LogicException::class);
		FieldBuilder::create('test')->setPhpType('123');
	}

	#[Depends('testFieldNameAndPhpType')]
	public function testPrependLinesBy(): void {
		$fieldBuilder = FieldBuilder::create(
				fieldName: 'test',
				phpType: 'hello',
		);

		$this->assertSame(
				"\tpublic hello \$test;",
				$fieldBuilder->getPhpFileContent("\t"),
		);
	}

	#[Depends('testFieldNameAndPhpType')]
	public function testVisibility(): void {
		foreach (Visibility::cases() as $visibility) {
			$fieldBuilder = FieldBuilder::create(
					fieldName: 'test',
					phpType: 'hello',
					visibility: $visibility,
			);

			$this->assertSame(
					$visibility->value . ' hello $test;',
					$fieldBuilder->getPhpFileContent(),
			);
		}
	}

	#[Depends('testFieldNameAndPhpType')]
	public function testVisibilityDefault(): void {
		$fieldBuilder = FieldBuilder::create(
				fieldName: 'test',
				phpType: 'hello',
		);

		$this->assertSame(
				'public hello $test;',
				$fieldBuilder->getPhpFileContent(),
		);
	}

	#[Depends('testFieldNameAndPhpType')]
	public function testDefaultValue(): void {
		$fieldBuilder = FieldBuilder::create(
				fieldName: 'test',
				phpType: 'hello',
				defaultValue: 'hello',
		);

		$this->assertSame(
				'public hello $test = hello;',
				$fieldBuilder->getPhpFileContent(),
		);
	}

	#[Depends('testFieldNameAndPhpType')]
	public function testDefaultValueEmpty(): void {
		$fieldBuilder = FieldBuilder::create(
				fieldName: 'test',
				phpType: 'hello',
				defaultValue: '',
		);

		$this->assertSame(
				'public hello $test;',
				$fieldBuilder->getPhpFileContent(),
		);
	}

	#[Depends('testFieldNameAndPhpType')]
	#[Depends('testBadIsNullable')]
	public function testIsNullable(): void {
		$fieldBuilder = FieldBuilder::create(
				fieldName: 'test',
				phpType: 'hello',
				isNullable: true,
		);

		$this->assertSame(
				'public hello|null $test;',
				$fieldBuilder->getPhpFileContent(),
		);
	}

	public function testBadIsNullable(): void {
		$this->expectException(LogicException::class);
		FieldBuilder::create(
				fieldName: 'test',
				isNullable: true,
		)->getPhpFileContent();
	}

	#[Depends('testFieldNameAndPhpType')]
	#[Depends('testIsConstNullable')]
	#[Depends('testIsConstNoDefaultValue')]
	public function testIsConst(): void {
		$fieldBuilder = FieldBuilder::create('test')
				->setIsConst(true)
				->setDefaultValue('hello');

		$expected = 'final ' . 'public'
				. ' const test = hello;';
		$this->assertSame(
				$expected,
				$fieldBuilder->getPhpFileContent(),
		);
	}

	public function testIsConstNullable(): void {
		$this->expectException(LogicException::class);
		FieldBuilder::create('test')
				->setIsConst(true)
				->setIsNullable(true)
				->getPhpFileContent();
	}

	public function testIsConstNoDefaultValue(): void {
		$this->expectException(LogicException::class);
		FieldBuilder::create('test')
				->setIsConst(true)
				->setDefaultValue('')
				->getPhpFileContent();
	}

	#[Depends('testFieldNameAndPhpType')]
	public function testCustomTypeHint(): void {
		$fieldBuilder = FieldBuilder::create('test')
				->setPhpType('hello')
				->setCustomTypeHint('hi');

		$fileContentLines = explode("\n", $fieldBuilder->getPhpFileContent());
		$this->assertCount(2, $fileContentLines);
		$this->assertSame(
				'/** @type hi */',
				$fileContentLines[0],
		);
	}

	#[Depends('testFieldNameAndPhpType')]
	public function testClassFieldEnum(): void {
		foreach (ClassFieldEnum::cases() as $classFieldEnum) {
			$fieldBuilder = FieldBuilder::create('test')
					->setPhpType('hello')
					->setClassFieldEnum($classFieldEnum);

			$fileContentLines = explode("\n", $fieldBuilder->getPhpFileContent());
			$this->assertCount(2, $fileContentLines);
			$this->assertSame(
					'#[ClassField(ClassFieldEnum::' . $classFieldEnum->name . ')]',
					$fileContentLines[0],
			);
		}
	}

	#[Depends('testFieldNameAndPhpType')]
	public function testComments(): void {
		$fieldBuilder = FieldBuilder::create('test')
				->setPhpType('hello');

		$fieldBuilder->addComments('one comment');
		$expectedOneComment = 'public'
				. ' hello'
				. ' $test; // one comment';
		$this->assertSame(
				$expectedOneComment,
				$fieldBuilder->getPhpFileContent(),
		);

		$fieldBuilder->addComments('2nd comment');
		$expectedTwoComments = 'public'
				. ' hello'
				. ' $test; // one comment. 2nd comment';
		$this->assertSame(
				$expectedTwoComments,
				$fieldBuilder->getPhpFileContent(),
		);

		$fieldBuilder->setComments([]);
		$expectedNoComments = 'public'
				. ' hello'
				. ' $test;';
		$this->assertSame(
				$expectedNoComments,
				$fieldBuilder->getPhpFileContent(),
		);
	}

}
