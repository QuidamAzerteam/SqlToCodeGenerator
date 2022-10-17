<?php

namespace SqlToCodeGenerator\test\codeGeneration\builder;

use LogicException;
use PHPUnit\Framework\TestCase;
use SqlToCodeGenerator\codeGeneration\builder\EnumBuilder;

class EnumBuilderJsTest extends TestCase {

	public function testFields(): void {
		$fileBuilder = EnumBuilder::create(
				basePackage: 'basePackage',
				namespace: 'namespace',
				name: 'name',
		);

		$fileBuilder->addFields('test');
		$expected = "export class name {

	static get test() {
		return 1;
	}

}
";
		$this->assertSame(
				$expected,
				$fileBuilder->getJsFileContent()
		);
	}

}
