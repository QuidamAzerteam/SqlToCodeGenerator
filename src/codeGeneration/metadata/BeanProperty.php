<?php

namespace SqlToCodeGenerator\codeGeneration\metadata;

use SqlToCodeGenerator\codeGeneration\attribute\ClassFieldEnum;
use SqlToCodeGenerator\codeGeneration\builder\FieldBuilder;
use SqlToCodeGenerator\sql\SqlDao;

class BeanProperty {

	public string $sqlName;
	public Bean $belongsToBean;
	public bool $isNullable;
	public BeanPropertyType $propertyType;
	public ?BeanPropertyColKey $columnKey = null;
	public Enum|null $enum = null;
	/** @var Enum[] */
	public array $enums = [];
	public string|null $defaultValueAsString = null;
	public string|null $sqlComment = null;
	public bool $isGenerated = false;

	public function getName(?string $sqlPartToRemove = null): string {
		$sqlName = $this->sqlName;
		if ($sqlPartToRemove !== null) {
			$sqlName = str_replace($sqlPartToRemove . '_', '', $sqlName);
		}
		return lcfirst(SqlDao::sqlToCamelCase($sqlName));
	}

	public function getSqlNameWithoutId(): string {
		return str_replace('id_', '',$this->sqlName);
	}

	public function getUniqueKey(): string {
		return $this->belongsToBean->getUniqueIdentifier() . '_' . $this->sqlName;
	}

	public function getFieldBuilder(): FieldBuilder {
		$fieldBuilder = FieldBuilder::create($this->getName())
				->setPhpType($this->enum ? $this->enum->getFullName() : BeanPropertyType::getPhpType($this->propertyType))
				// TODO Better enum js handling
				->setJsType($this->enum ? '*' : BeanPropertyType::getJsType($this->propertyType))
				->setIsNullable($this->isNullable || $this->columnKey === BeanPropertyColKey::PRI)
				->addClassFieldEnums($this->columnKey?->toClassFieldEnum());

		if ($this->enums) {
			$fieldBuilder->setCustomTypeHint("{$this->enums[0]->getFullNamespace()}[]");
		}

		if ($this->defaultValueAsString !== null) {
			$defaultValue = $this->defaultValueAsString;
		} else if ($fieldBuilder->isNullable()) {
			$defaultValue = 'null';
		} else {
			$defaultValue = null;
		}
		$fieldBuilder->setDefaultValue($defaultValue);

		if ($this->columnKey === BeanPropertyColKey::MUL) {
			$fieldBuilder->addComments($this->columnKey->toHumanReadableString() . ' key');
		}
		if ($this->sqlComment) {
			$fieldBuilder->addComments($this->sqlComment);
			if (str_contains($this->sqlComment, 'ImmutableAttribute')) {
				$fieldBuilder->addClassFieldEnums(ClassFieldEnum::IMMUTABLE);
			}
		}
		if ($this->isGenerated) {
			$fieldBuilder->addClassFieldEnums(ClassFieldEnum::GENERATED);
		}

		return $fieldBuilder;
	}

}
