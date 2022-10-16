<?php

namespace SqlToCodeGenerator\sql;

use RuntimeException;

abstract class SqlUtils {

	private static PdoContainer $pdoContainer;

	private final function __construct() {}

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
			throw new RuntimeException('PDO is null. Call initPdo method first');
		}

		return self::$pdoContainer;
	}

}
