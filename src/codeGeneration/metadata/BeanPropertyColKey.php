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

	public static function tryFrom(string $columnKey): ?self {
		foreach (BeanPropertyColKey::cases() as $case) {
			if ($case->name === $columnKey) {
				return $case;
			}
		}
		return null;
	}

	public function toClassFieldEnum(): ClassFieldEnum|null {
		return match ($this) {
			self::PRI => ClassFieldEnum::PRIMARY,
			self::UNI => ClassFieldEnum::UNIQUE,
			self::MUL => null,
		};
	}

	public function toHumanReadableString(): string {
		return match ($this) {
			self::PRI => 'Primary',
			self::UNI => 'Unique',
			self::MUL => 'Multiple',
		};
	}

}
