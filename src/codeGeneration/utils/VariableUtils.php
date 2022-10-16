<?php

namespace SqlToCodeGenerator\codeGeneration\utils;

abstract class VariableUtils {

	private final function __construct() {}

	public static function getPluralOfVarName($var): string {
		$arrayVar = substr($var, 0, -1);
		$lastCharacter = substr($var, -1);
		$previousLastCharacter = substr($var, -2, 1);

		if (in_array($previousLastCharacter, array(
			'a', 'e', 'i', 'o', 'u'
		), true)) {
			return $var . 's';
		}

		$arrayVar .= match ($lastCharacter) {
			'y' => 'ies',
			's' => $lastCharacter . 'List',
			default => $lastCharacter . 's',
		};
		return $arrayVar;
	}

}
