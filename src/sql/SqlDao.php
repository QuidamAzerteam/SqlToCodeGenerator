<?php

namespace SqlToCodeGenerator\sql;

use BackedEnum;
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

abstract class SqlDao {

	abstract protected function getTable(): string;
	abstract protected function getClass(): string;

	private PdoContainer $pdoContainer;

	public function __construct(PdoContainer $pdoContainer = null) {
		$this->pdoContainer = $pdoContainer ?: SqlUtils::getPdoContainer();
	}

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

	public function lastInsertedId(): int {
		return $this->getPdo()->lastInsertId();
	}

	public function fetchFromQuery(string $query): array {
		return $this->getPdo()->query(
				$query,
				PDO::FETCH_ASSOC
		)->fetchAll();
	}

	/**
	 * @param string $where
	 * @param string $groupBy
	 * @param string $orderBy
	 * @param string $limit
	 * @return array
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
				PDO::FETCH_ASSOC
		);
		$result = $statement->fetchAll();
		$className = $this->getClass();
		$items = array();
		foreach ($result as $row) {
			$item = new $className();
			$itemReflection = new ReflectionObject($item);
			foreach ($row as $colKey => $colValue) {
				$field = lcfirst(self::sqlToCamelCase($colKey));

				if ($colValue === null) {
					$item->$field = null;
				} else {
					$property = $itemReflection->getProperty($field);
					// https://www.php.net/manual/en/language.types.declarations.php
					$typeName = $property->getType()?->getName();

					$item->$field = match($typeName) {
						'array', 'callable', 'iterable', 'object', 'mixed' => throw new LogicException("PDO cannot return $typeName"),

						'bool' => (bool) $colValue,
						'float' => (float) $colValue,
						'int' => (int) $colValue,
						'string' => (string) $colValue,
						'DateTime' => new DateTime($colValue),

						default => (static function () use ($colValue, $item, $typeName) {
							try {
								$maybeItsAClass = new ReflectionClass($typeName);
								if ($maybeItsAClass->isEnum()) {
									return $colValue !== null
											? $maybeItsAClass
													->getMethod('from')
													->invoke(null, $colValue)
											: $colValue;
								}
							} catch (ReflectionException) {}

							throw new LogicException("PDO type return not implemented: $typeName");
						})()
					};
				}
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
	 * @param array $elements
	 * @return void
	 */
	protected function restoreIds(
			array $uniqueFields,
			array $elements
	): void {
		if (!$elements) {
			return;
		}
		$uniqueKeyFromElement = static function ($element) use ($uniqueFields) {
			return implode('_', array_map(
					static function ($uniqueField) use ($element) {
						return $element->$uniqueField instanceof BackedEnum
								? $element->$uniqueField->value
								: $element->$uniqueField;
					},
					$uniqueFields
			));
		};

		$elementsByUniqueFieldsKey = array();
		$allWheres = array();
		foreach ($elements as $element) {
			$elementsByUniqueFieldsKey[$uniqueKeyFromElement($element)] = $element;

			$wheres = array();
			foreach ($uniqueFields as $uniqueField) {
				$fieldAsSql = static::getSqlColFromField($uniqueField);
				$value = $element->$uniqueField instanceof BackedEnum
						? $element->$uniqueField->value
						: $element->$uniqueField;
				$wheres[] = "`$fieldAsSql` = '$value'";
			}
			$allWheres[] = implode(' AND ', $wheres);
		}

		$where = '(' . implode(') OR (', $allWheres) . ')';
		$existingElements = $this->get($where);

		foreach ($existingElements as $existingElement) {
			$elementsByUniqueFieldsKey[$uniqueKeyFromElement($existingElement)]->id = $existingElement->id;
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
							true
					);
					if ($isPrimitiveField) {
						return true;
					}
					if ($name === 'array') {
						return false;
					}
					return (new ReflectionClass($name))->isEnum();
				}
		);
	}

	public function updateItem(mixed $item): void {
		$table = $this->getTable();

		$primaryReflectionProperty = null;
		$sqlSetValues = array();
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

		$updateQuery = "
			UPDATE `$table`
			SET $sqlSetValuesAsSql
			WHERE `$primaryAttributeSqlName` = $primaryQuotedValue
		";

		$this->getPdo()->exec($updateQuery);
	}

	public function insertItem(mixed $item): void {
		$table = $this->getTable();

		$sqlFields = array();
		$sqlValues = array();
		$primaryReflectionProperty = null;
		foreach ($this->getReflectionProperties() as $reflectionProperty) {
			$attributeName = $reflectionProperty->getName();
			$attributeSqlName = static::getSqlColFromField($attributeName);
			$sqlFields[] = "`$attributeSqlName`";
			$sqlValues[] =  $this->getQuotedValueOfReflectionProperty($item, $reflectionProperty);

			if ($this->isReflectionPropertyPrimary($reflectionProperty)) {
				$primaryReflectionProperty = $reflectionProperty;
			}
		}

		$sqlFieldsAsSql = implode(', ', $sqlFields);
		$sqlValuesAsSql = implode(', ', $sqlValues);

		$updateQuery = "
			INSERT INTO `$table` ($sqlFieldsAsSql)
			VALUES ($sqlValuesAsSql)
		";

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

	private function getQuotedValueOfReflectionProperty(
			mixed $element,
			ReflectionProperty $reflectionProperty
	): string {
		$attributeName = $reflectionProperty->getName();
		$rawValue = $element->$attributeName;
		if ($rawValue === null) {
			$quotedValue = 'NULL';
		} else if ($rawValue instanceof BackedEnum) {
			// Generated Enums are BackedEnum with value as string = sql value
			$quotedValue = $this->getPdo()->quote($rawValue->value);
		} else {
			$value = match ($reflectionProperty->getType()?->getName()) {
				'bool' => $rawValue ? '1' : '0',
				'float', 'int', 'string' => $rawValue,
				'DateTime' => date('Y-m-d H:i:s', $rawValue->getTimestamp()),
			};
			$quotedValue = $this->getPdo()->quote($value);
		}

		return $quotedValue;
	}

	private function isReflectionPropertyPrimary(ReflectionProperty $reflectionProperty): bool {
		$classFieldAttributes = $reflectionProperty->getAttributes(ClassField::class);
		foreach ($classFieldAttributes as $classFieldAttribute) {
			/** @var ClassField $classField */
			$classField = $classFieldAttribute->newInstance();
			if ($classField->classFieldEnum === ClassFieldEnum::PRIMARY) {
				return true;
			}
		}

		return false;
	}

	public function saveElements(array $elements): void {
		$table = $this->getTable();

		$reflectionProperties = $this->getReflectionProperties();

		$sqlFields = array();
		foreach ($reflectionProperties as $reflectionProperty) {
			$attributeName = $reflectionProperty->getName();
			$attributeSqlName = static::getSqlColFromField($attributeName);
			$sqlFields[] = "`$attributeSqlName`";
		}

		$sqlValuesGroups = array();
		$sqlUpdatesGroups = array();
		$iterationCount = 500;
		$iterations = (int) floor(count($elements) / $iterationCount) + 1;
		for ($i = 0; $i < $iterations; $i++) {
			$partialElements = array_slice(
					$elements,
					$i * $iterationCount,
					$iterationCount
			);
			foreach ($partialElements as $element) {
				$sqlValues = array();
				$sqlUpdates = array();
				foreach ($reflectionProperties as $reflectionProperty) {
					$attributeName = $reflectionProperty->getName();
					$attributeSqlName = static::getSqlColFromField($attributeName);

					$sqlValues[] = $this->getQuotedValueOfReflectionProperty($element, $reflectionProperty);

					$isPrimaryField = $this->isReflectionPropertyPrimary($reflectionProperty);
					if (!$isPrimaryField) {
						$sqlUpdates[] = "`$attributeSqlName`=VALUES(`$attributeSqlName`)";
					}
				}
				$sqlValuesGroups[] = $sqlValues;
				$sqlUpdatesGroups[] = $sqlUpdates;
			}

			$allSqlValues = array();
			foreach ($sqlValuesGroups as $sqlValues) {
				$allSqlValues[] = "(" . implode(', ', $sqlValues) . ")";
			}
			$allSqlUpdates = array();
			foreach ($sqlUpdatesGroups as $sqlUpdates) {
				$allSqlUpdates[] = implode(', ', $sqlUpdates);
			}

			$sqlFieldsAsSql = implode(', ', $sqlFields);
			$sqlValuesAsSql = implode(', ', $allSqlValues);
			$sqlUpdatesAsSql = implode(', ', $allSqlUpdates);

			$saveQuery = "
				INSERT INTO `$table` ($sqlFieldsAsSql)
				VALUES $sqlValuesAsSql
				ON DUPLICATE KEY UPDATE $sqlUpdatesAsSql
			";

			$this->getPdo()->exec($saveQuery);
		}
	}

}
