<?php

namespace SqlToCodeGenerator\sql;

use DateTime;
use PDO;

/**
 * Using the getPdo method will allow reconnexion if `wait_timeout` requires it
 * @link https://dev.mysql.com/doc/refman/8.0/en/server-system-variables.html#sysvar_wait_timeout
 */
class PdoContainer {

	private PDO $pdo;
	private DateTime $lastPdoConnexionDateTime;
	private int $waitTimeout;

	public function __construct(
			private readonly string $dbName,
			private readonly string $host,
			private readonly string $port,
			private readonly string $user,
			private readonly string $password
	) {}

	public function getPdo(): PDO {
		if (
				!isset($this->pdo)
				|| !isset($this->lastPdoConnexionDateTime)
				|| $this->lastPdoConnexionDateTime->diff(new DateTime())->s > $this->waitTimeout
		) {
			$dsnAsArray = array(
				'dbname' => $this->dbName,
				'host' => $this->host,
				'port' => $this->port,
				'charset' => 'utf8',
			);
			$dsn = implode(';', array_map(static function (string $key, string $value) {
				return $key . '=' . $value;
			}, array_keys($dsnAsArray), $dsnAsArray));

			$this->pdo = new PDO(
					'mysql:' . $dsn,
					$this->user,
					$this->password,
					array(
						PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
					),
			);
			$result = $this->pdo->query("SHOW VARIABLES LIKE 'wait_timeout'")->fetchAll(PDO::FETCH_ASSOC);
			$this->waitTimeout = (int) $result[0]['Value'];
			$this->lastPdoConnexionDateTime = new DateTime();
		}
		return $this->pdo;
	}

}
