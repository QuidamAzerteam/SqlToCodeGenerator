<?php

namespace SqlToCodeGenerator\test\codeGeneration\builder;

use LogicException;
use PHPUnit\Framework\TestCase;
use SqlToCodeGenerator\codeGeneration\builder\ClassBuilder;

class ClassBuilderPhpTest extends TestCase {

	public function testMinimal(): void {
		$fileBuilder = ClassBuilder::create(
				basePackage: 'basePackage',
				namespace: 'namespace',
				name: 'name',
		);

		$expected = "<?php

namespace basePackage\\namespace;

/**
 * This code is generated. Do not edit it
 */
class name {

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
	public function testOneImport(): void {
		$fileBuilder = ClassBuilder::create(
				basePackage: 'basePackage',
				namespace: 'namespace',
				name: 'name',
		);

		$fileBuilder->addImports('test');
		$expected = "<?php

namespace basePackage\\namespace;

use test;

/**
 * This code is generated. Do not edit it
 */
class name {

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
	public function testUniqueImport(): void {
		$fileBuilder = ClassBuilder::create(
				basePackage: 'basePackage',
				namespace: 'namespace',
				name: 'name',
		);

		$fileBuilder->addImports('test', 'test');
		$expected = "<?php

namespace basePackage\\namespace;

use test;

/**
 * This code is generated. Do not edit it
 */
class name {

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
	public function testTwoImports(): void {
		$fileBuilder = ClassBuilder::create(
				basePackage: 'basePackage',
				namespace: 'namespace',
				name: 'name',
		);

		$fileBuilder->addImports('test', 'test2');
		$expected = "<?php

namespace basePackage\\namespace;

use test;
use test2;

/**
 * This code is generated. Do not edit it
 */
class name {

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
	public function testImportsOrders(): void {
		$fileBuilder = ClassBuilder::create(
				basePackage: 'basePackage',
				namespace: 'namespace',
				name: 'name',
		);

		$fileBuilder->addImports('aTest', 'cTest', 'bTest');
		$expected = "<?php

namespace basePackage\\namespace;

use aTest;
use bTest;
use cTest;

/**
 * This code is generated. Do not edit it
 */
class name {

}
";
		$this->assertSame(
				$expected,
				$fileBuilder->getPhpFileContent()
		);
	}

	public function testEmptyNamespace(): void {
		$this->expectException(LogicException::class);
		ClassBuilder::create(
				basePackage: '',
				namespace: '',
				name: 'name',
		);
	}

	public function testBadNamespace(): void {
		$this->expectException(LogicException::class);
		ClassBuilder::create(
				basePackage: '\\',
				namespace: '\\',
				name: 'name',
		);
	}

	public function testNumericNamespace(): void {
		ClassBuilder::create(
				basePackage: '1',
				namespace: '2',
				name: 'name',
		);
		$this->assertTrue(true);
	}

	/**
	 * @depends testMinimal
	 */
	public function testExtends(): void {
		$fileBuilder = ClassBuilder::create(
				basePackage: 'basePackage',
				namespace: 'namespace',
				name: 'name',
				extends: 'coucou',
		);

		$expected = "<?php

namespace basePackage\\namespace;

/**
 * This code is generated. Do not edit it
 */
class name extends coucou {

}
";
		$this->assertSame(
				$expected,
				$fileBuilder->getPhpFileContent()
		);
	}

	public function testBadExtends(): void {
		$this->expectException(LogicException::class);
		ClassBuilder::create(
				basePackage: 'basePackage',
				namespace: 'namespace',
				name: 'name',
				extends: 'hello world',
		);
	}

	/**
	 * @depends testMinimal
	 */
	public function testImplements(): void {
		$fileBuilder = ClassBuilder::create(
				basePackage: 'basePackage',
				namespace: 'namespace',
				name: 'name',
				implements: 'coucou',
		);

		$expected = "<?php

namespace basePackage\\namespace;

/**
 * This code is generated. Do not edit it
 */
class name implements coucou {

}
";
		$this->assertSame(
				$expected,
				$fileBuilder->getPhpFileContent()
		);
	}

	public function testBadImplements(): void {
		$this->expectException(LogicException::class);
		ClassBuilder::create(
				basePackage: 'basePackage',
				namespace: 'namespace',
				name: 'name',
				implements: 'hello world',
		);
	}

	/**
	 * @depends testMinimal
	 */
	public function testExtendsAndImplements(): void {
		$fileBuilder = ClassBuilder::create(
				basePackage: 'basePackage',
				namespace: 'namespace',
				name: 'name',
				extends: 'coucouExtends',
				implements: 'coucouImplements',
		);

		$expected = "<?php

namespace basePackage\\namespace;

/**
 * This code is generated. Do not edit it
 */
class name extends coucouExtends implements coucouImplements {

}
";
		$this->assertSame(
				$expected,
				$fileBuilder->getPhpFileContent()
		);
	}

}
