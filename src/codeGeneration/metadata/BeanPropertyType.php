<?php

namespace SqlToCodeGenerator\codeGeneration\metadata;

use LogicException;

enum BeanPropertyType {

	case INT;
	case FLOAT;
	case STRING;
	case DATE;
	case ENUM;
	case ENUM_LIST;
	case BOOL;
	case OBJECT;
	case JSON;

	public static function getPhpType(BeanPropertyType $propertyType): string {
		return match ($propertyType) {
			self::INT => 'int',
			self::ENUM => '',
			self::ENUM_LIST => 'array',
			self::FLOAT => 'float',
			self::STRING => 'string',
			self::DATE => 'DateTime',
			self::BOOL => 'bool',
			self::OBJECT => throw new LogicException('Not supposed to occur'),
			self::JSON => throw new LogicException('To be implemented'),
		};
	}

	public static function getJsType(BeanPropertyType $propertyType): string {
		return match ($propertyType) {
			self::INT, self::ENUM => 'int',
			self::ENUM_LIST => 'Array',
			self::FLOAT => 'float',
			self::STRING => 'string',
			self::DATE => 'Date',
			self::BOOL => 'bool',
			self::OBJECT => throw new LogicException('Not supposed to occur'),
			self::JSON => throw new LogicException('To be implemented'),
		};
	}

	/**
	 * @link https://mariadb.com/kb/en/data-types/
	 * @param string $sqlTypeAsString
	 * @param string $columnType
	 * @return BeanPropertyType
	 */
	public static function getPropertyTypeFromSql(string $sqlTypeAsString, string $columnType): BeanPropertyType {
		return match (strtoupper($sqlTypeAsString)) {

			// https://mariadb.com/kb/en/numeric-data-type-overview/
			'SMALLINT', 'INT', 'INT1', 'INT2', 'INT3', 'INT4', 'INT8', 'INTEGER', 'MEDIUMINT', 'BIGINT' => self::INT,
			'BOOLEAN', 'BIT' => self::BOOL,
			'TINYINT' => $columnType === 'tinyint(1)' ? self::BOOL : self::INT,
			'DECIMAL', 'DEC', 'NUMERIC', 'FIXED', 'NUMBER', 'FLOAT', 'DOUBLE', 'DOUBLE PRECISION', 'REAL' => self::FLOAT,

			// https://mariadb.com/kb/en/string-data-types/
			'VARCHAR', 'BINARY', 'BLOB', 'TEXT', 'CHAR', 'CHAR BYTE', 'INET4', 'INET6', 'MEDIUMBLOB', 'LONGBLOB',
			'LONG', 'LONG VARCHAR', 'LONGTEXT', 'MEDIUMTEXT', 'ROW', 'TINYBLOB', 'TINYTEXT', 'VARBINARY', 'UUID' => self::STRING,
			'JSON' => self::JSON,
			'ENUM' => self::ENUM,
			'SET' => self::ENUM_LIST,

			// https://mariadb.com/kb/en/date-and-time-data-types/
			'DATE', 'TIME', 'DATETIME', 'TIMESTAMP', 'YEAR' => self::DATE,

			default => throw new LogicException('Not handle sql type: ' . $sqlTypeAsString),
		};
	}

}
