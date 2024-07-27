<?php

namespace SqlToCodeGenerator\test\codeGeneration\metadata;

use LogicException;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;
use SqlToCodeGenerator\codeGeneration\attribute\ClassField;
use SqlToCodeGenerator\codeGeneration\attribute\ClassFieldEnum;
use SqlToCodeGenerator\codeGeneration\builder\ClassBuilder;
use SqlToCodeGenerator\codeGeneration\builder\FieldBuilder;
use SqlToCodeGenerator\codeGeneration\metadata\Bean;
use SqlToCodeGenerator\codeGeneration\metadata\BeanProperty;
use SqlToCodeGenerator\codeGeneration\metadata\BeanPropertyColKey;
use SqlToCodeGenerator\codeGeneration\metadata\BeanPropertyType;
use SqlToCodeGenerator\codeGeneration\metadata\ForeignBeanField;
use SqlToCodeGenerator\codeGeneration\utils\VariableUtils;
use SqlToCodeGenerator\sql\PdoContainer;
use SqlToCodeGenerator\sql\SqlDao;

class BeanTest extends TestCase {

	public function testGetPhpClassFileContentSimpleFieldProperty(): void {
		$bean = new Bean();
		$bean->basePackage = 'basePackage';
		$bean->beanNamespace = 'beanNamespace';
		$bean->sqlDatabase = 'sqlDatabase';
		$bean->sqlTable = 'sqlTable';

		$fieldBuilder = $this->createMock(FieldBuilder::class);
		$fieldBuilder->method('getPhpFileContent')->willReturn('getPhpFileContent');

		$beanProperty = $this->createMock(BeanProperty::class);
		$beanProperty->propertyType = BeanPropertyType::INT;
		$beanProperty->method('getFieldBuilder')->willReturn($fieldBuilder);
		$bean->properties[] = $beanProperty;

		$classBuilder = ClassBuilder::create(
				basePackage: 'basePackage',
				namespace: 'beanNamespace',
				name: $bean->getClassName(),
				docLines: ['Bean of `sqlDatabase.sqlTable`'],
		)->addFieldBuilders($fieldBuilder);

		$this->assertSame(
				$classBuilder->getPhpFileContent(),
				$bean->getPhpClassFileContent(),
		);
	}

	public function testGetPhpClassFileContentFieldWithClassFieldEnum(): void {
		$bean = new Bean();
		$bean->basePackage = 'basePackage';
		$bean->beanNamespace = 'beanNamespace';
		$bean->sqlDatabase = 'sqlDatabase';
		$bean->sqlTable = 'sqlTable';

		$beanProperty = $this->createMock(BeanProperty::class);
		$beanProperty->method('getFieldBuilder')->willReturn($this->createMock(FieldBuilder::class));
		$beanProperty->columnKey = BeanPropertyColKey::PRI;
		$bean->properties[] = $beanProperty;

		$this->assertStringContainsString(
				'use ' . ClassField::class,
				$bean->getPhpClassFileContent(),
		);
		$this->assertStringContainsString(
				'use ' . ClassFieldEnum::class,
				$bean->getPhpClassFileContent(),
		);
	}

	public function testGetPhpClassFileContentForeignBeans(): void {
		$bean = new Bean();
		$bean->basePackage = 'basePackage';
		$bean->beanNamespace = 'beanNamespace';
		$bean->sqlDatabase = 'sqlDatabase';
		$bean->sqlTable = 'sqlTable';

		$fieldBuilder = $this->createMock(FieldBuilder::class);
		$fieldBuilder->method('getPhpFileContent')->willReturn('getPhpFileContent');

		$foreignBeanField = $this->createMock(ForeignBeanField::class);
		$foreignBeanField->method('getAsFieldBuilderForPhp')->willReturn($fieldBuilder);

		$bean->foreignBeanFields[] = $foreignBeanField;

		$classBuilder = ClassBuilder::create(
				basePackage: 'basePackage',
				namespace: 'beanNamespace',
				name: $bean->getClassName(),
				docLines: ['Bean of `sqlDatabase.sqlTable`'],
		)->addFieldBuilders($fieldBuilder);

		$this->assertSame(
				$classBuilder->getPhpFileContent(),
				$bean->getPhpClassFileContent(),
		);
	}

	public function testGetPhpDaoFileContentProperty(): void {
		$bean = new Bean();
		$bean->basePackage = 'basePackage';
		$bean->beanNamespace = 'beanNamespace';
		$bean->daoNamespace = 'daoNamespace';
		$bean->sqlDatabase = 'sqlDatabase';
		$bean->sqlTable = 'sqlTable';

		$fieldBuilder = $this->createMock(FieldBuilder::class);
		$fieldBuilder->method('getPhpFileContent')->willReturn('getPhpFileContent');

		$beanProperty = $this->createMock(BeanProperty::class);
		$beanProperty->sqlName = 'sqlName';
		$beanProperty->method('getFieldBuilder')->willReturn($fieldBuilder);
		$bean->properties[] = $beanProperty;

		$this->assertStringContainsString(
				FieldBuilder::create(strtoupper($beanProperty->sqlName) . '_SQL')
						->setIsConst(true)
						->setDefaultValue("'$beanProperty->sqlName'")
						->getPhpFileContent(),
				$bean->getPhpDaoFileContent(),
		);
	}

	#[Depends('testGetPhpDaoFileContentProperty')]
	public function testGetPhpDaoFileContentPrimaryField(): void {
		$bean = new Bean();
		$bean->basePackage = 'basePackage';
		$bean->beanNamespace = 'beanNamespace';
		$bean->daoNamespace = 'daoNamespace';
		$bean->sqlDatabase = 'sqlDatabase';
		$bean->sqlTable = 'sqlTable';
		$bean->colNamesByUniqueConstraintName = ['helloWorld' => ['sqlName']];

		$fieldBuilder = $this->createMock(FieldBuilder::class);
		$fieldBuilder->method('getPhpFileContent')->willReturn('getPhpFileContent');

		$beanProperty = $this->createMock(BeanProperty::class);
		$beanProperty->sqlName = 'sqlName';
		$beanProperty->columnKey = BeanPropertyColKey::PRI;
		$beanProperty->method('getFieldBuilder')->willReturn($fieldBuilder);
		$beanProperty->method('getName')->willReturn(lcfirst(SqlDao::sqlToCamelCase($beanProperty->sqlName)));
		$bean->properties[] = $beanProperty;

		$primaryFieldFunctionName = ucfirst(VariableUtils::getPluralOfVarName($beanProperty->getName()));
		$this->assertStringContainsString(
				"public function deleteThrough$primaryFieldFunctionName(",
				$bean->getPhpDaoFileContent(),
		);
		$this->assertStringContainsString(
				"public function getFrom$primaryFieldFunctionName(",
				$bean->getPhpDaoFileContent(),
		);
		$restoreIdEndOfMethod = ucfirst($beanProperty->getName());
		$this->assertStringContainsString(
				"public function restoreIdsThrough$restoreIdEndOfMethod(",
				$bean->getPhpDaoFileContent(),
		);
	}

	#[Depends('testGetPhpDaoFileContentProperty')]
	public function testGetPhpDaoFileContentPrimaryFieldLogicException(): void {
		$bean = new Bean();
		$bean->basePackage = 'basePackage';
		$bean->beanNamespace = 'beanNamespace';
		$bean->daoNamespace = 'daoNamespace';
		$bean->sqlDatabase = 'sqlDatabase';
		$bean->sqlTable = 'sqlTable';
		$bean->colNamesByUniqueConstraintName = ['helloWorld' => ['fail']];

		$fieldBuilder = $this->createMock(FieldBuilder::class);
		$fieldBuilder->method('getPhpFileContent')->willReturn('getPhpFileContent');

		$beanProperty = $this->createMock(BeanProperty::class);
		$beanProperty->sqlName = 'sqlName';
		$beanProperty->columnKey = BeanPropertyColKey::PRI;
		$beanProperty->method('getFieldBuilder')->willReturn($fieldBuilder);
		$beanProperty->method('getName')->willReturn(lcfirst(SqlDao::sqlToCamelCase($beanProperty->sqlName)));
		$bean->properties[] = $beanProperty;

		$this->expectException(LogicException::class);
		$bean->getPhpDaoFileContent();
	}

	#[Depends('testGetPhpDaoFileContentProperty')]
	public function testGetPhpDaoFileContentForeignBeans(): void {
		$bean = new Bean();
		$bean->basePackage = 'basePackage';
		$bean->beanNamespace = 'beanNamespace';
		$bean->daoNamespace = 'daoNamespace';
		$bean->sqlDatabase = 'sqlDatabase';
		$bean->sqlTable = 'sqlTable';

		$fieldBuilder = $this->createMock(FieldBuilder::class);
		$fieldBuilder->method('getPhpFileContent')->willReturn('getPhpFileContent');

		$beanProperty = $this->createMock(BeanProperty::class);
		$beanProperty->sqlName = 'sql_name';
		$beanProperty->columnKey = BeanPropertyColKey::PRI;
		$beanProperty->method('getFieldBuilder')->willReturn($fieldBuilder);
		$beanProperty->method('getName')->willReturn(lcfirst(SqlDao::sqlToCamelCase($beanProperty->sqlName)));
		$bean->properties[] = $beanProperty;

		$foreignBeanField = $this->createMock(ForeignBeanField::class);
		$foreignBeanField->toBean = $bean;
		$foreignBeanField->onProperty = $beanProperty;
		$foreignBeanField->withProperty = $beanProperty;
		$bean->foreignBeanFields[] = $foreignBeanField;

		$classNameInMethod = ucfirst(SqlDao::sqlToCamelCase($foreignBeanField->withProperty->getSqlNameWithoutId()));
		$this->assertStringContainsString(
				"public function completeWith$classNameInMethod(",
				$bean->getPhpDaoFileContent(),
		);

		$foreignBeanField->isArray = true;
		$classNameInMethod = $foreignBeanField->toBean->getClassName()
				. ucfirst(VariableUtils::getPluralOfVarName(
						$foreignBeanField->onProperty->getName($foreignBeanField->withProperty->sqlName)
				));
		$this->assertStringContainsString(
				"public function completeWith$classNameInMethod(",
				$bean->getPhpDaoFileContent(),
		);
	}

	public function testGetJsClassFileContentProperty(): void {
		$bean = new Bean();
		$bean->basePackage = 'basePackage';
		$bean->beanNamespace = 'beanNamespace';
		$bean->sqlDatabase = 'sqlDatabase';
		$bean->sqlTable = 'sqlTable';

		$fieldBuilder = $this->createMock(FieldBuilder::class);
		$fieldBuilder->method('getJsFileContent')->willReturn('getJsFileContent');

		$beanProperty = $this->createMock(BeanProperty::class);
		$beanProperty->method('getFieldBuilder')->willReturn($fieldBuilder);
		$bean->properties[] = $beanProperty;

		$this->assertStringContainsString(
				'getJsFileContent',
				$bean->getJsClassFileContent(),
		);
	}

	public function testGetJsClassFileContentForeignBeans(): void {
		$bean = new Bean();
		$bean->basePackage = 'basePackage';
		$bean->beanNamespace = 'beanNamespace';
		$bean->sqlDatabase = 'sqlDatabase';
		$bean->sqlTable = 'sqlTable';

		$fieldBuilder = $this->createMock(FieldBuilder::class);
		$fieldBuilder->method('getJsFileContent')->willReturn('getJsFileContent');

		$foreignBeanField = $this->createMock(ForeignBeanField::class);
		$foreignBeanField->method('getAsFieldBuilderForJs')->willReturn($fieldBuilder);

		$bean->foreignBeanFields[] = $foreignBeanField;

		$this->assertStringContainsString(
				'getJsFileContent',
				$bean->getJsClassFileContent(),
		);
	}

	public function testGetPhpTestFileContent(): void {
		$bean = new Bean();
		$bean->basePackage = 'basePackage';
		$bean->beanNamespace = 'beanNamespace';
		$bean->sqlDatabase = 'sqlDatabase';
		$bean->sqlTable = 'sqlTable';

		$this->assertStringContainsString(
				'use ' . TestCase::class,
				$bean->getPhpTestFileContent(),
		);
		$this->assertStringContainsString(
				"class {$bean->getClassName()}Test extends TestCase",
				$bean->getPhpTestFileContent(),
		);
		$this->assertStringContainsString(
				"use $bean->basePackage\\$bean->beanNamespace\\{$bean->getClassName()}",
				$bean->getPhpTestFileContent(),
		);
		$this->assertStringContainsString(
				'testConstructor',
				$bean->getPhpTestFileContent(),
		);
	}

	public function testGetPhpDaoTestFileContent(): void {
		$bean = new Bean();
		$bean->basePackage = 'basePackage';
		$bean->beanNamespace = 'beanNamespace';
		$bean->daoNamespace = 'daoNamespace';
		$bean->sqlDatabase = 'sqlDatabase';
		$bean->sqlTable = 'sqlTable';

		$this->assertStringContainsString(
				'use ' . TestCase::class,
				$bean->getPhpDaoTestFileContent(),
		);
		$this->assertStringContainsString(
				"class {$bean->getDaoName()}Test extends TestCase",
				$bean->getPhpDaoTestFileContent(),
		);
		$this->assertStringContainsString(
				'use ' . PdoContainer::class,
				$bean->getPhpDaoTestFileContent(),
		);
		$this->assertStringContainsString(
				"use $bean->basePackage\\$bean->daoNamespace\\{$bean->getClassName()}",
				$bean->getPhpDaoTestFileContent(),
		);
		$this->assertStringContainsString(
				'testConstructor',
				$bean->getPhpDaoTestFileContent(),
		);
	}

}
