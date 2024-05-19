<?php

namespace SqlToCodeGenerator\codeGeneration\utils;

use LogicException;

final class CheckUtils {

	public static function checkPhpFullNamespace(string $fullNamespace): void {
		if ($fullNamespace === '\\') {
			throw new LogicException('If does not make sense to have an empty namespace');
		}
		if (preg_match(
				'/^((?:\w+|\w+\\\\)(?:\w+\\\\?)+)$/u',
				$fullNamespace,
		) !== 1) {
			throw new LogicException(htmlentities($fullNamespace) . ' does not match a valid PHP namespace');
		}
	}

	public static function checkPhpFieldName(string $fieldName): void {
		if ($fieldName === '') {
			throw new LogicException('If does not make sense to have an empty field name');
		}
		if ($fieldName === 'this') {
			throw new LogicException('$this is a special variable that cannot be assigned');
		}
		// Regex taken from https://www.php.net/manual/en/language.variables.basics.php
		if (preg_match(
				'/^[a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*$/',
				$fieldName,
		) !== 1) {
			throw new LogicException(htmlentities($fieldName) . ' does not match a valid PHP variable');
		}
		// And if a var name is valid for PHP, it is for JS too
	}

	public static function checkPhpType(string $phpType): void {
		if ($phpType === 'class') {
			throw new LogicException('class is a keyword');
		}
		if (str_starts_with($phpType, '$')) {
			throw new LogicException('You cannot start a php type with $');
		}
		// Regex taken from https://stackoverflow.com/a/12011255/5649527
		if ($phpType !== '' && preg_match(
				'/^[\\\\]?[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*(\\\\[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)*$/',
				$phpType,
		) !== 1) {
			throw new LogicException(htmlentities($phpType) . ' does not match a valid PHP variable');
		}
	}

	public static function checkUniqueFields(array $fields): void {
		foreach ($fields as $field) {
			if (!is_string($field) && !is_int($field)) {
				throw new LogicException('A field must be a string or an integer');
			}
			if ($field === '') {
				throw new LogicException('A field cannot be an empty string');
			}
		}
		$duplicates = [];
		foreach (array_count_values($fields) as $value => $count) {
			if ($count > 1) {
				$duplicates[] = $value;
			}
		}
		if ($duplicates) {
			throw new LogicException('Duplicates in array found: '
					. implode(', ', $duplicates));
		}
	}

	/**
	 * @param mixed $value T
	 * @param string $className class-string&lt;T&gt;
	 * @return void
	 */
	public static function checkIfValueIsAClass(mixed $value, string $className): void {
		if (!is_object($value)) {
			throw new LogicException('Value is not an object therefore cannot belong to a class');
		}
		if (!is_a($value, $className)) {
			throw new LogicException('Value does not belong to class ("'
					. get_class($value) . '" given vs "' . $className . '" expected)');
		}
	}

}
