<?php

namespace SqlToCodeGenerator\codeGeneration\enums;

/**
 * @link https://www.php.net/manual/language.oop5.visibility.php
 */
enum Visibility: string {

	case PUBLIC = 'public';
	case PROTECTED = 'protected';
	case PRIVATE = 'private';

}
