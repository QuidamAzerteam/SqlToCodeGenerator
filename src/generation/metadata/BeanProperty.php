<?php

namespace SqlToCodeGenerator\generation\metadata;

use SqlToCodeGenerator\generation\builder\PhpFieldBuilder;

class BeanProperty {

	public string $sqlName;
	public string $name;
	public string $belongsToClass;
	public bool $isNullable;
	/** @var int {@see BeanPropertyType} */
	public int $propertyType;
	/** @var int|null {@see BeanPropertyColKey} */
	public ?int $columnKey;
	public string|null $enumAsString = null;
	public string|null $defaultValueAsString = null;
	public string|null $sqlComment = null;

	public function getJsDeclaringField(): string {
		if ($this->defaultValueAsString !== null) {
			$defaultValue = $this->defaultValueAsString;
		} else if ($this->isNullable) {
			$defaultValue = 'null';
		} else {
			$defaultValue = null;
		}

		$stringToReturn = "";
		$stringToReturn .=	"/** @type {";
		if ($this->enumAsString) {
			$stringToReturn .= $this->enumAsString;
		} else {
			$stringToReturn .= BeanPropertyType::getJsType($this->propertyType);
		}
		$stringToReturn .= ($this->isNullable ? '|null' : '');
		$stringToReturn .= "} */\n";
		$stringToReturn .= "	$this->name". ($defaultValue ? ' = ' . $defaultValue : '');

		return $stringToReturn;
	}

	public function getUniqueKey(): string {
		return $this->belongsToClass . '_' . $this->name;
	}

	public function getPhpFieldBuilder(): PhpFieldBuilder {
		$fieldBuilder = new PhpFieldBuilder();

		$fieldBuilder->type = $this->enumAsString ?: BeanPropertyType::getPhpType($this->propertyType);
		$fieldBuilder->fieldName = $this->name;
		$fieldBuilder->isNullable = $this->isNullable || $this->columnKey === BeanPropertyColKey::PRI;

		if ($this->defaultValueAsString !== null) {
			$defaultValue = $this->defaultValueAsString;
		} else if ($fieldBuilder->isNullable) {
			$defaultValue = 'null';
		} else {
			$defaultValue = null;
		}
		$fieldBuilder->defaultValue = $defaultValue;

		if ($this->columnKey) {
			$string = BeanPropertyColKey::getAsString($this->columnKey);
			if ($string) {
				$fieldBuilder->comments[] = $string;
			}
		}
		if ($this->sqlComment) {
			$fieldBuilder->comments[] = $this->sqlComment;
		}

		return $fieldBuilder;
	}
}
