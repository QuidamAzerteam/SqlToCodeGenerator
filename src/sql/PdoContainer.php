<?php

namespace SqlToCodeGenerator\sql;

use DateTime;
use PDO;

/**
 * Using the getPdo method will allow reconnection if `wait_timeout` requires it
 * @link https://dev.mysql.com/doc/refman/8.0/en/server-system-variables.html#sysvar_wait_timeout
 *
 * This class is not tested nor covered because it requires a PDO connection
 */
class PdoContainer {

	private PDO $pdo;
	private DateTime $lastPdoConnexionDateTime;

	public function __construct(
			private readonly string $dbName,
			private readonly string $host,
			private readonly string $port,
			private readonly string $user,
			private readonly string $password,
			private readonly int $waitTimeout,
	) {}

	public function getPdo(): PDO {
		if (
				!isset($this->pdo)
				|| !isset($this->lastPdoConnexionDateTime)
				|| $this->lastPdoConnexionDateTime->diff(new DateTime())->s > $this->waitTimeout
		) {
			$dsnAsArray = [
				'dbname' => $this->dbName,
				'host' => $this->host,
				'port' => $this->port,
				'charset' => 'utf8',
			];
			$dsn = implode(';', array_map(static function (string $key, string $value) {
				return $key . '=' . $value;
			}, array_keys($dsnAsArray), $dsnAsArray));

			$this->pdo = new PDO(
					'mysql:' . $dsn,
					$this->user,
					$this->password,
					[
						PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
					],
			);
			$this->pdo->exec("SET session wait_timeout=$this->waitTimeout;");
			$this->lastPdoConnexionDateTime = new DateTime();
		}
		return $this->pdo;
	}

}
