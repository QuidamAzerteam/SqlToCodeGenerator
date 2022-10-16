<?php

namespace SqlToCodeGenerator\test\sql;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use SqlToCodeGenerator\sql\SqlUtils;

class SqlUtilsTest extends TestCase {

	public function testExceptionOnGetPdoContainer(): void {
		$this->expectException(RuntimeException::class);
		SqlUtils::getPdoContainer();
	}

}
