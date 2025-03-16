<?php

namespace SqlToCodeGenerator\test\codeGeneration\utils;

use LogicException;
use PHPUnit\Framework\TestCase;
use SqlToCodeGenerator\codeGeneration\utils\CheckUtils;

class CheckUtilsTest extends TestCase {

	public function testValidFullNamespaceDoesNotThrowException(): void {
		$namespace = 'SqlToCodeGenerator\\codeGeneration\\utils';
		$this->expectNotToPerformAssertions(); // No exception should be thrown
		CheckUtils::checkPhpFullNamespace($namespace);
	}

	public function testEmptyNamespaceThrowsLogicException(): void {
		$namespace = '\\';
		$this->expectException(LogicException::class);
		$this->expectExceptionMessage('If does not make sense to have an empty namespace');
		CheckUtils::checkPhpFullNamespace($namespace);
	}

	public function testInvalidNamespaceThrowsLogicException(): void {
		$namespace = 'Invalid-Namespace';
		$this->expectException(LogicException::class);
		$this->expectExceptionMessage('Invalid-Namespace does not match a valid PHP namespace');
		CheckUtils::checkPhpFullNamespace($namespace);
	}

	public function testValidFieldNameDoesNotThrowException(): void {
		$fieldName = 'validFieldName';
		$this->expectNotToPerformAssertions(); // No exception should be thrown
		CheckUtils::checkPhpFieldName($fieldName);
	}

	public function testEmptyFieldNameThrowsLogicException(): void {
		$fieldName = '';
		$this->expectException(LogicException::class);
		$this->expectExceptionMessage('If does not make sense to have an empty field name');
		CheckUtils::checkPhpFieldName($fieldName);
	}

	public function testThisFieldNameThrowsLogicException(): void {
		$fieldName = 'this';
		$this->expectException(LogicException::class);
		$this->expectExceptionMessage('$this is a special variable that cannot be assigned');
		CheckUtils::checkPhpFieldName($fieldName);
	}

	public function testInvalidFieldNameThrowsLogicException(): void {
		$fieldName = '123invalid';
		$this->expectException(LogicException::class);
		$this->expectExceptionMessage(htmlentities($fieldName) . ' does not match a valid PHP variable');
		CheckUtils::checkPhpFieldName($fieldName);
	}

	public function testValidPhpTypeDoesNotThrowException(): void {
		$phpType = 'Some\\Valid\\Type';
		$this->expectNotToPerformAssertions(); // No exception should be thrown
		CheckUtils::checkPhpType($phpType);
	}

	public function testClassKeywordThrowsLogicException(): void {
		$phpType = 'class';
		$this->expectException(LogicException::class);
		$this->expectExceptionMessage('class is a keyword');
		CheckUtils::checkPhpType($phpType);
	}

	public function testPhpTypeStartingWithDollarThrowsLogicException(): void {
		$phpType = '$TypeName';
		$this->expectException(LogicException::class);
		$this->expectExceptionMessage('You cannot start a php type with $');
		CheckUtils::checkPhpType($phpType);
	}

	public function testInvalidPhpTypeThrowsLogicException(): void {
		$phpType = 'Invalid Type!';
		$this->expectException(LogicException::class);
		$this->expectExceptionMessage(htmlentities($phpType) . ' does not match a valid PHP variable');
		CheckUtils::checkPhpType($phpType);
	}

	public function testUniqueFieldsDoesNotThrowException(): void {
		$fields = ['field1', 'field2', 'field3'];
		$this->expectNotToPerformAssertions(); // No exception should be thrown
		CheckUtils::checkUniqueFields($fields);
	}

	public function testDuplicateFieldsThrowsLogicException(): void {
		$fields = ['field1', 'field2', 'field1'];
		$this->expectException(LogicException::class);
		$this->expectExceptionMessage('Duplicates in array found: field1');
		CheckUtils::checkUniqueFields($fields);
	}

	public function testEmptyFieldThrowsLogicException(): void {
		$fields = ['field1', ''];
		$this->expectException(LogicException::class);
		$this->expectExceptionMessage('A field cannot be an empty string');
		CheckUtils::checkUniqueFields($fields);
	}

	public function testNonStringOrIntegerFieldThrowsLogicException(): void {
		$fields = ['field1', [], 'field3'];
		$this->expectException(LogicException::class);
		$this->expectExceptionMessage('A field must be a string or an integer');
		CheckUtils::checkUniqueFields($fields);
	}

	public function testMultipleDuplicatesThrowsLogicExceptionWithAccurateMessage(): void {
		$fields = ['field1', 'field2', 'field1', 'field2'];
		$this->expectException(LogicException::class);
		$this->expectExceptionMessage('Duplicates in array found: field1, field2');
		CheckUtils::checkUniqueFields($fields);
	}

	public function testValidObjectAndClassNameDoesNotThrowException(): void {
		$className = 'Test';
		$value = $this->getMockBuilder($className)->getMock();
		$this->expectNotToPerformAssertions(); // No exception should be thrown
		CheckUtils::checkIfValueIsAClass($value, $className);
	}

	public function testNonObjectValueThrowsLogicException(): void {
		$this->expectException(LogicException::class);
		$this->expectExceptionMessage('Value is not an object therefore cannot belong to a class');
		CheckUtils::checkIfValueIsAClass('test', '');
	}

	public function testObjectOfIncorrectClassThrowsLogicException(): void {
		$value = $this->getMockBuilder('Test')->getMock();
		$this->expectException(LogicException::class);
		$this->expectExceptionMessage("Value does not belong to class (\"" . $value::class
				. "\" given vs \"Test2\" expected)");
		CheckUtils::checkIfValueIsAClass($value, 'Test2');
	}

}
