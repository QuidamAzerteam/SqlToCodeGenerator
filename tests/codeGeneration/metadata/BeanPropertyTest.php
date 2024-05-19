<?php

namespace SqlToCodeGenerator\test\codeGeneration\metadata;

use PHPUnit\Framework\TestCase;
use SqlToCodeGenerator\codeGeneration\builder\FieldBuilder;
use SqlToCodeGenerator\codeGeneration\metadata\Bean;
use SqlToCodeGenerator\codeGeneration\metadata\BeanProperty;
use SqlToCodeGenerator\codeGeneration\metadata\BeanPropertyColKey;
use SqlToCodeGenerator\codeGeneration\metadata\BeanPropertyType;

class BeanPropertyTest extends TestCase {

	public function testGetName(): void {
		$beanProperty = new BeanProperty();
		$beanProperty->sqlName = 'test';

		$this->assertSame(lcfirst('test'), $beanProperty->getName());
	}

	public function testGetSqlNameWithoutId(): void {
		$beanProperty = new BeanProperty();

		$beanProperty->sqlName = 'ok';
		$this->assertSame('ok', $beanProperty->getSqlNameWithoutId());

		$beanProperty->sqlName = 'id_ok2';
		$this->assertSame('ok2', $beanProperty->getSqlNameWithoutId());
	}

	public function testGetUniqueKey(): void {
		$beanProperty = new BeanProperty();
		$beanProperty->sqlName = 'test';

		$belongsToBean = new Bean();
		$belongsToBean->sqlTable = 'belongsToBean';
		$belongsToBean->sqlDatabase = 'belongsToBeanDataBase';

		$beanProperty->belongsToBean = $belongsToBean;

		$this->assertSame('belongsToBeanDataBase_belongsToBean_test', $beanProperty->getUniqueKey());
	}

	public function testFieldBuilderSimpleType(): void {
		$beanProperty = new BeanProperty();
		$beanProperty->sqlName = 'test';
		$beanProperty->propertyType = BeanPropertyType::STRING;
		$beanProperty->isNullable = false;

		$fieldBuilder = FieldBuilder::create($beanProperty->getName())
				->setPhpType(BeanPropertyType::getPhpType($beanProperty->propertyType))
				->setJsType(BeanPropertyType::getJsType($beanProperty->propertyType))
				->setIsNullable(false);
		$this->assertSame(
				$fieldBuilder->getPhpFileContent(),
				$beanProperty->getFieldBuilder()->getPhpFileContent(),
		);
		$this->assertSame(
				$fieldBuilder->getJsFileContent(),
				$beanProperty->getFieldBuilder()->getJsFileContent(),
		);
	}

	public function testFieldBuilderSimpleTypeNullable(): void {
		$beanProperty = new BeanProperty();
		$beanProperty->sqlName = 'test';
		$beanProperty->propertyType = BeanPropertyType::STRING;
		$beanProperty->isNullable = true;

		$fieldBuilder = FieldBuilder::create($beanProperty->getName())
				->setPhpType(BeanPropertyType::getPhpType($beanProperty->propertyType))
				->setJsType(BeanPropertyType::getJsType($beanProperty->propertyType))
				->setIsNullable(true)
				->setDefaultValue('null');
		$this->assertSame(
				$fieldBuilder->getPhpFileContent(),
				$beanProperty->getFieldBuilder()->getPhpFileContent(),
		);
		$this->assertSame(
				$fieldBuilder->getJsFileContent(),
				$beanProperty->getFieldBuilder()->getJsFileContent(),
		);
	}

	public function testFieldBuilderSimpleTypeNullableWithDefaultValue(): void {
		$beanProperty = new BeanProperty();
		$beanProperty->sqlName = 'test';
		$beanProperty->propertyType = BeanPropertyType::STRING;
		$beanProperty->isNullable = true;
		$beanProperty->defaultValueAsString = 'defaultValue';

		$fieldBuilder = FieldBuilder::create($beanProperty->getName())
				->setPhpType(BeanPropertyType::getPhpType($beanProperty->propertyType))
				->setJsType(BeanPropertyType::getJsType($beanProperty->propertyType))
				->setIsNullable(true)
				->setDefaultValue('defaultValue');
		$this->assertSame(
				$fieldBuilder->getPhpFileContent(),
				$beanProperty->getFieldBuilder()->getPhpFileContent(),
		);
		$this->assertSame(
				$fieldBuilder->getJsFileContent(),
				$beanProperty->getFieldBuilder()->getJsFileContent(),
		);
	}

	public function testFieldBuilderAsPrimary(): void {
		$beanProperty = new BeanProperty();
		$beanProperty->sqlName = 'test';
		$beanProperty->isNullable = false;
		$beanProperty->propertyType = BeanPropertyType::STRING;
		$beanProperty->columnKey = BeanPropertyColKey::PRI;

		$fieldBuilder = FieldBuilder::create($beanProperty->getName())
				->setPhpType(BeanPropertyType::getPhpType($beanProperty->propertyType))
				->setJsType(BeanPropertyType::getJsType($beanProperty->propertyType))
				->setIsNullable(true)
				->setDefaultValue('null')
				->addComments(BeanPropertyColKey::PRI->toHumanReadableString() . ' key')
				->setClassFieldEnum(BeanPropertyColKey::PRI->toClassFieldEnum());
		$this->assertSame(
				$fieldBuilder->getPhpFileContent(),
				$beanProperty->getFieldBuilder()->getPhpFileContent(),
		);
		$this->assertSame(
				$fieldBuilder->getJsFileContent(),
				$beanProperty->getFieldBuilder()->getJsFileContent(),
		);
	}

	public function testFieldBuilderAsUnique(): void {
		$beanProperty = new BeanProperty();
		$beanProperty->sqlName = 'test';
		$beanProperty->isNullable = false;
		$beanProperty->propertyType = BeanPropertyType::STRING;
		$beanProperty->columnKey = BeanPropertyColKey::UNI;

		$fieldBuilder = FieldBuilder::create($beanProperty->getName())
				->setPhpType(BeanPropertyType::getPhpType($beanProperty->propertyType))
				->setJsType(BeanPropertyType::getJsType($beanProperty->propertyType))
				->setIsNullable(false)
				->addComments(BeanPropertyColKey::UNI->toHumanReadableString() . ' key')
				->setClassFieldEnum(BeanPropertyColKey::UNI->toClassFieldEnum());
		$this->assertSame(
				$fieldBuilder->getPhpFileContent(),
				$beanProperty->getFieldBuilder()->getPhpFileContent(),
		);
		$this->assertSame(
				$fieldBuilder->getJsFileContent(),
				$beanProperty->getFieldBuilder()->getJsFileContent(),
		);
	}

	public function testFieldBuilderSqlComment(): void {
		$beanProperty = new BeanProperty();
		$beanProperty->sqlName = 'test';
		$beanProperty->isNullable = false;
		$beanProperty->propertyType = BeanPropertyType::STRING;
		$beanProperty->sqlComment = 'sqlComment';

		$fieldBuilder = FieldBuilder::create($beanProperty->getName())
				->setPhpType(BeanPropertyType::getPhpType($beanProperty->propertyType))
				->setJsType(BeanPropertyType::getJsType($beanProperty->propertyType))
				->setIsNullable(false)
				->addComments('sqlComment');
		$this->assertSame(
				$fieldBuilder->getPhpFileContent(),
				$beanProperty->getFieldBuilder()->getPhpFileContent(),
		);
		$this->assertSame(
				$fieldBuilder->getJsFileContent(),
				$beanProperty->getFieldBuilder()->getJsFileContent(),
		);
	}


}
