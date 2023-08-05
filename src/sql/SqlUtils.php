<?php

namespace SqlToCodeGenerator\sql;

use RuntimeException;

abstract class SqlUtils {

	private static PdoContainer $pdoContainer;

	private final function __construct() {}

	public static function initFromScratch(
			string $dbName,
			string $host,
			string $port,
			string $user,
			string $password,
	): void {
		self::$pdoContainer = new PdoContainer($dbName, $host, $port, $user, $password);
	}

	public static function initFromPdoContainer(PdoContainer $pdoContainer): void {
		self::$pdoContainer = $pdoContainer;
	}

	public static function getPdoContainer(): PdoContainer {
		if (!isset(self::$pdoContainer)) {
			throw new RuntimeException('PDO is null. Call an init method method first');
		}

		return self::$pdoContainer;
	}

}
