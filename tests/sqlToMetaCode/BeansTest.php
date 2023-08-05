<?php

namespace SqlToCodeGenerator\test\sqlToMetaCode;

use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;

class BeansTest extends TestCase {

	public function testTableUniqueIdentifier(): void {
		$table = SqlToMetaTestUtils::getTable(tableSchema: 'tableSchema', tableName: 'tableName');

		$this->assertSame('tableSchema_tableName', $table->getUniqueIdentifier());
	}

	#[Depends('testTableUniqueIdentifier')]
	public function testColumnTableUniqueIdentifier(): void {
		$table = SqlToMetaTestUtils::getTable(tableSchema: 'tableSchema', tableName: 'tableName');
		$column = SqlToMetaTestUtils::getColumn(tableSchema: $table->tableSchema, tableName: $table->tableName);

		$this->assertSame($table->getUniqueIdentifier(), $column->getTableUniqueIdentifier());
	}

	public function testColumnUniqueIdentifier(): void {
		$column = SqlToMetaTestUtils::getColumn(
				tableSchema: 'tableSchema',
				tableName: 'tableName',
				columnName: 'columnName',
		);

		$this->assertSame('tableSchema_tableName_columnName', $column->getUniqueIdentifier());
	}

}
