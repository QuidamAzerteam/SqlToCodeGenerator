<?php

namespace SqlToCodeGenerator\codeGeneration\metadata;

use SqlToCodeGenerator\codeGeneration\attribute\ClassFieldEnum;

/**
 * {@link https://dev.mysql.com/doc/refman/5.7/en/information-schema-columns-table.html}
 * Search COLUMN_KEY
 */
enum BeanPropertyColKey {

	case PRI;
	case UNI;
	case MUL;

	public static function getFromString(?string $value): ?BeanPropertyColKey {
		if (!$value) {
			return null;
		}
		return match ($value) {
			'PRI' => self::PRI,
			'UNI' => self::UNI,
			'MUL' => self::MUL,
		};
	}

	public static function getAsString(BeanPropertyColKey $colKey): string {
		return match ($colKey) {
			self::PRI => 'Primary',
			self::UNI => 'Unique',
			self::MUL => '',
		};
	}

	public function toClassFieldEnum(): ClassFieldEnum|null {
		return match ($this) {
			self::PRI => ClassFieldEnum::PRIMARY,
			self::UNI => ClassFieldEnum::UNIQUE,
			self::MUL => null,
		};
	}

}
