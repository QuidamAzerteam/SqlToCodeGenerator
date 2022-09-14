<?php

namespace SqlToCodeGenerator\generation\metadata;

use RuntimeException;

/**
 * {@link https://dev.mysql.com/doc/refman/5.7/en/information-schema-columns-table.html}
 * Search COLUMN_KEY
 */
class BeanPropertyColKey {

	public const PRI = 1;
	public const UNI = 2;
	public const MUL = 3;

	public static function getFromString(?string $value): ?int {
		if (!$value) {
			return null;
		}
		return match ($value) {
			'PRI' => self::PRI,
			'UNI' => self::UNI,
			'MUL' => self::MUL,
			default => throw new RuntimeException('Unexpected BeanPropertyColKey value: ' . $value),
		};
	}

	public static function getAsString(int $colKey): string {
		return match ($colKey) {
			self::PRI => 'Primary',
			self::UNI => 'Unique',
			self::MUL => '',
			default => throw new RuntimeException('Unexpected BeanPropertyColKey value: ' . $colKey),
		};
	}

}
