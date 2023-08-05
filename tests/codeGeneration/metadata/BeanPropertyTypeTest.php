<?php

namespace SqlToCodeGenerator\test\codeGeneration\metadata;

use LogicException;
use PHPUnit\Framework\TestCase;
use SqlToCodeGenerator\codeGeneration\metadata\BeanPropertyType;

class BeanPropertyTypeTest extends TestCase {

	public function testPhpType(): void {
		foreach (BeanPropertyType::cases() as $beanPropertyType) {
			if (in_array($beanPropertyType, [BeanPropertyType::OBJECT, BeanPropertyType::JSON], true)) {
				$this->expectException(LogicException::class);
			}
			BeanPropertyType::getPhpType($beanPropertyType);
		}
		$this->assertTrue(true);
	}

	public function testJsType(): void {
		foreach (BeanPropertyType::cases() as $beanPropertyType) {
			if (in_array($beanPropertyType, [BeanPropertyType::OBJECT, BeanPropertyType::JSON], true)) {
				$this->expectException(LogicException::class);
			}
			BeanPropertyType::getJsType($beanPropertyType);
		}
		$this->assertTrue(true);
	}

	/**
	 * @link https://www.mariadbtutorial.com/mariadb-basics/mariadb-data-types/
	 */
	public function testMariaDbPropertyTypes(): void {
		$mariaDbTypes = [
			'TINYINT',
			'BOOLEAN',
			'SMALLINT',
			'MEDIUMINT',
			'INT',
			'INT1',
			'INT2',
			'INT3',
			'INT4',
			'INT8',
			'INTEGER',
			'BIGINT',

			'DECIMAL',
			'DEC',
			'NUMERIC',
			'FIXED',
			'NUMBER',
			'FLOAT',
			'DOUBLE',
			'DOUBLE PRECISION',
			'REAL',

			'BIT',

			'CHAR',
			'VARCHAR',
			'BINARY',
			'CHAR BYTE',
			'VARBINARY',
			'TINYTEXT',
			'TEXT',
			'MEDIUMTEXT',
			'LONGTEXT',
			'LONG',
			'INET4',
			'INET6',
			'UUID',

			'TINYBLOB',
			'BLOB',
			'MEDIUMBLOB',
			'LONGBLOB',

			'ENUM',
			'SET',

			'DATE',
			'TIME',
			'DATETIME',
			'TIMESTAMP',
			'YEAR',

			'JSON',
		];
		foreach ($mariaDbTypes as $mariaDbType) {
			$this->assertNotNull(BeanPropertyType::getPropertyTypeFromSql($mariaDbType, ''));
		}
	}

	/**
	 * @link https://www.mariadbtutorial.com/mariadb-basics/mariadb-data-types/
	 */
	public function testMariaDbNotHandledPropertyTypes(): void {
		$mariaDbTypes = [
			'ROW',

			'GEOMETRY',
			'GEOMETRYCOLLECTION',
			'LINESTRING',
			'MULTILINESTRING',
			'MULTIPOINT',
			'MULTIPOLYGON',
			'POINT',
			'POLYGON',
		];
		foreach ($mariaDbTypes as $mariaDbType) {
			$this->expectException(LogicException::class);
			BeanPropertyType::getPropertyTypeFromSql($mariaDbType, '');
		}
	}

}
