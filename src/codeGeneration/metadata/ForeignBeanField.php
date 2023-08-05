<?php

namespace SqlToCodeGenerator\codeGeneration\metadata;

use SqlToCodeGenerator\codeGeneration\builder\FieldBuilder;
use SqlToCodeGenerator\codeGeneration\utils\VariableUtils;

class ForeignBeanField {

	public Bean $toBean;
	public BeanProperty $withProperty;
	public BeanProperty $onProperty;
	public bool $isArray = false;

	public function getAsFieldBuilderForPhp(): FieldBuilder {
		$var = lcfirst($this->toBean->getClassName());

		if ($this->isArray) {
			$foreignBeanFieldBuilder = FieldBuilder::create(VariableUtils::getPluralOfVarName($var))
					->setPhpType('array')
					->setDefaultValue('[]')
					->setCustomTypeHint($this->toBean->getClassName() . '[]');
		} else {
			$foreignBeanFieldBuilder = FieldBuilder::create($var)
					->setPhpType($this->toBean->getClassName());
		}
		return $foreignBeanFieldBuilder;
	}

	public function getAsFieldBuilderForJs(): FieldBuilder {
		$var = lcfirst($this->toBean->getClassName());

		if ($this->isArray) {
			$foreignBeanFieldBuilder = FieldBuilder::create(VariableUtils::getPluralOfVarName($var))
					->setJsType($this->toBean->getClassName() . '[]');
		} else {
			$foreignBeanFieldBuilder = FieldBuilder::create($var)->setJsType($this->toBean->getClassName());
		}
		return $foreignBeanFieldBuilder;
	}

}
