<?php

namespace SqlToCodeGenerator\sql;

use BackedEnum;
use DateTime;
use LogicException;
use PDO;
use PDOException;
use ReflectionClass;
use ReflectionException;
use ReflectionObject;
use ReflectionProperty;

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
				$property = $itemReflection->getProperty($field);
				// https://www.php.net/manual/en/language.types.declarations.php
				$typeName = $property->getType()?->getName();
				switch ($typeName) {
					case 'array':
					case 'callable':
					case 'iterable':
					case 'object':
					case 'mixed':
						throw new LogicException("PDO cannot return $typeName");

					case 'bool':
						$item->$field = (bool) $colValue;
						break;
					case 'float':
						$item->$field = (float) $colValue;
						break;
					case 'int':
						$item->$field = (int) $colValue;
						break;
					case 'string':
						$item->$field = (string) $colValue;
						break;
					case 'DateTime':
						$item->$field = new DateTime($colValue);
						break;

					default:
						try {
							$maybeItsAClass = new ReflectionClass($typeName);
							if ($maybeItsAClass->isEnum()) {
								$item->$field = $colValue !== null
										? $maybeItsAClass
												->getMethod('from')
												->invoke(null, $colValue)
										: $colValue;
								break;
							}
						} catch (ReflectionException) {}

						throw new LogicException("PDO type return not implemented: $typeName");
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
		$uniqueKeyFromElement = static function ($element) use ($uniqueFields) {
			return implode('_', array_map(
					static function ($uniqueField) use ($element) {
						return $element->$uniqueField;
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
				$value = $element->$uniqueField;
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

	public function saveElements(array $elements): void {
		$table = $this->getTable();

		$reflectionClass = new ReflectionClass($this->getClass());
		$reflectionProperties = array_filter(
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

					$sqlValues[] = $quotedValue;
					$sqlUpdates[] = "`$attributeSqlName`=VALUES(`$attributeSqlName`)";
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
