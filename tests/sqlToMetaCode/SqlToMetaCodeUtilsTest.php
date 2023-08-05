<?php

namespace SqlToCodeGenerator\test\sqlToMetaCode;

use LogicException;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;
use SqlToCodeGenerator\codeGeneration\metadata\ForeignBeanField;
use SqlToCodeGenerator\codeGeneration\utils\VariableUtils;
use SqlToCodeGenerator\sql\SqlDao;
use SqlToCodeGenerator\sqlToMetaCode\SqlToMetaCodeUtils;

class SqlToMetaCodeUtilsTest extends TestCase {

	public function testNoPropertySoNoBeans(): void {
		$this->assertEmpty(SqlToMetaCodeUtils::getBeansFromMetaCodeBeans(
				[SqlToMetaTestUtils::getTable()],
				[],
				[],
		));
	}

	public function testSimpleColumnToBean(): void {
		$table = SqlToMetaTestUtils::getTable(
				tableSchema: 'tableSchema',
				tableName: 'tableName',
		);
		$column = SqlToMetaTestUtils::getColumn(
				tableSchema: 'tableSchema',
				tableName: 'tableName',
				columnName: 'columnName',
				columnComment: 'columnComment',
				dataType: 'INT',
		);

		[$bean] = SqlToMetaCodeUtils::getBeansFromMetaCodeBeans([$table], [$column], []);
		[$property] = $bean->properties;

		$this->assertSame($column->tableSchema, $bean->sqlDatabase);
		$this->assertSame($column->tableName, $bean->sqlTable);
		$this->assertSame($column->columnName, $property->sqlName);
		$this->assertSame($column->columnComment, $property->sqlComment);
		$this->assertSame($bean, $property->belongsToBean);
	}

	#[Depends('testSimpleColumnToBean')]
	public function testNoDefaultValue(): void {
		$table = SqlToMetaTestUtils::getTable(
				tableSchema: 'tableSchema',
				tableName: 'tableName',
		);
		$columns = [
			SqlToMetaTestUtils::getColumn(
					tableSchema: 'tableSchema',
					tableName: 'tableName',
					columnName: 'columnNameInt',
					dataType: 'INT',
			),
			SqlToMetaTestUtils::getColumn(
					tableSchema: 'tableSchema',
					tableName: 'tableName',
					columnName: 'columnNameFloat',
					dataType: 'FLOAT',
			),
			SqlToMetaTestUtils::getColumn(
					tableSchema: 'tableSchema',
					tableName: 'tableName',
					columnName: 'columnNameString',
					dataType: 'TEXT',
			),
			SqlToMetaTestUtils::getColumn(
					tableSchema: 'tableSchema',
					tableName: 'tableName',
					columnName: 'columnNameString',
					dataType: 'DATE',
			),
		];

		[$bean] = SqlToMetaCodeUtils::getBeansFromMetaCodeBeans([$table], $columns, []);

		foreach ($bean->properties as $property) {
			$this->assertNull($property->defaultValueAsString);
		}

	}

	#[Depends('testSimpleColumnToBean')]
	public function testIntFloatStringJsonDefaultValueAsNull(): void {
		$table = SqlToMetaTestUtils::getTable(
				tableSchema: 'tableSchema',
				tableName: 'tableName',
		);
		$columns = [
			SqlToMetaTestUtils::getColumn(
					tableSchema: 'tableSchema',
					tableName: 'tableName',
					columnName: 'columnNameInt',
					dataType: 'INT',
					columnDefault: 'NULL',
			),
			SqlToMetaTestUtils::getColumn(
					tableSchema: 'tableSchema',
					tableName: 'tableName',
					columnName: 'columnNameFloat',
					dataType: 'FLOAT',
					columnDefault: 'NULL',
			),
			SqlToMetaTestUtils::getColumn(
					tableSchema: 'tableSchema',
					tableName: 'tableName',
					columnName: 'columnNameString',
					dataType: 'TEXT',
					columnDefault: 'NULL',
			),
			SqlToMetaTestUtils::getColumn(
					tableSchema: 'tableSchema',
					tableName: 'tableName',
					columnName: 'columnNameDate',
					dataType: 'DATE',
					columnDefault: 'NULL',
			),
			SqlToMetaTestUtils::getColumn(
					tableSchema: 'tableSchema',
					tableName: 'tableName',
					columnName: 'columnNameJson',
					dataType: 'JSON',
					columnDefault: 'NULL',
			),
		];

		[$bean] = SqlToMetaCodeUtils::getBeansFromMetaCodeBeans([$table], $columns, []);

		foreach ($bean->properties as $property) {
			$this->assertSame('null', $property->defaultValueAsString);
		}
	}

	#[Depends('testSimpleColumnToBean')]
	public function testIntFloatStringJsonDefaultValueAsInt(): void {
		$table = SqlToMetaTestUtils::getTable(
				tableSchema: 'tableSchema',
				tableName: 'tableName',
		);
		$columns = [
			SqlToMetaTestUtils::getColumn(
					tableSchema: 'tableSchema',
					tableName: 'tableName',
					columnName: 'columnNameInt',
					dataType: 'INT',
					columnDefault: '1',
			),
			SqlToMetaTestUtils::getColumn(
					tableSchema: 'tableSchema',
					tableName: 'tableName',
					columnName: 'columnNameFloat',
					dataType: 'FLOAT',
					columnDefault: '1',
			),
			SqlToMetaTestUtils::getColumn(
					tableSchema: 'tableSchema',
					tableName: 'tableName',
					columnName: 'columnNameString',
					dataType: 'TEXT',
					columnDefault: '1',
			),
			SqlToMetaTestUtils::getColumn(
					tableSchema: 'tableSchema',
					tableName: 'tableName',
					columnName: 'columnNameJson',
					dataType: 'JSON',
					columnDefault: '1',
			),
		];

		[$bean] = SqlToMetaCodeUtils::getBeansFromMetaCodeBeans([$table], $columns, []);

		foreach ($bean->properties as $property) {
			$this->assertSame('1', $property->defaultValueAsString);
		}
	}

	#[Depends('testSimpleColumnToBean')]
	public function testBoolDefaultFalse(): void {
		$table = SqlToMetaTestUtils::getTable(
				tableSchema: 'tableSchema',
				tableName: 'tableName',
		);
		$column = SqlToMetaTestUtils::getColumn(
				tableSchema: 'tableSchema',
				tableName: 'tableName',
				columnName: 'columnName',
				dataType: 'BOOLEAN',
				columnDefault: '0',
		);

		[$bean] = SqlToMetaCodeUtils::getBeansFromMetaCodeBeans([$table], [$column], []);
		[$property] = $bean->properties;

		$this->assertSame('false', $property->defaultValueAsString);
	}

	#[Depends('testSimpleColumnToBean')]
	public function testBoolDefaultTrue(): void {
		$table = SqlToMetaTestUtils::getTable(
				tableSchema: 'tableSchema',
				tableName: 'tableName',
		);
		$column = SqlToMetaTestUtils::getColumn(
				tableSchema: 'tableSchema',
				tableName: 'tableName',
				columnName: 'columnName',
				dataType: 'BOOLEAN',
				columnDefault: '1',
		);

		[$bean] = SqlToMetaCodeUtils::getBeansFromMetaCodeBeans([$table], [$column], []);
		[$property] = $bean->properties;

		$this->assertSame('true', $property->defaultValueAsString);
	}

	#[Depends('testSimpleColumnToBean')]
	public function testBoolDefaultNotNull(): void {
		$table = SqlToMetaTestUtils::getTable(
				tableSchema: 'tableSchema',
				tableName: 'tableName',
		);
		$column = SqlToMetaTestUtils::getColumn(
				tableSchema: 'tableSchema',
				tableName: 'tableName',
				columnName: 'columnName',
				dataType: 'BOOLEAN',
		);

		[$bean] = SqlToMetaCodeUtils::getBeansFromMetaCodeBeans([$table], [$column], []);
		[$property] = $bean->properties;

		$this->assertNull($property->defaultValueAsString);
	}

	#[Depends('testSimpleColumnToBean')]
	public function testBoolDefaultNull(): void {
		$table = SqlToMetaTestUtils::getTable(
				tableSchema: 'tableSchema',
				tableName: 'tableName',
		);
		$column = SqlToMetaTestUtils::getColumn(
				tableSchema: 'tableSchema',
				tableName: 'tableName',
				columnName: 'columnName',
				dataType: 'BOOLEAN',
				isNullable: 'YES',
		);

		[$bean] = SqlToMetaCodeUtils::getBeansFromMetaCodeBeans([$table], [$column], []);
		[$property] = $bean->properties;

		$this->assertSame('null', $property->defaultValueAsString);
	}

	#[Depends('testSimpleColumnToBean')]
	public function testEnum(): void {
		$table = SqlToMetaTestUtils::getTable(
				tableSchema: 'tableSchema',
				tableName: 'tableName',
		);
		$column = SqlToMetaTestUtils::getColumn(
				tableSchema: 'tableSchema',
				tableName: 'tableName',
				columnName: 'columnName',
				dataType: 'ENUM',
				columnComment: 'columnComment',
				columnType: "enum('Y','aa aa')",
				columnDefault: "'Y'",
		);

		[$bean] = SqlToMetaCodeUtils::getBeansFromMetaCodeBeans([$table], [$column], []);
		[$property] = $bean->properties;
		$enum = $property->enum;

		$this->assertNotNull($enum);
		$this->assertSame($column->columnComment, $enum->sqlComment);
		$this->assertSame($column->columnDefault, $property->defaultValueAsString);
		$this->assertSame(SqlDao::sqlToCamelCase("{$bean->sqlTable}_{$property->sqlName}_enum"), $enum->name);
		$this->assertCount(2, $enum->values);
		$this->assertContains('Y', $enum->values);
		$this->assertContains(VariableUtils::stringToEnumCompliantValue('aa aa'), $enum->values);
	}

	#[Depends('testSimpleColumnToBean')]
	public function testBadEnum(): void {
		$table = SqlToMetaTestUtils::getTable(
				tableSchema: 'tableSchema',
				tableName: 'tableName',
		);
		$column = SqlToMetaTestUtils::getColumn(
				tableSchema: 'tableSchema',
				tableName: 'tableName',
				columnName: 'columnName',
				dataType: 'ENUM',
		);

		$this->expectException(LogicException::class);
		SqlToMetaCodeUtils::getBeansFromMetaCodeBeans([$table], [$column], []);
	}

	#[Depends('testSimpleColumnToBean')]
	public function testSet(): void {
		$table = SqlToMetaTestUtils::getTable(
				tableSchema: 'tableSchema',
				tableName: 'tableName',
		);
		$column = SqlToMetaTestUtils::getColumn(
				tableSchema: 'tableSchema',
				tableName: 'tableName',
				columnName: 'columnName',
				dataType: 'SET',
				columnComment: 'columnComment',
				columnType: "set('Value A','Value B')",
				columnDefault: "'Value A'",
		);

		[$bean] = SqlToMetaCodeUtils::getBeansFromMetaCodeBeans([$table], [$column], []);
		[$property] = $bean->properties;
		$enum = $property->enum;

		$this->assertNotNull($enum);
		$this->assertSame($column->columnComment, $enum->sqlComment);
		$this->assertSame($column->columnDefault, $property->defaultValueAsString);
		$this->assertSame(SqlDao::sqlToCamelCase("{$bean->sqlTable}_{$property->sqlName}_enum"), $enum->name);
		$this->assertCount(2, $enum->values);
		$this->assertContains(VariableUtils::stringToEnumCompliantValue('Value A'), $enum->values);
		$this->assertContains(VariableUtils::stringToEnumCompliantValue('Value B'), $enum->values);
	}

	#[Depends('testSimpleColumnToBean')]
	public function testBadSet(): void {
		$table = SqlToMetaTestUtils::getTable(
				tableSchema: 'tableSchema',
				tableName: 'tableName',
		);
		$column = SqlToMetaTestUtils::getColumn(
				tableSchema: 'tableSchema',
				tableName: 'tableName',
				columnName: 'columnName',
				dataType: 'SET',
		);

		$this->expectException(LogicException::class);
		SqlToMetaCodeUtils::getBeansFromMetaCodeBeans([$table], [$column], []);
	}

	#[Depends('testSimpleColumnToBean')]
	public function testTableKeyColUsages(): void {
		$table = SqlToMetaTestUtils::getTable(
				tableSchema: 'tableSchema',
				tableName: 'tableName',
		);
		$column = SqlToMetaTestUtils::getColumn(
				tableSchema: 'tableSchema',
				tableName: 'tableName',
				columnName: 'columnName',
				dataType: 'INT',
		);
		$keyColumnUsage = SqlToMetaTestUtils::getKeyColumnUsage(
				tableSchema: 'tableSchema',
				tableName: 'tableName',
				columnName: 'columnName',
				constraintSchema: 'tableSchema',
				constraintName: 'constraintName',
		);

		[$bean] = SqlToMetaCodeUtils::getBeansFromMetaCodeBeans([$table], [$column], [$keyColumnUsage]);

		$this->assertNotEmpty($bean->colNamesByUniqueConstraintName);
		$this->assertSame('columnName', array_values($bean->colNamesByUniqueConstraintName)[0][0]);
	}

	#[Depends('testSimpleColumnToBean')]
	public function testBadTableKeyColUsages(): void {
		$table = SqlToMetaTestUtils::getTable(
				tableSchema: 'tableSchema',
				tableName: 'tableName',
		);
		$column = SqlToMetaTestUtils::getColumn(
				tableSchema: 'tableSchema',
				tableName: 'tableName',
				columnName: 'columnName',
				dataType: 'INT',
		);
		$keyColumnUsage = SqlToMetaTestUtils::getKeyColumnUsage(
				tableSchema: 'tableSchema',
				tableName: 'tableName',
				columnName: 'badColumnName',
				constraintSchema: 'tableSchema',
				constraintName: 'constraintName',
		);

		$this->expectException(LogicException::class);
		SqlToMetaCodeUtils::getBeansFromMetaCodeBeans([$table], [$column], [$keyColumnUsage]);
	}

	#[Depends('testSimpleColumnToBean')]
	public function testKeyColumnUsages(): void {
		$tableA = SqlToMetaTestUtils::getTable(
				tableSchema: 'tableSchema',
				tableName: 'tableA',
		);
		$tableB = SqlToMetaTestUtils::getTable(
				tableSchema: 'tableSchema',
				tableName: 'tableB',
		);
		$columnA = SqlToMetaTestUtils::getColumn(
				tableSchema: 'tableSchema',
				tableName: 'tableA',
				columnName: 'columnA',
				dataType: 'INT',
		);
		$columnB = SqlToMetaTestUtils::getColumn(
				tableSchema: 'tableSchema',
				tableName: 'tableB',
				columnName: 'columnB',
				dataType: 'INT',
		);
		$keyColumnUsage = SqlToMetaTestUtils::getKeyColumnUsage(
				tableSchema: 'tableSchema',
				tableName: 'tableA',
				columnName: 'columnA',
				constraintName: 'columnA_to_columnB',
				referencedTableSchema: 'tableSchema',
				referencedTableName: 'tableB',
				referencedColumnName: 'columnB',
		);

		$beans = SqlToMetaCodeUtils::getBeansFromMetaCodeBeans(
				[$tableA, $tableB],
				[$columnA, $columnB],
				[$keyColumnUsage],
		);
		$beanA = null;
		$beanB = null;
		foreach ($beans as $bean) {
			switch ($bean->sqlTable) {
				case 'tableA':
					$beanA = $bean;
					break;
				case 'tableB':
					$beanB = $bean;
					break;

				default:
					throw new LogicException('Bean does is not from tableA nor tableB');
			}
		}
		if ($beanA === null || $beanB === null) {
			throw new LogicException('beanA or beanB is null');
		}
		/** @var ForeignBeanField $foreignBean */
		$foreignBeanAtoB = array_values($beanA->foreignBeans)[0] ?? null;
		/** @var ForeignBeanField $foreignBean */
		$foreignBeanBToA = array_values($beanB->foreignBeans)[0] ?? null;

		$this->assertNotNull($foreignBeanAtoB);
		$this->assertNotNull($foreignBeanBToA);

		$this->assertSame($beanB, $foreignBeanAtoB->toBean);
		$this->assertSame($beanA, $foreignBeanBToA->toBean);

		$this->assertSame($beanB->properties[0], $foreignBeanAtoB->onProperty);
		$this->assertSame($beanA->properties[0], $foreignBeanBToA->onProperty);
	}

}
