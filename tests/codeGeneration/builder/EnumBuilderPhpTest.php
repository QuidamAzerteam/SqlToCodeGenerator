<?php

namespace SqlToCodeGenerator\test\codeGeneration\builder;

use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;
use SqlToCodeGenerator\codeGeneration\builder\EnumBuilder;

class EnumBuilderPhpTest extends TestCase {

	public function testMinimal(): void {
		$fileBuilder = EnumBuilder::create(
				basePackage: 'basePackage',
				namespace: 'namespace',
				name: 'name',
		);

		$expected = <<<PHP
			<?php

			namespace basePackage\\namespace;

			/**
			 * This code is generated. Do not edit it.
			 */
			enum name {

			}

			PHP;
		$this->assertSame(
				$expected,
				$fileBuilder->getPhpFileContent(),
		);
	}

	#[Depends('testMinimal')]
	public function testFields(): void {
		$fileBuilder = EnumBuilder::create(
				basePackage: 'basePackage',
				namespace: 'namespace',
				name: 'name',
				fields: ['test1', 'test2'],
		);

		$expected = <<<PHP
			<?php

			namespace basePackage\\namespace;

			/**
			 * This code is generated. Do not edit it.
			 */
			enum name {

				case test1;
				case test2;

			}

			PHP;
		$this->assertSame(
				$expected,
				$fileBuilder->getPhpFileContent(),
		);
	}

}
