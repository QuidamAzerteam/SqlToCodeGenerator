<?php

namespace SqlToCodeGenerator\test\codeGeneration\builder;

use LogicException;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;
use SqlToCodeGenerator\codeGeneration\builder\FieldBuilder;

class FieldBuilderJsTest extends TestCase {

	public function testFieldNameAndJsType(): void {
		$fieldBuilder = FieldBuilder::create('test')->setJsType('hello');

		$fileContentLines = explode("\n", $fieldBuilder->getJsFileContent(''));
		$this->assertCount(2, $fileContentLines);
		$this->assertSame(
				'/** @type {hello} */',
				$fileContentLines[0],
		);
		$this->assertSame(
				'test',
				$fileContentLines[1],
		);
	}

	public function testBadFieldNameEmpty(): void {
		$this->expectException(LogicException::class);
		FieldBuilder::create('');
	}

	public function testNoJsType(): void {
		$this->expectException(LogicException::class);
		FieldBuilder::create('test')->setJsType('')->getJsFileContent('');
	}

	#[Depends('testFieldNameAndJsType')]
	public function testPrependLinesBy(): void {
		$fieldBuilder = FieldBuilder::create('test')->setJsType('hello');

		$fileContentLine = explode("\n", $fieldBuilder->getJsFileContent("\t"))[1];
		$this->assertSame(
				"	test",
				$fileContentLine,
		);
	}

	#[Depends('testFieldNameAndJsType')]
	public function testDefaultValue(): void {
		$fieldBuilder = FieldBuilder::create('test')->setJsType('hello');

		$fieldBuilder->setDefaultValue('hello');
		$fileContentLine = explode("\n", $fieldBuilder->getJsFileContent(''))[1];
		$this->assertSame(
				'test = hello',
				$fileContentLine,
		);

		$fieldBuilder->setDefaultValue('');
		$fileContentLine = explode("\n", $fieldBuilder->getJsFileContent(''))[1];
		$this->assertSame(
				'test',
				$fileContentLine,
		);
	}

	#[Depends('testFieldNameAndJsType')]
	public function testIsNullable(): void {
		$fieldBuilder = FieldBuilder::create('test')
				->setJsType('hello')
				->setIsNullable(true);

		$fileContentLine = explode("\n", $fieldBuilder->getJsFileContent(''))[0];
		$this->assertSame(
				'/** @type {hello|null} */',
				$fileContentLine,
		);
	}

	#[Depends('testFieldNameAndJsType')]
	public function testCustomTypeHint(): void {
		$fieldBuilder = FieldBuilder::create('test')
				->setJsType('hello')
				->setCustomTypeHint('hi');

		$fileContentLine = explode("\n", $fieldBuilder->getJsFileContent(''))[0];
		$this->assertNotSame(
				'/** @type {hello} */',
				$fileContentLine,
		);
		$this->assertSame(
				'/** @type {hi} */',
				$fileContentLine,
		);
	}

	#[Depends('testFieldNameAndJsType')]
	#[Depends('testCustomTypeHint')]
	#[Depends('testIsNullable')]
	public function testCustomTypeHintWithIsNullable(): void {
		$fieldBuilder = FieldBuilder::create('test')
				->setJsType('hello')
				->setCustomTypeHint('hi')
				->setIsNullable(true);

		$fieldBuilder->setIsNullable(true);
		$fileContentLine = explode("\n", $fieldBuilder->getJsFileContent(''))[0];
		$this->assertNotSame(
				'/** @type {hello|null} */',
				$fileContentLine,
		);
		$this->assertSame(
				'/** @type {hi|null} */',
				$fileContentLine,
		);
	}

	#[Depends('testFieldNameAndJsType')]
	public function testComments(): void {
		$fieldBuilder = FieldBuilder::create('test')
				->setJsType('hello');

		$fieldBuilder->addComments('one comment');
		$fileContentLine = explode("\n", $fieldBuilder->getJsFileContent(''))[1];
		$this->assertSame(
				'test // one comment',
				$fileContentLine,
		);

		$fieldBuilder->addComments('2nd comment');
		$fileContentLine = explode("\n", $fieldBuilder->getJsFileContent(''))[1];
		$this->assertSame(
				'test // one comment. 2nd comment',
				$fileContentLine,
		);

		$fieldBuilder->setComments([]);
		$fileContentLine = explode("\n", $fieldBuilder->getJsFileContent(''))[1];
		$this->assertSame(
				'test',
				$fileContentLine,
		);
	}

}
