<?php

namespace SqlToCodeGenerator\test\sql\bean;

use DateTime;
use SqlToCodeGenerator\codeGeneration\attribute\ClassField;
use SqlToCodeGenerator\codeGeneration\attribute\ClassFieldEnum;

class SqlDaoTestCompleteClass {

	// Compatible with PDO types
	public bool $bool;
	public float $float;
	#[ClassField(ClassFieldEnum::PRIMARY)]
	public int $int;
	public string $string;
	public DateTime $dateTime;
	public SqlDaoTestBackedEnum $sqlDaoTestBackedEnum;

	// Not compatible with PDO types
	public array $array;
	/** @var callable */
	public $callable;
	public iterable $iterable;
	public object $object;
	public mixed $mixed;

}
