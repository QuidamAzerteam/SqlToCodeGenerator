<?php

namespace SqlToCodeGenerator\sql;

use PDO;
use PDOStatement;

readonly class SqlPrepareStatement {

	public function __construct(public PDOStatement $statement) {}

	public function bind(string|int $param, mixed $value, int $type = PDO::PARAM_STR): self {
		$this->statement->bindValue($param, $value, $type);
		return $this;
	}

	public function executeAndFetch(int $mode = PDO::FETCH_DEFAULT): array {
		$this->statement->execute();
		return $this->statement->fetchAll($mode);
	}
}
