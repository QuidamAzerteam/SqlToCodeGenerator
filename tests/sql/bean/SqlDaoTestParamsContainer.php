<?php

namespace SqlToCodeGenerator\test\sql\bean;

use PDO;
use PHPUnit\Framework\MockObject\MockObject;
use SqlToCodeGenerator\sql\PdoContainer;
use SqlToCodeGenerator\sql\SqlDao;
use SqlToCodeGenerator\sql\SqlUtils;

final class SqlDaoTestParamsContainer {

	public const TABLE = 'table';
	public const CLASS_NAME = 'Class';

	public function __construct(
			public readonly MockObject&PdoContainer $pdoContainer,
			public readonly PDO&MockObject $pdo,
			public ?SqlDao $sqlDao,
	) {}

	public static function getWithInit(
			MockObject&PdoContainer $pdoContainer,
			PDO&MockObject $pdo,
			string $table = SqlDaoTestParamsContainer::TABLE,
			string $className = SqlDaoTestParamsContainer::CLASS_NAME,
			?callable $getSqlColFromFieldCallable = null,
	): self {
		SqlUtils::initFromPdoContainer($pdoContainer);
		$pdoContainer->method('getPdo')->willReturn($pdo);

		$getSqlColFromFieldCallable = $getSqlColFromFieldCallable ?? static fn(string $field): string => 'sqlColFromField' . $field;
		return new self(
				$pdoContainer,
				$pdo,
				new class($table, $className, $getSqlColFromFieldCallable) extends SqlDao {

					public function __construct(
							public string $table,
							public string $className,
							public $getSqlColFromFieldCallable,
					) {
						parent::__construct();
					}

					protected function getTable(): string {
						return $this->table;
					}

					protected function getClass(): string {
						return $this->className;
					}

					protected function getSqlColFromField(string $field): string {
						$callable = $this->getSqlColFromFieldCallable;
						return $callable($field);
					}

					public function __destruct() {
						// Do nothing
					}
				},
		);
	}

}
