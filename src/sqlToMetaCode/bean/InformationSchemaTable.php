<?php

namespace SqlToCodeGenerator\sqlToMetaCode\bean;

interface InformationSchemaTable {

	public static function createFromSqlRow(array $sqlRow): static;

}
