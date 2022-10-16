<?php

namespace SqlToCodeGenerator\test\codeGeneration\builder;

use LogicException;
use PHPUnit\Framework\TestCase;
use SqlToCodeGenerator\codeGeneration\builder\EnumBuilder;

class EnumBuilderPhpTest extends TestCase {

	public function testMinimal(): void {
		$fileBuilder = EnumBuilder::create(
				basePackage: 'basePackage',
				namespace: 'namespace',
				name: 'name',
		);

		$expected = "<?php

namespace basePackage\\namespace;

/**
 * This code is generated. Do not edit it
 */
enum name: string {

}
";
		$this->assertSame(
				$expected,
				$fileBuilder->getPhpFileContent()
		);
	}

	/**
	 * @depends testMinimal
	 */
	public function testFields(): void {
		$fileBuilder = EnumBuilder::create(
				basePackage: 'basePackage',
				namespace: 'namespace',
				name: 'name',
		);

		$fileBuilder->addFields('test');
		$expected = "<?php

namespace basePackage\\namespace;

/**
 * This code is generated. Do not edit it
 */
enum name: string {

	case test = 'test';

}
";
		$this->assertSame(
				$expected,
				$fileBuilder->getPhpFileContent()
		);
	}

	/**
	 * @depends testMinimal
	 */
	public function testBadFields(): void {
		$fileBuilder = EnumBuilder::create(
				basePackage: 'basePackage',
				namespace: 'namespace',
				name: 'name',
		);

		$this->expectException(LogicException::class);
		$fileBuilder->addFields('123');
	}

	/**
	 * @depends testMinimal
	 */
	public function testDuplicateFieldsError(): void {
		$fileBuilder = EnumBuilder::create(
				basePackage: 'basePackage',
				namespace: 'namespace',
				name: 'name',
		);

		$this->expectException(LogicException::class);
		$fileBuilder->addFields('test', 'test');
	}

}
