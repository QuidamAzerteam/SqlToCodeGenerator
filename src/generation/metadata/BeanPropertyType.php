<?php

namespace SqlToCodeGenerator\generation\metadata;

use LogicException;

class BeanPropertyType {

	public const INT = 1;
	public const FLOAT = 2;
	public const STRING = 3;
	public const DATE = 4;
	public const ENUM = 5;
	public const BOOL = 6;
	public const OBJECT = 7;
	public const JSON = 8;

	public static function getPhpType(int $propertyType): string {
		return match ($propertyType) {
			self::INT => 'int',
			self::ENUM => '',
			self::FLOAT => 'float',
			self::STRING => 'string',
			self::DATE => '\DateTime',
			self::BOOL => 'bool',
			default => throw new LogicException('Unexpected $propertyType: ' . $propertyType),
		};
	}

	public static function getJsType(int $propertyType): string {
		return match ($propertyType) {
			self::INT, self::ENUM => 'int',
			self::FLOAT => 'float',
			self::STRING => 'string',
			self::DATE => 'Date',
			self::BOOL => 'bool',
			default => throw new LogicException('Unexpected $propertyType: ' . $propertyType),
		};
	}

	/**
	 * @link https://mariadb.com/kb/en/data-types/
	 * @return int {@see BeanPropertyType}
	 */
	public static function getPropertyTypeFromSql(string $sqlTypeAsString, string $columnType): int {
		return match (strtoupper($sqlTypeAsString)) {

			// https://mariadb.com/kb/en/numeric-data-type-overview/
			'INT', 'INT1', 'INT2', 'INT3', 'INT4', 'INT8', 'MEDIUMINT', 'BIGINT' => self::INT,
			'BOOLEAN', 'BIT' => self::BOOL,
			'TINYINT' => $columnType === 'tinyint(1)' ? self::BOOL : self::INT,
			'DECIMAL', 'FLOAT', 'DOUBLE' => self::FLOAT,

			// https://mariadb.com/kb/en/string-data-types/
			'VARCHAR', 'BINARY', 'BLOB', 'TEXT', 'CHAR', 'CHAR BYTE', 'INET6', 'MEDIUM BLOB', 'LONG BLOB',
			'LONG', 'LONG VARCHAR', 'LONGTEXT', 'MEDIUMTEXT', 'ROW', 'TINYBLOB', 'TINYTEXT', 'VARBINARY', 'UUID' => self::STRING,
			'JSON' => self::JSON,
			'ENUM', 'SET' => self::ENUM,

			// https://mariadb.com/kb/en/date-and-time-data-types/
			'DATE', 'TIME', 'DATETIME', 'TIMESTAMP', 'YEAR' => self::DATE,

			default => throw new LogicException('Not handle sql type: ' . $sqlTypeAsString),
		};
	}
}
