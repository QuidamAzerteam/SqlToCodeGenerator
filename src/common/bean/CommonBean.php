<?php

namespace SqlToCodeGenerator\common\bean;

use InvalidArgumentException;
use LogicException;
use ReflectionClass;
use SqlToCodeGenerator\codeGeneration\attribute\ClassField;
use SqlToCodeGenerator\codeGeneration\attribute\ClassFieldEnum;

abstract class CommonBean {

	public function castAsChildClass(string $newClass): mixed {
		if (!is_subclass_of($newClass, static::class)) {
			throw new InvalidArgumentException('Can\'t change class hierarchy, you must cast to a child class');
		}
		$obj = new $newClass;
		foreach (get_object_vars($this) as $key => $name) {
			$obj->$key = $name;
		}
		return $obj;
	}

	public function __toString(): string {
		static $firstUniqueFieldPropertyByClassName = [];

		$firstUniqueFieldPropertyName = $firstUniqueFieldPropertyByClassName[static::class] ?? null;
		if ($firstUniqueFieldPropertyName === null) {
			$classFields = (new ReflectionClass(static::class))->getProperties();
			foreach ($classFields as $field) {
				$attributes = $field->getAttributes(ClassField::class);
				foreach ($attributes as $attribute) {
					if (in_array($attribute->getArguments()[0], [ClassFieldEnum::PRIMARY, ClassFieldEnum::UNIQUE], true)) {
						$firstUniqueFieldPropertyName = $field->name;
						break 2;
					}
				}
			}
		}

		if ($firstUniqueFieldPropertyName === null) {
			throw new LogicException('No unique field, so no way to uniquely toString the object');
		}
		$firstUniqueFieldPropertyByClassName[static::class] = $firstUniqueFieldPropertyName;

		return (string) $this->$firstUniqueFieldPropertyName;
	}
	
}
