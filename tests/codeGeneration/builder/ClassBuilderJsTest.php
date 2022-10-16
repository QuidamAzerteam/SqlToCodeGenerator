<?php

namespace SqlToCodeGenerator\test\codeGeneration\builder;

use PHPUnit\Framework\TestCase;
use SqlToCodeGenerator\codeGeneration\builder\ClassBuilder;

class ClassBuilderJsTest extends TestCase {

	public function testMinimal(): void {
		$fileBuilder = ClassBuilder::create(
				basePackage: 'basePackage',
				namespace: 'namespace',
				name: 'name',
		);

		$expected = "export class name {

}
";
		$this->assertSame(
				$expected,
				$fileBuilder->getJsFileContent()
		);
	}

}
