<?php

namespace SqlToCodeGenerator\test\codeGeneration\metadata;

use PHPUnit\Framework\TestCase;
use SqlToCodeGenerator\codeGeneration\builder\FieldBuilder;
use SqlToCodeGenerator\codeGeneration\metadata\Bean;
use SqlToCodeGenerator\codeGeneration\metadata\ForeignBeanField;
use SqlToCodeGenerator\codeGeneration\utils\VariableUtils;

class ForeignBeanFieldTest extends TestCase {

	public function testGetAsFieldBuilder(): void {
		$toBean = $this->createMock(Bean::class);
		$toBean->method('getClassName')->willReturn('getClassName');

		$foreignBeanField = new ForeignBeanField();
		$foreignBeanField->toBean = $toBean;

		$this->assertSame(
				FieldBuilder::create(lcfirst('getClassName'))
						->setPhpType('getClassName')
						->getPhpFileContent(),
				$foreignBeanField->getAsFieldBuilderForPhp()
						->getPhpFileContent(),
		);
		$this->assertSame(
				FieldBuilder::create(lcfirst('getClassName'))
						->setJsType('getClassName')
						->getJsFileContent(),
				$foreignBeanField->getAsFieldBuilderForJs()
						->getJsFileContent(),
		);

		$foreignBeanField->isArray = true;

		$this->assertSame(
				FieldBuilder::create(VariableUtils::getPluralOfVarName('getClassName'))
						->setPhpType('array')
						->setDefaultValue('[]')
						->setCustomTypeHint('getClassName[]')
						->getPhpFileContent(),
				$foreignBeanField->getAsFieldBuilderForPhp()
						->getPhpFileContent(),
		);
		$this->assertSame(
				FieldBuilder::create(VariableUtils::getPluralOfVarName('getClassName'))
						->setJsType('getClassName[]')
						->getJsFileContent(),
				$foreignBeanField->getAsFieldBuilderForJs()
						->getJsFileContent(),
		);
	}

}
