<?php

namespace SqlToCodeGenerator\test\log;

use Exception;
use PHPUnit\Framework\TestCase;
use SqlToCodeGenerator\log\BasicLogger;

class BasicLoggerTest extends TestCase {

	public function testError(): void {
		$this->expectException(Exception::class);
		$this->expectExceptionMessage('test');
		(new BasicLogger())->error('test');
	}

	public function testInfo(): void {
		$this->expectOutputString("test\n");
		(new BasicLogger())->info('test');
	}

}
