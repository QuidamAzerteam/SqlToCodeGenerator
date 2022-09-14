<?php

namespace SqlToCodeGenerator\sql;

use RuntimeException;

class SqlUtils {

	private static PdoContainer $pdoContainer;

	private function __construct() {}

	public static function initPdo(
			string $dbName,
			string $host,
			string $port,
			string $user,
			string $password
	): void {
		self::$pdoContainer = new PdoContainer($dbName, $host, $port, $user, $password);
	}

	public static function getPdoContainer(): PdoContainer {
		if (!isset(self::$pdoContainer)) {
			throw new RuntimeException('PDO is null. Call init method first');
		}

		return self::$pdoContainer;
	}

	public static function getArrayVarAsString($var): string {
		$arrayVar = substr($var, 0, -1);
		$lastCharacter = substr($var, -1);
		$arrayVar .= match ($lastCharacter) {
			'y' => 'ies',
			's' => $lastCharacter . 'List',
			default => $lastCharacter . 's',
		};
		return $arrayVar;
	}

}
