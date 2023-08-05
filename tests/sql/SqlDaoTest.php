<?php

namespace SqlToCodeGenerator\test\sql;

use DateTime;
use LogicException;
use PDO;
use PDOStatement;
use PHPUnit\Framework\TestCase;
use SqlToCodeGenerator\sql\PdoContainer;
use SqlToCodeGenerator\sql\SqlDao;
use SqlToCodeGenerator\test\exception\ExpectedException;
use SqlToCodeGenerator\test\sql\bean\SqlDaoTestBackedEnum;
use SqlToCodeGenerator\test\sql\bean\SqlDaoTestCompleteClass;
use SqlToCodeGenerator\test\sql\bean\SqlDaoTestParamsContainer;
use SqlToCodeGenerator\test\sql\bean\SqlDaoTestWithoutPrimaryField;
use stdClass;

class SqlDaoTest extends TestCase {

	private function getParamsContainer(
			string $table = SqlDaoTestParamsContainer::TABLE,
			string $className = SqlDaoTestParamsContainer::CLASS_NAME,
			?callable $getSqlColFromFieldCallable = null,
	): SqlDaoTestParamsContainer {
		return SqlDaoTestParamsContainer::getWithInit(
				$this->createMock(PdoContainer::class),
				$this->createMock(Pdo::class),
				$table,
				$className,
				$getSqlColFromFieldCallable,
		);
	}

	public function testStartTransaction(): void {
		$paramsContainer = $this->getParamsContainer();
		$paramsContainer->pdo->method('beginTransaction')->willThrowException(new ExpectedException('beginTransaction'));

		$this->expectException(ExpectedException::class);
		$this->expectExceptionMessage('beginTransaction');
		$paramsContainer->sqlDao->startTransaction();
	}

	public function testEndTransactionInTransaction(): void {
		$paramsContainer = $this->getParamsContainer();
		$paramsContainer->pdo->method('inTransaction')->willThrowException(new ExpectedException('inTransaction'));

		try {
			$paramsContainer->sqlDao->endTransaction();
			$this->fail('Expected ExpectedException');
		} catch (ExpectedException $e) {
			$this->assertSame('inTransaction', $e->getMessage());
		}
	}

	public function testEndTransactionInTransactionCommit(): void {
		$paramsContainer = $this->getParamsContainer();
		$paramsContainer->pdo->method('inTransaction')->willReturn(true);
		$paramsContainer->pdo->method('commit')->willThrowException(new ExpectedException('commit'));

		try {
			$paramsContainer->sqlDao->endTransaction();
			$this->fail('Expected ExpectedException');
		} catch (ExpectedException $e) {
			$this->assertSame('commit', $e->getMessage());
		}
	}

	public function testEndTransactionInTransactionOut(): void {
		$paramsContainer = $this->getParamsContainer();
		$paramsContainer->pdo->method('inTransaction')->willReturn(false);

		$paramsContainer->sqlDao->endTransaction();
		$this->assertTrue(true);
	}

	public function testCancelTransactionInTransaction(): void {
		$paramsContainer = $this->getParamsContainer();
		$paramsContainer->pdo->method('inTransaction')->willThrowException(new ExpectedException('inTransaction'));

		try {
			$paramsContainer->sqlDao->cancelTransaction();
			$this->fail('Expected ExpectedException');
		} catch (ExpectedException $e) {
			$this->assertSame('inTransaction', $e->getMessage());
		}
	}

	public function testCancelTransactionInTransactionCommit(): void {
		$paramsContainer = $this->getParamsContainer();
		$paramsContainer->pdo->method('inTransaction')->willReturn(true);
		$paramsContainer->pdo->method('rollBack')->willThrowException(new ExpectedException('rollBack'));

		try {
			$paramsContainer->sqlDao->cancelTransaction();
			$this->fail('Expected ExpectedException');
		} catch (ExpectedException $e) {
			$this->assertSame('rollBack', $e->getMessage());
		}
	}

	public function testCancelTransactionInTransactionOut(): void {
		$paramsContainer = $this->getParamsContainer();
		$paramsContainer->pdo->method('inTransaction')->willReturn(false);

		$paramsContainer->sqlDao->cancelTransaction();
		$this->assertTrue(true);
	}

	public function testDeleteData(): void {
		$paramsContainer = $this->getParamsContainer();
		$paramsContainer->pdo->method('exec')
				->with('DELETE FROM table WHERE test')
				->willReturn(-1);

		$this->assertSame(-1, $paramsContainer->sqlDao->deleteData('test'));
	}

	public function testQuote(): void {
		$paramsContainer = $this->getParamsContainer();
		$paramsContainer->pdo->method('quote')
				->with('test')
				->willReturn('`test`');

		$this->assertSame('`test`', $paramsContainer->sqlDao->quote('test'));
	}

	public function testFoundRows(): void {
		$paramsContainer = $this->getParamsContainer();

		$pdoStatement = $this->createMock(PDOStatement::class);
		$pdoStatement->method('fetchColumn')->willReturn(-1);
		$paramsContainer->pdo->method('query')
				->with('SELECT FOUND_ROWS()')
				->willReturn($pdoStatement);

		$this->assertSame(-1, $paramsContainer->sqlDao->foundRows());
	}

	public function testLastInsertedId(): void {
		$paramsContainer = $this->getParamsContainer();

		$paramsContainer->pdo->method('lastInsertId')->willReturn('-1');

		$this->assertSame('-1', $paramsContainer->sqlDao->lastInsertedId());
	}

	public function testFetchFromQuery(): void {
		$paramsContainer = $this->getParamsContainer();

		$pdoStatement = $this->createMock(PDOStatement::class);
		$pdoStatement->method('fetchAll')->willReturn([-1]);
		$paramsContainer->pdo->method('query')
				->with('test', PDO::FETCH_ASSOC)
				->willReturn($pdoStatement);

		$this->assertSame([-1], $paramsContainer->sqlDao->fetchFromQuery('test'));
	}

	public function testGetQueryEmpty(): void {
		$paramsContainer = $this->getParamsContainer();

		$pdoStatement = $this->createMock(PDOStatement::class);
		$pdoStatement->method('fetchAll')->willReturn([]);
		$table = SqlDaoTestParamsContainer::TABLE;
		$paramsContainer->pdo
				->method('query')->with(
						"SELECT SQL_CALC_FOUND_ROWS * FROM `{$table}`",
						PDO::FETCH_ASSOC,
				)->willThrowException(new ExpectedException('emptyQuery'));

		try {
			$paramsContainer->sqlDao->get();
			$this->fail('Expected ExpectedException');
		} catch (ExpectedException $e) {
			$this->assertSame('emptyQuery', $e->getMessage());
		}
	}

	public function testGetOnlyWhere(): void {
		$paramsContainer = $this->getParamsContainer();

		$pdoStatement = $this->createMock(PDOStatement::class);
		$pdoStatement->method('fetchAll')->willReturn([]);
		$table = SqlDaoTestParamsContainer::TABLE;
		$paramsContainer->pdo
				->method('query')->with(
						"SELECT SQL_CALC_FOUND_ROWS * FROM `{$table}`\nWHERE where",
						PDO::FETCH_ASSOC,
				)->willThrowException(new ExpectedException('onlyWhere'));

		try {
			$paramsContainer->sqlDao->get(where: 'where');
			$this->fail('Expected ExpectedException');
		} catch (ExpectedException $e) {
			$this->assertSame('onlyWhere', $e->getMessage());
		}
	}

	public function testGetOnlyGroupBy(): void {
		$paramsContainer = $this->getParamsContainer();

		$pdoStatement = $this->createMock(PDOStatement::class);
		$pdoStatement->method('fetchAll')->willReturn([]);
		$table = SqlDaoTestParamsContainer::TABLE;
		$paramsContainer->pdo
				->method('query')->with(
						"SELECT SQL_CALC_FOUND_ROWS * FROM `{$table}`\nGROUP BY group by",
						PDO::FETCH_ASSOC,
				)->willThrowException(new ExpectedException('onlyGroupBy'));

		try {
			$paramsContainer->sqlDao->get(groupBy: 'group by');
			$this->fail('Expected ExpectedException');
		} catch (ExpectedException $e) {
			$this->assertSame('onlyGroupBy', $e->getMessage());
		}
	}

	public function testGetOnlyOrderBy(): void {
		$paramsContainer = $this->getParamsContainer();

		$pdoStatement = $this->createMock(PDOStatement::class);
		$pdoStatement->method('fetchAll')->willReturn([]);
		$table = SqlDaoTestParamsContainer::TABLE;
		$paramsContainer->pdo
				->method('query')->with(
						"SELECT SQL_CALC_FOUND_ROWS * FROM `{$table}`\nORDER BY order by",
						PDO::FETCH_ASSOC,
				)->willThrowException(new ExpectedException('onlyOrderBy'));

		try {
			$paramsContainer->sqlDao->get(orderBy: 'order by');
			$this->fail('Expected ExpectedException');
		} catch (ExpectedException $e) {
			$this->assertSame('onlyOrderBy', $e->getMessage());
		}
	}

	public function testGetOnlyLimit(): void {
		$paramsContainer = $this->getParamsContainer();

		$pdoStatement = $this->createMock(PDOStatement::class);
		$pdoStatement->method('fetchAll')->willReturn([]);
		$table = SqlDaoTestParamsContainer::TABLE;
		$paramsContainer->pdo
				->method('query')->with(
						"SELECT SQL_CALC_FOUND_ROWS * FROM `{$table}`\nLIMIT limit",
						PDO::FETCH_ASSOC,
				)->willThrowException(new ExpectedException('onlyLimit'));

		try {
			$paramsContainer->sqlDao->get(limit: 'limit');
			$this->fail('Expected ExpectedException');
		} catch (ExpectedException $e) {
			$this->assertSame('onlyLimit', $e->getMessage());
		}
	}

	public function testGetWhereBeforeAll(): void {
		$paramsContainer = $this->getParamsContainer();

		$pdoStatement = $this->createMock(PDOStatement::class);
		$pdoStatement->method('fetchAll')->willReturn([]);
		$table = SqlDaoTestParamsContainer::TABLE;
		$paramsContainer->pdo
				->method('query')->with(
						"SELECT SQL_CALC_FOUND_ROWS * FROM `{$table}`\nWHERE where\nGROUP BY group by\nORDER BY order by\nLIMIT limit",
						PDO::FETCH_ASSOC,
				)->willThrowException(new ExpectedException('whereBeforeAll'));

		try {
			$paramsContainer->sqlDao->get(
					where: 'where',
					groupBy: 'group by',
					orderBy: 'order by',
					limit: 'limit',
			);
			$this->fail('Expected ExpectedException');
		} catch (ExpectedException $e) {
			$this->assertSame('whereBeforeAll', $e->getMessage());
		}
	}

	public function testGetWhereBeforeGroupBy(): void {
		$paramsContainer = $this->getParamsContainer();

		$pdoStatement = $this->createMock(PDOStatement::class);
		$pdoStatement->method('fetchAll')->willReturn([]);
		$table = SqlDaoTestParamsContainer::TABLE;
		$paramsContainer->pdo
				->method('query')->with(
						"SELECT SQL_CALC_FOUND_ROWS * FROM `{$table}`\nWHERE where\nGROUP BY group by",
						PDO::FETCH_ASSOC,
				)->willThrowException(new ExpectedException('whereBeforeGroupBy'));

		try {
			$paramsContainer->sqlDao->get(where: 'where', groupBy: 'group by');
			$this->fail('Expected ExpectedException');
		} catch (ExpectedException $e) {
			$this->assertSame('whereBeforeGroupBy', $e->getMessage());
		}
	}

	public function testGetWhereBeforeOrderBy(): void {
		$paramsContainer = $this->getParamsContainer();

		$pdoStatement = $this->createMock(PDOStatement::class);
		$pdoStatement->method('fetchAll')->willReturn([]);
		$table = SqlDaoTestParamsContainer::TABLE;
		$paramsContainer->pdo
				->method('query')->with(
						"SELECT SQL_CALC_FOUND_ROWS * FROM `{$table}`\nWHERE where\nORDER BY order by",
						PDO::FETCH_ASSOC,
				)->willThrowException(new ExpectedException('whereBeforeOrderBy'));

		try {
			$paramsContainer->sqlDao->get(where: 'where', orderBy: 'order by');
			$this->fail('Expected ExpectedException');
		} catch (ExpectedException $e) {
			$this->assertSame('whereBeforeOrderBy', $e->getMessage());
		}
	}

	public function testGetWhereBeforeLimit(): void {
		$paramsContainer = $this->getParamsContainer();

		$pdoStatement = $this->createMock(PDOStatement::class);
		$pdoStatement->method('fetchAll')->willReturn([]);
		$table = SqlDaoTestParamsContainer::TABLE;
		$paramsContainer->pdo
				->method('query')->with(
						"SELECT SQL_CALC_FOUND_ROWS * FROM `{$table}`\nWHERE where\nLIMIT limit",
						PDO::FETCH_ASSOC,
				)->willThrowException(new ExpectedException('whereBeforeLimit'));

		try {
			$paramsContainer->sqlDao->get(where: 'where', limit: 'limit');
			$this->fail('Expected ExpectedException');
		} catch (ExpectedException $e) {
			$this->assertSame('whereBeforeLimit', $e->getMessage());
		}
	}

	public function testGetGroupByBeforeOrderBy(): void {
		$paramsContainer = $this->getParamsContainer();

		$pdoStatement = $this->createMock(PDOStatement::class);
		$pdoStatement->method('fetchAll')->willReturn([]);
		$table = SqlDaoTestParamsContainer::TABLE;
		$paramsContainer->pdo
				->method('query')->with(
						"SELECT SQL_CALC_FOUND_ROWS * FROM `{$table}`\nGROUP BY group by\nORDER BY order by",
						PDO::FETCH_ASSOC,
				)->willThrowException(new ExpectedException('groupByBeforeOrderBy'));

		try {
			$paramsContainer->sqlDao->get(groupBy: 'group by', orderBy: 'order by');
			$this->fail('Expected ExpectedException');
		} catch (ExpectedException $e) {
			$this->assertSame('groupByBeforeOrderBy', $e->getMessage());
		}
	}

	public function testGetGroupByBeforeLimit(): void {
		$paramsContainer = $this->getParamsContainer();

		$pdoStatement = $this->createMock(PDOStatement::class);
		$pdoStatement->method('fetchAll')->willReturn([]);
		$table = SqlDaoTestParamsContainer::TABLE;
		$paramsContainer->pdo
				->method('query')->with(
						"SELECT SQL_CALC_FOUND_ROWS * FROM `{$table}`\nGROUP BY group by\nLIMIT limit",
						PDO::FETCH_ASSOC,
				)->willThrowException(new ExpectedException('groupByBeforeLimit'));

		try {
			$paramsContainer->sqlDao->get(groupBy: 'group by', limit: 'limit');
			$this->fail('Expected ExpectedException');
		} catch (ExpectedException $e) {
			$this->assertSame('groupByBeforeLimit', $e->getMessage());
		}
	}

	public function testGetOrderByBeforeLimit(): void {
		$paramsContainer = $this->getParamsContainer();

		$pdoStatement = $this->createMock(PDOStatement::class);
		$pdoStatement->method('fetchAll')->willReturn([]);
		$table = SqlDaoTestParamsContainer::TABLE;
		$paramsContainer->pdo
				->method('query')->with(
						"SELECT SQL_CALC_FOUND_ROWS * FROM `{$table}`\nORDER BY order by\nLIMIT limit",
						PDO::FETCH_ASSOC,
				)->willThrowException(new ExpectedException('orderByBeforeLimit'));

		try {
			$paramsContainer->sqlDao->get(orderBy: 'order by', limit: 'limit');
			$this->fail('Expected ExpectedException');
		} catch (ExpectedException $e) {
			$this->assertSame('orderByBeforeLimit', $e->getMessage());
		}
	}

	public function testGetResults(): void {
		$paramsContainer = $this->getParamsContainer(
				className: SqlDaoTestCompleteClass::class,
		);

		$pdoStatement = $this->createMock(PDOStatement::class);
		$pdoRow = [
			'bool' => true,
			'float' => 1.0,
			'int' => 1,
			'string' => 'string',
			'dateTime' => (new DateTime())->format('Y-m-d H:i:s'),
			'sqlDaoTestBackedEnum' => SqlDaoTestBackedEnum::HELLO->name,
		];
		$pdoStatement->method('fetchAll')->willReturn([$pdoRow]);
		$paramsContainer->pdo->method('query')->willReturn($pdoStatement);

		$results = $paramsContainer->sqlDao->get();
		$this->assertCount(1, $results);
		/** @var SqlDaoTestCompleteClass $result */
		$result = $results[0];
		$this->assertSame($pdoRow['bool'], $result->bool);
		$this->assertSame($pdoRow['float'], $result->float);
		$this->assertSame($pdoRow['int'], $result->int);
		$this->assertSame($pdoRow['string'], $result->string);
		$this->assertSame($pdoRow['dateTime'], $result->dateTime->format('Y-m-d H:i:s'));
		$this->assertSame($pdoRow['sqlDaoTestBackedEnum'], $result->sqlDaoTestBackedEnum->name);
		$this->assertSame(SqlDaoTestBackedEnum::HELLO, $result->sqlDaoTestBackedEnum);
	}

	public function testGetFailsArray(): void {
		$paramsContainer = $this->getParamsContainer(
				className: SqlDaoTestCompleteClass::class,
		);

		$pdoStatement = $this->createMock(PDOStatement::class);
		$pdoRow = ['array' => [1]];
		$pdoStatement->method('fetchAll')->willReturn([$pdoRow]);
		$paramsContainer->pdo->method('query')->willReturn($pdoStatement);

		$this->expectException(LogicException::class);
		$paramsContainer->sqlDao->get();
	}

	public function testGetFailsCallable(): void {
		$paramsContainer = $this->getParamsContainer(
				className: SqlDaoTestCompleteClass::class,
		);

		$pdoStatement = $this->createMock(PDOStatement::class);
		$pdoRow = ['callable' => static fn() => 'hello'];
		$pdoStatement->method('fetchAll')->willReturn([$pdoRow]);
		$paramsContainer->pdo->method('query')->willReturn($pdoStatement);

		$this->expectException(LogicException::class);
		$paramsContainer->sqlDao->get();
	}

	public function testGetFailsIterable(): void {
		$paramsContainer = $this->getParamsContainer(
				className: SqlDaoTestCompleteClass::class,
		);

		$pdoStatement = $this->createMock(PDOStatement::class);
		$pdoRow = ['iterable' => [1]];
		$pdoStatement->method('fetchAll')->willReturn([$pdoRow]);
		$paramsContainer->pdo->method('query')->willReturn($pdoStatement);

		$this->expectException(LogicException::class);
		$paramsContainer->sqlDao->get();
	}

	public function testGetFailsObject(): void {
		$paramsContainer = $this->getParamsContainer(
				className: SqlDaoTestCompleteClass::class,
		);

		$pdoStatement = $this->createMock(PDOStatement::class);
		$pdoRow = ['object' => new stdClass()];
		$pdoStatement->method('fetchAll')->willReturn([$pdoRow]);
		$paramsContainer->pdo->method('query')->willReturn($pdoStatement);

		$this->expectException(LogicException::class);
		$paramsContainer->sqlDao->get();
	}

	public function testGetFailsMixed(): void {
		$paramsContainer = $this->getParamsContainer(
				className: SqlDaoTestCompleteClass::class,
		);

		$pdoStatement = $this->createMock(PDOStatement::class);
		$pdoRow = ['mixed' => 'nope'];
		$pdoStatement->method('fetchAll')->willReturn([$pdoRow]);
		$paramsContainer->pdo->method('query')->willReturn($pdoStatement);

		$this->expectException(LogicException::class);
		$paramsContainer->sqlDao->get();
	}

	public function testSqlToCamelCase(): void {
		$this->assertSame(
				'HelloWorld',
				SqlDao::sqlToCamelCase('hello_world'),
		);
	}

	public function testUpdateItem(): void {
		$paramsContainer = $this->getParamsContainer(
				table: 'sql_table',
				className: SqlDaoTestCompleteClass::class,
				getSqlColFromFieldCallable: static fn(string $field) => 'sql_' . $field,
		);

		$item = new SqlDaoTestCompleteClass();
		$item->bool = true;
		$item->float = 1.1;
		$item->int = 1;
		$item->string = 'string';
		$item->dateTime = new DateTime();
		$item->sqlDaoTestBackedEnum = SqlDaoTestBackedEnum::HELLO;

		$setParts = [
			"`sql_bool` = `1`",
			"`sql_float` = `$item->float`",
			// No sql_int because primary is not updated
			"`sql_string` = `$item->string`",
			"`sql_dateTime` = `{$item->dateTime->format('Y-m-d H:i:s')}`",
			"`sql_sqlDaoTestBackedEnum` = `{$item->sqlDaoTestBackedEnum->value}`",
		];
		$setAsString = implode(', ', $setParts);

		$paramsContainer->pdo->method('exec')
				->with(<<<SQL
					UPDATE `sql_table`
					SET $setAsString
					WHERE `sql_int` = `$item->int`
					SQL,
				)
				->willThrowException(new ExpectedException('updateItem'));


		try {
			$paramsContainer->sqlDao->updateItem($item);
			$this->fail('Expected ExpectedException');
		} catch (ExpectedException $e) {
			$this->assertSame('updateItem', $e->getMessage());
		}
	}

	public function testInsertItem(): void {
		$paramsContainer = $this->getParamsContainer(
				table: 'sql_table',
				className: SqlDaoTestCompleteClass::class,
				getSqlColFromFieldCallable: static fn(string $field) => 'sql_' . $field,
		);

		$item = new SqlDaoTestCompleteClass();
		$item->bool = true;
		$item->float = 1.1;
		$item->int = 1;
		$item->string = 'string';
		$item->dateTime = new DateTime();
		$item->sqlDaoTestBackedEnum = SqlDaoTestBackedEnum::HELLO;

		$valuesParts = [
			"`1`",
			"`$item->float`",
			"`$item->int`",
			"`$item->string`",
			"`{$item->dateTime->format('Y-m-d H:i:s')}`",
			"`{$item->sqlDaoTestBackedEnum->value}`",
		];
		$valuesAsString = implode(', ', $valuesParts);

		$paramsContainer->pdo->method('exec')
				->with(<<<SQL
					INSERT INTO `sql_table` (`sql_bool`, `sql_float`, `sql_int`, `sql_string`, `sql_dateTime`, `sql_sqlDaoTestBackedEnum`)
					VALUES ($valuesAsString)
					SQL,
				)
				->willThrowException(new ExpectedException('insertItem'));


		try {
			$paramsContainer->sqlDao->insertItem($item);
			$this->fail('Expected ExpectedException');
		} catch (ExpectedException $e) {
			$this->assertSame('insertItem', $e->getMessage());
		}
	}

	public function testInsertItemLastInsertedId(): void {
		$paramsContainer = $this->getParamsContainer(
				table: 'sql_table',
				className: SqlDaoTestCompleteClass::class,
				getSqlColFromFieldCallable: static fn(string $field) => 'sql_' . $field,
		);

		$item = new SqlDaoTestCompleteClass();
		$item->bool = true;
		$item->float = 1.1;
		$item->int = 1;
		$item->string = 'string';
		$item->dateTime = new DateTime();
		$item->sqlDaoTestBackedEnum = SqlDaoTestBackedEnum::HELLO;

		$paramsContainer->pdo->method('lastInsertId')
				->willReturn('-1');

		$paramsContainer->sqlDao->insertItem($item);

		$this->assertSame(-1, $item->int);
	}

	public function testSaveElements(): void {
		$paramsContainer = $this->getParamsContainer(
				table: 'sql_table',
				className: SqlDaoTestCompleteClass::class,
				getSqlColFromFieldCallable: static fn(string $field) => 'sql_' . $field,
		);

		$item = new SqlDaoTestCompleteClass();
		$item->bool = true;
		$item->float = 1.1;
		$item->int = 1;
		$item->string = 'string';
		$item->dateTime = new DateTime();
		$item->sqlDaoTestBackedEnum = SqlDaoTestBackedEnum::HELLO;

		$valuesParts = [
			"`1`",
			"`$item->float`",
			"`$item->int`",
			"`$item->string`",
			"`{$item->dateTime->format('Y-m-d H:i:s')}`",
			"`{$item->sqlDaoTestBackedEnum->value}`",
		];
		$valuesAsString = implode(', ', $valuesParts);

		$paramsContainer->pdo->method('exec')
				->with(<<<SQL
					INSERT INTO `sql_table` (`sql_bool`, `sql_float`, `sql_int`, `sql_string`, `sql_dateTime`, `sql_sqlDaoTestBackedEnum`)
					VALUES ($valuesAsString)
					ON DUPLICATE KEY UPDATE `sql_bool`=VALUES(`sql_bool`), `sql_float`=VALUES(`sql_float`), `sql_string`=VALUES(`sql_string`), `sql_dateTime`=VALUES(`sql_dateTime`), `sql_sqlDaoTestBackedEnum`=VALUES(`sql_sqlDaoTestBackedEnum`)
					SQL,
				)
				->willThrowException(new ExpectedException('saveElements'));


		try {
			$paramsContainer->sqlDao->saveElements([$item]);
			$this->fail('Expected ExpectedException');
		} catch (ExpectedException $e) {
			$this->assertSame('saveElements', $e->getMessage());
		}
	}

}
