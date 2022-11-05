<?php

namespace SqlToCodeGenerator\codeGeneration\metadata;

use SqlToCodeGenerator\codeGeneration\builder\FieldBuilder;
use SqlToCodeGenerator\sql\SqlDao;
use SqlToCodeGenerator\sqlToMetaCode\bean\Column;

class BeanProperty {

	public string $sqlName;
	public Bean $belongsToBean;
	public bool $isNullable;
	public BeanPropertyType $propertyType;
	public ?BeanPropertyColKey $columnKey;
	public Enum|null $enum = null;
	public string|null $defaultValueAsString = null;
	public string|null $sqlComment = null;

	public function getName(): string {
		return lcfirst(SqlDao::sqlToCamelCase($this->sqlName));
	}

	public function getJsDeclaringField(): string {
		if ($this->defaultValueAsString !== null) {
			$defaultValue = $this->defaultValueAsString;
		} else if ($this->isNullable) {
			$defaultValue = 'null';
		} else {
			$defaultValue = null;
		}

		$stringToReturn = "/** @type {";
		if ($this->enum) {
			$stringToReturn .= $this->enum->getFullName();
		} else {
			$stringToReturn .= BeanPropertyType::getJsType($this->propertyType);
		}
		$stringToReturn .= ($this->isNullable ? '|null' : '');
		$stringToReturn .= "} */\n";
		$stringToReturn .= "	{$this->getName()}". ($defaultValue ? ' = ' . $defaultValue : '');

		return $stringToReturn;
	}

	public function getUniqueKey(): string {
		return $this->belongsToBean->sqlTable . '_' . $this->sqlName;
	}

	public function getFieldBuilder(): FieldBuilder {
		$fieldBuilder = FieldBuilder::create($this->getName())
				->setPhpType($this->enum ? $this->enum->getFullName() : BeanPropertyType::getPhpType($this->propertyType))
				->setJsType($this->enum ? $this->enum->getFullName() : BeanPropertyType::getJsType($this->propertyType))
				->setIsNullable($this->isNullable || $this->columnKey === BeanPropertyColKey::PRI)
				->setClassFieldEnum($this->columnKey?->toClassFieldEnum());

		if ($this->defaultValueAsString !== null) {
			$defaultValue = $this->defaultValueAsString;
		} else if ($fieldBuilder->isNullable()) {
			$defaultValue = 'null';
		} else {
			$defaultValue = null;
		}
		$fieldBuilder->setDefaultValue($defaultValue);

		if ($this->columnKey) {
			$string = BeanPropertyColKey::getAsString($this->columnKey);
			if ($string) {
				$fieldBuilder->addComments($string);
			}
		}
		if ($this->sqlComment) {
			$fieldBuilder->addComments($this->sqlComment);
		}

		return $fieldBuilder;
	}

}
