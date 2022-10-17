<?php

namespace SqlToCodeGenerator\test\codeGeneration\builder;

use LogicException;
use PHPUnit\Framework\TestCase;
use SqlToCodeGenerator\codeGeneration\builder\FieldBuilder;
use SqlToCodeGenerator\codeGeneration\enums\Visibility;

class FieldBuilderPhpTest extends TestCase {

	/**
	 * @depends testBadFieldNameEmpty
	 * @depends testBadFieldNameBadPhpVar
	 * @depends testBadPhpTypeClass
	 * @depends testBadPhpTypeDollar
	 * @depends testBadPhpType123
	 */
	public function testFieldNameAndPhpType(): void {
		$fielBuilder = FieldBuilder::create('test')->setPhpType('hello');

		$this->assertSame(
				$fielBuilder->getVisibility()->value . ' hello $test;',
				$fielBuilder->getPhpFileContent('')
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

	/**
	 * @depends testFieldNameAndPhpType
	 */
	public function testPrependLinesBy(): void {
		$fielBuilder = FieldBuilder::create('test')->setPhpType('hello');

		$expected = "\t" . $fielBuilder->getVisibility()->value
				. ' ' . $fielBuilder->getPhpType()
				. ' $' . $fielBuilder->getFieldName() . ';';
		$this->assertSame(
				$expected,
				$fielBuilder->getPhpFileContent("\t")
		);
	}

	/**
	 * @depends testFieldNameAndPhpType
	 */
	public function testVisibility(): void {
		$fielBuilder = FieldBuilder::create('test')->setPhpType('hello');

		$expectedPublic = 'public'
				. ' ' . $fielBuilder->getPhpType()
				. ' $' . $fielBuilder->getFieldName() . ';';
		$fielBuilder->setVisibility(Visibility::PUBLIC);
		$this->assertSame(
				$expectedPublic,
				$fielBuilder->getPhpFileContent('')
		);

		$expectedProtected = 'protected'
				. ' ' . $fielBuilder->getPhpType()
				. ' $' . $fielBuilder->getFieldName() . ';';
		$fielBuilder->setVisibility(Visibility::PROTECTED);
		$this->assertSame(
				$expectedProtected,
				$fielBuilder->getPhpFileContent('')
		);

		$expectedPrivate = 'private'
				. ' ' . $fielBuilder->getPhpType()
				. ' $' . $fielBuilder->getFieldName() . ';';
		$fielBuilder->setVisibility(Visibility::PRIVATE);
		$this->assertSame(
				$expectedPrivate,
				$fielBuilder->getPhpFileContent('')
		);
	}

	/**
	 * @depends testFieldNameAndPhpType
	 */
	public function testDefaultValue(): void {
		$fielBuilder = FieldBuilder::create('test')
				->setPhpType('hello')
				->setDefaultValue('hello');

		$fielBuilder->setDefaultValue('hello');
		$expectedWithValue = $fielBuilder->getVisibility()->value
				. ' ' . $fielBuilder->getPhpType()
				. ' $' . $fielBuilder->getFieldName() . ' = hello;';
		$this->assertSame(
				$expectedWithValue,
				$fielBuilder->getPhpFileContent('')
		);

		$fielBuilder->setDefaultValue('');
		$expectedWithoutValue = $fielBuilder->getVisibility()->value
				. ' ' . $fielBuilder->getPhpType()
				. ' $' . $fielBuilder->getFieldName() . ';';
		$this->assertSame(
				$expectedWithoutValue,
				$fielBuilder->getPhpFileContent('')
		);
	}

	/**
	 * @depends testFieldNameAndPhpType
	 * @depends testBadIsNullable
	 */
	public function testIsNullable(): void {
		$fielBuilder = FieldBuilder::create('test')
				->setPhpType('hello')
				->setIsNullable(true);

		$expected = $fielBuilder->getVisibility()->value
				. ' hello|null $' . $fielBuilder->getFieldName() . ';';
		$this->assertSame(
				$expected,
				$fielBuilder->getPhpFileContent('')
		);
	}

	public function testBadIsNullable(): void {
		$this->expectException(LogicException::class);
		FieldBuilder::create('test')
				->setIsNullable(true)
				->getPhpFileContent('');
	}

	/**
	 * @depends testFieldNameAndPhpType
	 * @depends testIsConstNullable
	 * @depends testIsConstNoDefaultValue
	 */
	public function testIsConst(): void {
		$fielBuilder = FieldBuilder::create('test')
				->setIsConst(true)
				->setDefaultValue('hello');

		$expected = 'final ' . $fielBuilder->getVisibility()->value
				. ' const ' . $fielBuilder->getFieldName() . ' = hello;';
		$this->assertSame(
				$expected,
				$fielBuilder->getPhpFileContent('')
		);
	}

	public function testIsConstNullable(): void {
		$this->expectException(LogicException::class);
		FieldBuilder::create('test')
				->setIsConst(true)
				->setIsNullable(true)
				->getPhpFileContent('');
	}

	public function testIsConstNoDefaultValue(): void {
		$this->expectException(LogicException::class);
		FieldBuilder::create('test')
				->setIsConst(true)
				->setDefaultValue('')
				->getPhpFileContent('');
	}

	/**
	 * @depends testFieldNameAndPhpType
	 */
	public function testCustomTypeHint(): void {
		$fielBuilder = FieldBuilder::create('test')
				->setPhpType('hello')
				->setCustomTypeHint('hi');

		$fileContentLines = explode("\n", $fielBuilder->getPhpFileContent(''));
		$this->assertCount(2, $fileContentLines);
		$this->assertSame(
				'/** @type hi */',
				$fileContentLines[0]
		);
	}

	/**
	 * @depends testFieldNameAndPhpType
	 */
	public function testComments(): void {
		$fielBuilder = FieldBuilder::create('test')
				->setPhpType('hello');

		$fielBuilder->addComments('one comment');
		$expectedOneComment = $fielBuilder->getVisibility()->value
				. ' ' . $fielBuilder->getPhpType()
				. ' $' . $fielBuilder->getFieldName() . '; // one comment';
		$this->assertSame(
				$expectedOneComment,
				$fielBuilder->getPhpFileContent('')
		);

		$fielBuilder->addComments('2nd comment');
		$expectedTwoComments = $fielBuilder->getVisibility()->value
				. ' ' . $fielBuilder->getPhpType()
				. ' $' . $fielBuilder->getFieldName() . '; // one comment. 2nd comment';
		$this->assertSame(
				$expectedTwoComments,
				$fielBuilder->getPhpFileContent('')
		);

		$fielBuilder->setComments([]);
		$expectedNoComments = $fielBuilder->getVisibility()->value
				. ' ' . $fielBuilder->getPhpType()
				. ' $' . $fielBuilder->getFieldName() . ';';
		$this->assertSame(
				$expectedNoComments,
				$fielBuilder->getPhpFileContent('')
		);
	}

}
