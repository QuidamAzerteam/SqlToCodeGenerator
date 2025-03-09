<?php

namespace SqlToCodeGenerator\common\bean;

use InvalidArgumentException;
use LogicException;
use ReflectionClass;
use SqlToCodeGenerator\codeGeneration\attribute\ClassFieldEnum;
use SqlToCodeGenerator\codeGeneration\metadata\BeanPropertyColKey;

abstract class CommonBean {

	// TODO Add unit tests for this
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

	// TODO Add unit tests for this
	public function __toString(): string {
		static $firstUniqueField = null;

		if ($firstUniqueField === null) {
			$classFields = (new ReflectionClass(static::class))->getProperties();
			foreach ($classFields as $field) {
				$attributes = $field->getAttributes(ClassFieldEnum::class);
				foreach ($attributes as $attribute) {
					if (in_array($attribute->getArguments()[0], [BeanPropertyColKey::PRI, BeanPropertyColKey::UNI], true)) {
						$firstUniqueField = $field;
						break 2;
					}
				}
			}
		}

		if ($firstUniqueField === null) {
			throw new LogicException('No unique field, so no way to uniquely toString the object');
		}


		return (string) $this->$firstUniqueField;
	}
	
}
