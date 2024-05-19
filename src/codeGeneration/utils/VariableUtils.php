<?php

namespace SqlToCodeGenerator\codeGeneration\utils;

final class VariableUtils {

	public static function getPluralOfVarName($var): string {
		// Some exceptions first
		$exceptionAsPlural = match ($var) {
			'data' => 'dataList',
			'information' => 'informationList',
			'scenario' => 'scenarii',
			default => null,
		};
		if ($exceptionAsPlural !== null) {
			return $exceptionAsPlural;
		}

		$arrayVar = substr($var, 0, -1);
		$lastCharacter = substr($var, -1);
		$previousLastCharacter = substr($var, -2, 1);

		if (in_array($previousLastCharacter, [
			'a', 'e', 'i', 'o', 'u',
		], true)) {
			return $var . 's';
		}

		$arrayVar .= match ($lastCharacter) {
			'y' => 'ies',
			's' => $lastCharacter . 'List',
			default => $lastCharacter . 's',
		};
		return $arrayVar;
	}

	public static function stringToEnumCompliantValue(string $value): string {
		return mb_strtoupper(preg_replace('/\s+/u', '_', $value));
	}

}
