<?php

namespace SqlToCodeGenerator\sql;

use DateTime;
use Exception;
use LogicException;
use PDO;
use PDOException;
use ReflectionClass;
use ReflectionException;
use ReflectionObject;
use ReflectionProperty;
use SqlToCodeGenerator\codeGeneration\attribute\ClassField;
use SqlToCodeGenerator\codeGeneration\attribute\ClassFieldEnum;
use UnitEnum;

/**
 * @template T
 */
abstract class SqlDao {

	private readonly PdoContainer $pdoContainer;

	public function __construct(PdoContainer $pdoContainer = null) {
		$this->pdoContainer = $pdoContainer ?: SqlUtils::getPdoContainer();
	}

	abstract protected function getTable(): string;

	/**
	 * @param mixed $colValue
	 * @param string $field
	 * @param ReflectionObject $itemReflection
	 * @return mixed
	 */
	public function getItemFieldValue(mixed $colValue, string $field, ReflectionObject $itemReflection): mixed {
		if ($colValue === null) {
			return null;
		}
		$property = $itemReflection->getProperty($field);
		// https://www.php.net/manual/en/language.types.declarations.php
		$typeName = $property->getType()?->getName();

		if (in_array($typeName, self::getNotPdoCompatibleTypes(), true)) {
			throw new LogicException("PDO cannot return $typeName");
		}
		return match ($typeName) {
			'bool' => (bool) $colValue,
			'float' => (float) $colValue,
			'int' => (int) $colValue,
			'string' => (string) $colValue,
			'DateTime' => new DateTime($colValue),

			default => (static function () use ($colValue, $typeName) {
				try {
					$maybeItsAClass = new ReflectionClass($typeName);
					if ($maybeItsAClass->isEnum()) {
						foreach ($maybeItsAClass->getMethod('cases')->invoke(null) as $case) {
							if ($case->name === $colValue) {
								return $case;
							}
						}
						throw new LogicException("Value '$colValue' not found in enum: $typeName");
					}
				} catch (ReflectionException) {
					// Ignore catch
				}

				throw new LogicException("PDO type return not implemented: $typeName");
			})()
		};
	}

	/**
	 * @return class-string<T>
	 */
	abstract protected function getClass(): string;

	private function getPdo(): PDO {
		return $this->pdoContainer->getPdo();
	}

	public function __destruct() {
		if ($this->getPdo()->inTransaction()) {
			$this->getPdo()->rollBack();
			throw new PDOException('Exception not closed ! It has been rollback');
		}
	}

	public function startTransaction(): void {
		$this->getPdo()->beginTransaction();
	}

	public function endTransaction(): void {
		if ($this->getPdo()->inTransaction()) {
			$this->getPdo()->commit();
		}
	}

	public function cancelTransaction(): void {
		if ($this->getPdo()->inTransaction()) {
			$this->getPdo()->rollBack();
		}
	}

	public function deleteData(string $where): int {
		$tableName = $this->getTable();
		$query = "DELETE FROM $tableName WHERE $where";
		return $this->getPdo()->exec($query);
	}

	/**
	 * @see PDO::quote()
	 */
	public function quote(string $toQuote): bool|string {
		return $this->getPdo()->quote($toQuote);
	}

	public function foundRows(): int {
		return $this->getPdo()->query('SELECT FOUND_ROWS()')->fetchColumn();
	}

	public function lastInsertedId(): string|false {
		return $this->getPdo()->lastInsertId();
	}

	public function fetchFromQuery(string $query): array {
		return $this->getPdo()->query(
				$query,
				PDO::FETCH_ASSOC,
		)->fetchAll();
	}

	public function prepareQuery(string $query): SqlPrepareStatement {
		return new SqlPrepareStatement($this->getPdo()->prepare($query));
	}

	public function executePrepare(SqlPrepareStatement $sqlPrepareStatement): array {
		return $sqlPrepareStatement->executeAndFetch(PDO::FETCH_ASSOC);
	}

	/**
	 * @return string[]
	 */
	private static function getNotPdoCompatibleTypes(): array {
		return ['array', 'callable', 'iterable', 'object', 'mixed'];
	}

	/**
	 * @param string $where
	 * @param string $groupBy
	 * @param string $orderBy
	 * @param string $limit
	 * @return T[]
	 */
	public function get(
			string $where = '',
			string $groupBy = '',
			string $orderBy = '',
			string $limit = '',
	): array {
		$table = $this->getTable();
		$query = "SELECT SQL_CALC_FOUND_ROWS * FROM `$table`";
		if ($where) {
			$query .= "\nWHERE $where";
		}
		if ($groupBy) {
			$query .= "\nGROUP BY $groupBy";
		}
		if ($orderBy) {
			$query .= "\nORDER BY $orderBy";
		}
		if ($limit) {
			$query .= "\nLIMIT $limit";
		}
		$statement = $this->getPdo()->query(
				$query,
				PDO::FETCH_ASSOC,
		);
		$result = $statement->fetchAll();
		$className = $this->getClass();
		$items = [];
		foreach ($result as $row) {
			$item = new $className();
			$itemReflection = new ReflectionObject($item);
			foreach ($row as $colKey => $colValue) {
				$field = lcfirst(static::sqlToCamelCase($colKey));

				$item->$field = $this->getItemFieldValue($colValue, $field, $itemReflection);
			}
			$items[] = $item;
		}
		return $items;
	}

	public static function sqlToCamelCase(string $sqlString): string {
		$upperCaseExplodedTableName = array_map(static function (string $namePart) {
			return ucfirst($namePart);
		}, explode('_', $sqlString));
		return implode('', $upperCaseExplodedTableName);
	}

	/**
	 * Set primary key to what exists in database bases on unique key(s)
	 * @param string[] $uniqueFields
	 * @param T[] $elements
	 * @return void
	 */
	protected function restoreIds(
			array $uniqueFields,
			array $elements,
	): void {
		if (!$elements) {
			return;
		}
		$elements = array_values($elements);
		$uniqueKeyFromElement = static function ($element) use ($uniqueFields) {
			return implode('_', array_map(
					static function ($uniqueField) use ($element) {
						return $element->$uniqueField instanceof UnitEnum
								? $element->$uniqueField->name
								: $element->$uniqueField;
					},
					$uniqueFields,
			));
		};

		$elementsByUniqueFieldsKey = [];
		$allWheres = [];
		foreach ($elements as $element) {
			$elementsByUniqueFieldsKey[$uniqueKeyFromElement($element)] = $element;

			$wheres = [];
			foreach ($uniqueFields as $uniqueField) {
				$fieldAsSql = static::getSqlColFromField($uniqueField);
				$value = $element->$uniqueField instanceof UnitEnum
						? $element->$uniqueField->name
						: $element->$uniqueField;
				$wheres[] = "`$fieldAsSql` = '$value'";
			}
			$allWheres[] = implode(' AND ', $wheres);
		}

		$where = '(' . implode(') OR (', $allWheres) . ')';
		$existingElements = $this->get($where);

		$reflexionClass = new ReflectionClass($elements[0]::class);
		/** @var string[] $fieldsAttributesToUpdate */
		$fieldsAttributesToUpdate = [];
		$properties = $reflexionClass->getProperties();
		if ($reflexionClass->getParentClass() !== false) {
			array_push($properties, ...$reflexionClass->getParentClass()->getProperties());
		}
		foreach ($properties as $property) {
			foreach ($property->getAttributes() as $attribute) {
				foreach ($attribute->getArguments() as $argument) {
					if (is_object($argument) && $argument::class === ClassFieldEnum::class && in_array(
							$argument->name,
							[ClassFieldEnum::PRIMARY->name, ClassFieldEnum::IMMUTABLE->name, ClassFieldEnum::GENERATED->name],
							true,
					)) {
						$fieldsAttributesToUpdate[$property->getName()] = $property->getName();
						break;
					}
				}
			}
		}

		if (!$fieldsAttributesToUpdate) {
			return;
		}
		foreach ($existingElements as $existingElement) {
			foreach ($fieldsAttributesToUpdate as $fieldAttribute) {
				$elementsByUniqueFieldsKey[$uniqueKeyFromElement($existingElement)]->$fieldAttribute
						= $existingElement->$fieldAttribute;
			}
		}
	}

	abstract protected function getSqlColFromField(string $field): string;

	/**
	 * @return ReflectionProperty[]
	 */
	private function getReflectionProperties(): array {
		$reflectionClass = new ReflectionClass($this->getClass());
		return array_filter(
				$reflectionClass->getProperties(ReflectionProperty::IS_PUBLIC),
				static function (ReflectionProperty $reflectionProperty) {
					$name = $reflectionProperty->getType()?->getName();
					$isPrimitiveField = in_array(
							$name,
							[
								'bool',
								'float',
								'int',
								'string',
								'DateTime',
							],
							true,
					);
					if ($isPrimitiveField) {
						return true;
					}
					if ($name === null || in_array($name, self::getNotPdoCompatibleTypes(), true)) {
						return false;
					}
					return (new ReflectionClass($name))->isEnum();
				},
		);
	}

	/**
	 * @param T $item
	 * @return void
	 */
	public function updateItem(mixed $item): void {
		$table = $this->getTable();

		$primaryReflectionProperty = null;
		$sqlSetValues = [];
		foreach ($this->getReflectionProperties() as $reflectionProperty) {
			if ($this->isReflectionPropertyPrimary($reflectionProperty)) {
				$primaryReflectionProperty = $reflectionProperty;
			} else {
				$attributeSqlName = static::getSqlColFromField($reflectionProperty->getName());
				$quotedValue = $this->getQuotedValueOfReflectionProperty($item, $reflectionProperty);

				$sqlSetValues[] = "`$attributeSqlName` = $quotedValue";
			}
		}
		if ($primaryReflectionProperty === null) {
			throw new LogicException('Cannot update without primary key');
		}
		$primaryAttributeName = $primaryReflectionProperty->getName();
		if ($item->$primaryAttributeName === null) {
			throw new LogicException('Cannot update with primary key as null');
		}
		$primaryAttributeSqlName = static::getSqlColFromField($primaryAttributeName);
		$primaryQuotedValue = $this->getQuotedValueOfReflectionProperty($item, $primaryReflectionProperty);

		$sqlSetValuesAsSql = implode(', ', $sqlSetValues);

		$updateQuery = <<<SQL
			UPDATE `$table`
			SET $sqlSetValuesAsSql
			WHERE `$primaryAttributeSqlName` = $primaryQuotedValue
			SQL;

		$this->getPdo()->exec($updateQuery);
	}

	/**
	 * @param T $item
	 * @return void
	 */
	public function insertItem(mixed $item): void {
		$table = $this->getTable();

		$sqlFields = [];
		$sqlValues = [];
		$primaryReflectionProperty = null;
		foreach ($this->getReflectionProperties() as $reflectionProperty) {
			$attributeName = $reflectionProperty->getName();
			$attributeSqlName = static::getSqlColFromField($attributeName);
			$sqlFields[] = "`$attributeSqlName`";
			$sqlValues[] = $this->getQuotedValueOfReflectionProperty($item, $reflectionProperty);

			if ($this->isReflectionPropertyPrimary($reflectionProperty)) {
				$primaryReflectionProperty = $reflectionProperty;
			}
		}

		$sqlFieldsAsSql = implode(', ', $sqlFields);
		$sqlValuesAsSql = implode(', ', $sqlValues);

		$updateQuery = <<<SQL
			INSERT INTO `$table` ($sqlFieldsAsSql)
			VALUES ($sqlValuesAsSql)
			SQL;

		if ($this->getPdo()->inTransaction()) {
			$this->getPdo()->exec($updateQuery);

			if ($primaryReflectionProperty !== null) {
				$primaryAttributeName = $primaryReflectionProperty->getName();
				$item->$primaryAttributeName = $this->lastInsertedId();
			}
		} else {
			try {
				$this->getPdo()->beginTransaction();

				$this->getPdo()->exec($updateQuery);

				if ($primaryReflectionProperty !== null) {
					$primaryAttributeName = $primaryReflectionProperty->getName();
					$item->$primaryAttributeName = $this->lastInsertedId();
				}

				$this->getPdo()->commit();
			} catch (Exception $e) {
				$this->getPdo()->rollBack();
				throw $e;
			}
		}
	}

	/**
	 * @param T $element
	 * @param ReflectionProperty $reflectionProperty
	 * @return string
	 */
	private function getQuotedValueOfReflectionProperty(
			mixed $element,
			ReflectionProperty $reflectionProperty,
	): string {
		$attributeName = $reflectionProperty->getName();
		$rawValue = $element->$attributeName;
		if ($rawValue === null) {
			return 'NULL';
		}
		if ($rawValue instanceof UnitEnum) {
			$value = $rawValue->name;
		} else {
			$value = match ($reflectionProperty->getType()?->getName()) {
				'bool' => $rawValue ? '1' : '0',
				'float', 'int', 'string' => $rawValue,
				'DateTime' => date('Y-m-d H:i:s', $rawValue->getTimestamp()),
			};
		}

		$quotedValue = $this->getPdo()->quote($value);
		return $quotedValue !== false ? $quotedValue : "`$value`";
	}

	private function isReflectionPropertyPrimary(ReflectionProperty $reflectionProperty): bool {
		$classFieldAttributes = $reflectionProperty->getAttributes(ClassField::class);
		foreach ($classFieldAttributes as $classFieldAttribute) {
			/** @var ClassField $classField */
			$classField = $classFieldAttribute->newInstance();
			if (in_array(ClassFieldEnum::PRIMARY, $classField->classFieldEnums, true)) {
				return true;
			}
		}

		return false;
	}

	private function canReflectionPropertyBeUpdated(ReflectionProperty $reflectionProperty): bool {
		$classFieldAttributes = $reflectionProperty->getAttributes(ClassField::class);
		foreach ($classFieldAttributes as $classFieldAttribute) {
			/** @var ClassField $classField */
			$classField = $classFieldAttribute->newInstance();
			foreach ($classField->classFieldEnums as $classFieldEnum) {
				if (in_array($classFieldEnum, [
						ClassFieldEnum::PRIMARY,
						ClassFieldEnum::IMMUTABLE,
						ClassFieldEnum::GENERATED,
				], true)) {
					return false;
				}
			}
		}

		return true;
	}

	/**
	 * @param T[] $elements
	 * @return void
	 */
	public function saveElements(array $elements): void {
		if (!$elements) {
			return;
		}
		$elements = array_values($elements);
		$table = $this->getTable();

		$reflectionProperties = $this->getReflectionProperties();

		$sqlFields = [];
		foreach ($reflectionProperties as $reflectionProperty) {
			if ($this->canReflectionPropertyBeUpdated($reflectionProperty)) {
				$attributeName = $reflectionProperty->getName();
				$attributeSqlName = static::getSqlColFromField($attributeName);
				$sqlFields[] = "`$attributeSqlName`";
			}
		}

		$sqlValuesGroups = [];
		$sqlUpdatesGroups = [];
		$iterationCount = 500;
		$iterations = (int) floor(count($elements) / $iterationCount) + 1;
		for ($i = 0; $i < $iterations; $i++) {
			$partialElements = array_slice(
					$elements,
					$i * $iterationCount,
					$iterationCount,
			);
			foreach ($partialElements as $element) {
				$sqlValues = [];
				$sqlUpdates = [];
				foreach ($reflectionProperties as $reflectionProperty) {
					$attributeName = $reflectionProperty->getName();
					$attributeSqlName = static::getSqlColFromField($attributeName);

					if ($this->canReflectionPropertyBeUpdated($reflectionProperty)) {
						$sqlValues[] = $this->getQuotedValueOfReflectionProperty($element, $reflectionProperty);
						$sqlUpdates[] = "`$attributeSqlName`=VALUES(`$attributeSqlName`)";
					}
				}
				$sqlValuesGroups[] = $sqlValues;
				$sqlUpdatesGroups[] = $sqlUpdates;
			}

			$allSqlValues = [];
			foreach ($sqlValuesGroups as $sqlValues) {
				$allSqlValues[] = "(" . implode(', ', $sqlValues) . ")";
			}
			$allSqlUpdates = [];
			foreach ($sqlUpdatesGroups as $sqlUpdates) {
				$allSqlUpdates[] = implode(', ', $sqlUpdates);
			}

			$sqlFieldsAsSql = implode(', ', $sqlFields);
			$sqlValuesAsSql = implode(', ', $allSqlValues);

			$saveQuery = <<<SQL
				INSERT INTO `$table` ($sqlFieldsAsSql)
				VALUES $sqlValuesAsSql
				SQL;
			if (count($allSqlUpdates) > 0) {
				$saveQuery .= "\nON DUPLICATE KEY UPDATE " . implode(', ', $allSqlUpdates);
			}

			$this->getPdo()->exec($saveQuery);
		}
	}

}
