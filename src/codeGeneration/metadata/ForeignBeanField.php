<?php

namespace SqlToCodeGenerator\codeGeneration\metadata;

use SqlToCodeGenerator\codeGeneration\builder\FieldBuilder;
use SqlToCodeGenerator\codeGeneration\utils\VariableUtils;
use SqlToCodeGenerator\sql\SqlDao;

class ForeignBeanField {

	public Bean $toBean;
	public BeanProperty $withProperty;
	public BeanProperty $onProperty;
	public bool $isArray = false;

	public function getAsFieldBuilderForPhp(): FieldBuilder {
		if ($this->isArray) {
			$foreignBeanFieldBuilder = FieldBuilder::create(VariableUtils::getPluralOfVarName(lcfirst(
					$this->toBean->getClassName()
					. ucfirst($this->onProperty->getName($this->withProperty->sqlName))
			)))
					->setPhpType('array')
					->setDefaultValue('[]')
					->setCustomTypeHint($this->toBean->getClassName() . '[]');
		} else {
			$fieldName = lcfirst(SqlDao::sqlToCamelCase($this->withProperty->getSqlNameWithoutId()));
			$foreignBeanFieldBuilder = FieldBuilder::create($fieldName)
					->setPhpType($this->toBean->getClassName())
					->setIsNullable($this->withProperty->isNullable);
		}
		return $foreignBeanFieldBuilder;
	}

	public function getAsFieldBuilderForJs(): FieldBuilder {
		if ($this->isArray) {
			$fieldName = VariableUtils::getPluralOfVarName(lcfirst($this->toBean->getClassName()));
			$foreignBeanFieldBuilder = FieldBuilder::create($fieldName)
					->setJsType($this->toBean->getClassName() . '[]');
		} else {
			$fieldName = lcfirst(SqlDao::sqlToCamelCase($this->withProperty->getSqlNameWithoutId()));
			$foreignBeanFieldBuilder = FieldBuilder::create($fieldName)
					->setJsType($this->toBean->getClassName())
					->setIsNullable($this->withProperty->isNullable);
		}
		return $foreignBeanFieldBuilder;
	}

}
