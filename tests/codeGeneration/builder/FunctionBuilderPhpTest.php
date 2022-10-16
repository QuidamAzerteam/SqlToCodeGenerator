<?php

namespace SqlToCodeGenerator\test\codeGeneration\builder;

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

		$expected = "public function name(): returnType {\n}";
		$this->assertSame(
				$expected,
				$functionBuilder->getPhpFileContent('')
		);
	}

	/**
	 * @depends testMinimal
	 */
	public function testIndent(): void {
		$functionBuilder = FunctionBuilder::create(
				name: 'name',
				returnType: 'returnType',
		);

		$expectedOneIndent = "\tpublic function {$functionBuilder->getName()}(): {$functionBuilder->getReturnType()} {\n\t}";
		$this->assertSame(
				$expectedOneIndent,
				$functionBuilder->getPhpFileContent("\t")
		);

		$expectedTwoIndents = "\t\tpublic function {$functionBuilder->getName()}(): {$functionBuilder->getReturnType()} {\n\t\t}";
		$this->assertSame(
				$expectedTwoIndents,
				$functionBuilder->getPhpFileContent("\t\t")
		);
	}

	/**
	 * @depends testMinimal
	 */
	public function testDocumentationLines(): void {
		$functionBuilder = FunctionBuilder::create(
				name: 'name',
				returnType: 'returnType',
				documentationLines: array(
					'test',
				),
		);

		$expected = "/**
 * test
 */
public function {$functionBuilder->getName()}(): {$functionBuilder->getReturnType()} {
}";
		$this->assertSame(
				$expected,
				$functionBuilder->getPhpFileContent('')
		);
	}

	/**
	 * @depends testMinimal
	 */
	public function testIsFinal(): void {
		$functionBuilder = FunctionBuilder::create(
				name: 'name',
				returnType: 'returnType',
				isFinal: true,
		);

		$expected = "final public function {$functionBuilder->getName()}(): {$functionBuilder->getReturnType()} {
}";
		$this->assertSame(
				$expected,
				$functionBuilder->getPhpFileContent('')
		);
	}

	/**
	 * @depends testMinimal
	 */
	public function testIsStatic(): void {
		$functionBuilder = FunctionBuilder::create(
				name: 'name',
				returnType: 'returnType',
				isStatic: true,
		);

		$expected = "public static function {$functionBuilder->getName()}(): {$functionBuilder->getReturnType()} {
}";
		$this->assertSame(
				$expected,
				$functionBuilder->getPhpFileContent('')
		);
	}

	/**
	 * @depends testMinimal
	 */
	public function testIsStaticAndIsFinal(): void {
		$functionBuilder = FunctionBuilder::create(
				name: 'name',
				returnType: 'returnType',
				isStatic: true,
				isFinal: true,
		);

		$expected = "final public static function {$functionBuilder->getName()}(): {$functionBuilder->getReturnType()} {
}";
		$this->assertSame(
				$expected,
				$functionBuilder->getPhpFileContent('')
		);
	}

	/**
	 * @depends testMinimal
	 */
	public function testVisibility(): void {
		$functionBuilder = FunctionBuilder::create(
				name: 'name',
				returnType: 'returnType',
		);

		$expectedDefault = "public function {$functionBuilder->getName()}(): {$functionBuilder->getReturnType()} {
}";
		$this->assertSame(
				$expectedDefault,
				$functionBuilder->getPhpFileContent('')
		);

		$expectedPrivate = "private function {$functionBuilder->getName()}(): {$functionBuilder->getReturnType()} {
}";
		$this->assertSame(
				$expectedPrivate,
				$functionBuilder->setVisibility(Visibility::PRIVATE)->getPhpFileContent('')
		);

		$expectedProtected = "protected function {$functionBuilder->getName()}(): {$functionBuilder->getReturnType()} {
}";
		$this->assertSame(
				$expectedProtected,
				$functionBuilder->setVisibility(Visibility::PROTECTED)->getPhpFileContent('')
		);

		$expectedPublic = "public function {$functionBuilder->getName()}(): {$functionBuilder->getReturnType()} {
}";
		$this->assertSame(
				$expectedPublic,
				$functionBuilder->setVisibility(Visibility::PUBLIC)->getPhpFileContent('')
		);
	}

	/**
	 * @depends testMinimal
	 */
	public function testLines(): void {
		$functionBuilder = FunctionBuilder::create(
				name: 'name',
				returnType: 'returnType',
				lines: array(
					Line::create('test'),
					Line::create('test2', 1),
				),
		);

		$expected = "public function {$functionBuilder->getName()}(): {$functionBuilder->getReturnType()} {
	test
		test2
}";
		$this->assertSame(
				$expected,
				$functionBuilder->getPhpFileContent('')
		);
	}

}
