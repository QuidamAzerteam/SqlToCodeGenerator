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
			$foreignBeanFieldBuilder = FieldBuilder::create($this->getFieldName())
					->setPhpType('array')
					->setDefaultValue('[]')
					->setCustomTypeHint($this->toBean->getClassName() . '[]');
		} else {
			$foreignBeanFieldBuilder = FieldBuilder::create($this->getFieldName())
					->setPhpType($this->toBean->getClassName())
					->setIsNullable($this->withProperty->isNullable);
		}
		return $foreignBeanFieldBuilder;
	}

	public function getAsFieldBuilderForJs(): FieldBuilder {
		if ($this->isArray) {
			$foreignBeanFieldBuilder = FieldBuilder::create($this->getFieldName())
					->setJsType($this->toBean->getClassName() . '[]');
		} else {
			$foreignBeanFieldBuilder = FieldBuilder::create($this->getFieldName())
					->setJsType($this->toBean->getClassName())
					->setIsNullable($this->withProperty->isNullable);
		}
		return $foreignBeanFieldBuilder;
	}

	public function getFieldName(): string {
		return $this->isArray
				? lcfirst(
						VariableUtils::getPluralOfVarName($this->toBean->getClassName())
						. 'ViaTheir' . ucfirst(SqlDao::sqlToCamelCase($this->onProperty->sqlName))
				)
				: lcfirst(SqlDao::sqlToCamelCase($this->withProperty->getSqlNameWithoutId()));
	}

}
