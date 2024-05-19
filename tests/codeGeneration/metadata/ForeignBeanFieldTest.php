<?php

namespace SqlToCodeGenerator\test\codeGeneration\metadata;

use PHPUnit\Framework\TestCase;
use SqlToCodeGenerator\codeGeneration\builder\FieldBuilder;
use SqlToCodeGenerator\codeGeneration\metadata\Bean;
use SqlToCodeGenerator\codeGeneration\metadata\BeanProperty;
use SqlToCodeGenerator\codeGeneration\metadata\BeanPropertyColKey;
use SqlToCodeGenerator\codeGeneration\metadata\ForeignBeanField;
use SqlToCodeGenerator\codeGeneration\utils\VariableUtils;

class ForeignBeanFieldTest extends TestCase {

	public function testGetAsFieldBuilder(): void {
		$toBean = $this->createMock(Bean::class);
		$toBean->method('getClassName')->willReturn('getClassName');

		$beanProperty = $this->createMock(BeanProperty::class);
		$beanProperty->sqlName = 'sql_name';
		$beanProperty->columnKey = BeanPropertyColKey::PRI;
		$beanProperty->isNullable = false;
		$beanProperty->method('getSqlNameWithoutId')->willReturn('sqlName');

		$foreignBeanField = new ForeignBeanField();
		$foreignBeanField->toBean = $toBean;
		$foreignBeanField->withProperty = $beanProperty;

		$this->assertSame(
				FieldBuilder::create(lcfirst('sqlName'))
						->setPhpType('getClassName')
						->getPhpFileContent(),
				$foreignBeanField->getAsFieldBuilderForPhp()
						->getPhpFileContent(),
		);
		$this->assertSame(
				FieldBuilder::create(lcfirst('sqlName'))
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
