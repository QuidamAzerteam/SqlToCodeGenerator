<?php

namespace SqlToCodeGenerator\test\codeGeneration\builder;

use LogicException;
use PHPUnit\Framework\TestCase;
use SqlToCodeGenerator\codeGeneration\builder\FieldBuilder;
use SqlToCodeGenerator\codeGeneration\enums\Visibility;

class FieldBuilderJsTest extends TestCase {

	public function testFieldNameAndJsType(): void {
		$fielBuilder = FieldBuilder::create('test')->setJsType('hello');

		$fileContentLines = explode("\n", $fielBuilder->getJsFileContent(''));
		$this->assertCount(2, $fileContentLines);
		$this->assertSame(
				'/** @type {hello} */',
				$fileContentLines[0]
		);
		$this->assertSame(
				'test',
				$fileContentLines[1]
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

	/**
	 * @depends testFieldNameAndJsType
	 */
	public function testPrependLinesBy(): void {
		$fielBuilder = FieldBuilder::create('test')->setJsType('hello');

		$fileContentLine = explode("\n", $fielBuilder->getJsFileContent("\t"))[1];
		$this->assertSame(
				"	test",
				$fileContentLine
		);
	}

	/**
	 * @depends testFieldNameAndJsType
	 */
	public function testDefaultValue(): void {
		$fielBuilder = FieldBuilder::create('test')->setJsType('hello');

		$fielBuilder->setDefaultValue('hello');
		$fileContentLine = explode("\n", $fielBuilder->getJsFileContent(''))[1];
		$this->assertSame(
				'test = hello',
				$fileContentLine
		);

		$fielBuilder->setDefaultValue('');
		$fileContentLine = explode("\n", $fielBuilder->getJsFileContent(''))[1];
		$this->assertSame(
				'test',
				$fileContentLine
		);
	}

	/**
	 * @depends testFieldNameAndJsType
	 */
	public function testIsNullable(): void {
		$fielBuilder = FieldBuilder::create('test')
				->setJsType('hello')
				->setIsNullable(true);

		$fileContentLine = explode("\n", $fielBuilder->getJsFileContent(''))[0];
		$this->assertSame(
				'/** @type {hello|null} */',
				$fileContentLine
		);
	}

	/**
	 * @depends testFieldNameAndJsType
	 */
	public function testCustomTypeHint(): void {
		$fielBuilder = FieldBuilder::create('test')
				->setJsType('hello')
				->setCustomTypeHint('hi');

		$fileContentLine = explode("\n", $fielBuilder->getJsFileContent(''))[0];
		$this->assertNotSame(
				'/** @type {hello} */',
				$fileContentLine
		);
		$this->assertSame(
				'/** @type {hi} */',
				$fileContentLine
		);
	}

	/**
	 * @depends testFieldNameAndJsType
	 * @depends testCustomTypeHint
	 * @depends testIsNullable
	 */
	public function testCustomTypeHintWithIsNullable(): void {
		$fielBuilder = FieldBuilder::create('test')
				->setJsType('hello')
				->setCustomTypeHint('hi')
				->setIsNullable(true);

		$fielBuilder->setIsNullable(true);
		$fileContentLine = explode("\n", $fielBuilder->getJsFileContent(''))[0];
		$this->assertNotSame(
				'/** @type {hello|null} */',
				$fileContentLine
		);
		$this->assertSame(
				'/** @type {hi|null} */',
				$fileContentLine
		);
	}

	/**
	 * @depends testFieldNameAndJsType
	 */
	public function testComments(): void {
		$fielBuilder = FieldBuilder::create('test')
				->setJsType('hello');

		$fielBuilder->addComments('one comment');
		$fileContentLine = explode("\n", $fielBuilder->getJsFileContent(''))[1];
		$this->assertSame(
				'test // one comment',
				$fileContentLine
		);

		$fielBuilder->addComments('2nd comment');
		$fileContentLine = explode("\n", $fielBuilder->getJsFileContent(''))[1];
		$this->assertSame(
				'test // one comment. 2nd comment',
				$fileContentLine
		);

		$fielBuilder->setComments([]);
		$fileContentLine = explode("\n", $fielBuilder->getJsFileContent(''))[1];
		$this->assertSame(
				'test',
				$fileContentLine
		);
	}

}
