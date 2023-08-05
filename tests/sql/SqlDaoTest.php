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

		$this->expectException(ExpectedException::class);
		$this->expectExceptionMessage('inTransaction');
		$paramsContainer->sqlDao->endTransaction();
	}

	public function testEndTransactionInTransactionCommit(): void {
		$paramsContainer = $this->getParamsContainer();
		$paramsContainer->pdo->method('inTransaction')->willReturn(true);
		$paramsContainer->pdo->method('commit')->willThrowException(new ExpectedException('commit'));


		$this->expectException(ExpectedException::class);
		$this->expectExceptionMessage('commit');
		$paramsContainer->sqlDao->endTransaction();
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

		$this->expectException(ExpectedException::class);
		$this->expectExceptionMessage('inTransaction');
		$paramsContainer->sqlDao->cancelTransaction();
	}

	public function testCancelTransactionInTransactionCommit(): void {
		$paramsContainer = $this->getParamsContainer();
		$paramsContainer->pdo->method('inTransaction')->willReturn(true);
		$paramsContainer->pdo->method('rollBack')->willThrowException(new ExpectedException('rollBack'));

		$this->expectException(ExpectedException::class);
		$this->expectExceptionMessage('rollBack');
		$paramsContainer->sqlDao->cancelTransaction();
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

		$this->expectException(ExpectedException::class);
		$this->expectExceptionMessage('emptyQuery');
		$paramsContainer->sqlDao->get();
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

		$this->expectException(ExpectedException::class);
		$this->expectExceptionMessage('onlyWhere');
		$paramsContainer->sqlDao->get(where: 'where');
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

		$this->expectException(ExpectedException::class);
		$this->expectExceptionMessage('onlyGroupBy');
		$paramsContainer->sqlDao->get(groupBy: 'group by');
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

		$this->expectException(ExpectedException::class);
		$this->expectExceptionMessage('onlyOrderBy');
		$paramsContainer->sqlDao->get(orderBy: 'order by');
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

		$this->expectException(ExpectedException::class);
		$this->expectExceptionMessage('onlyLimit');
		$paramsContainer->sqlDao->get(limit: 'limit');
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

		$this->expectException(ExpectedException::class);
		$this->expectExceptionMessage('whereBeforeAll');
		$paramsContainer->sqlDao->get(
				where: 'where',
				groupBy: 'group by',
				orderBy: 'order by',
				limit: 'limit',
		);
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

		$this->expectException(ExpectedException::class);
		$this->expectExceptionMessage('whereBeforeGroupBy');
		$paramsContainer->sqlDao->get(where: 'where', groupBy: 'group by');
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

		$this->expectException(ExpectedException::class);
		$this->expectExceptionMessage('whereBeforeOrderBy');
		$paramsContainer->sqlDao->get(where: 'where', orderBy: 'order by');
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

		$this->expectException(ExpectedException::class);
		$this->expectExceptionMessage('whereBeforeLimit');
		$paramsContainer->sqlDao->get(where: 'where', limit: 'limit');
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

		$this->expectException(ExpectedException::class);
		$this->expectExceptionMessage('groupByBeforeOrderBy');
		$paramsContainer->sqlDao->get(groupBy: 'group by', orderBy: 'order by');
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

		$this->expectException(ExpectedException::class);
		$this->expectExceptionMessage('groupByBeforeLimit');
		$paramsContainer->sqlDao->get(groupBy: 'group by', limit: 'limit');
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

		$this->expectException(ExpectedException::class);
		$this->expectExceptionMessage('orderByBeforeLimit');
		$paramsContainer->sqlDao->get(orderBy: 'order by', limit: 'limit');
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

		$this->expectException(ExpectedException::class);
		$this->expectExceptionMessage('updateItem');
		$paramsContainer->sqlDao->updateItem($item);
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

		$this->expectException(ExpectedException::class);
		$this->expectExceptionMessage('insertItem');
		$paramsContainer->sqlDao->insertItem($item);
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

		$this->expectException(ExpectedException::class);
		$this->expectExceptionMessage('saveElements');
		$paramsContainer->sqlDao->saveElements([$item]);
	}

}
