<?php

namespace SqlToCodeGenerator\log;

use Exception;

class BasicLogger implements LoggerInterface {

	public function error(string $message): void {
		throw new Exception($message);
	}

	public function info(string $message): void {
		echo $message . "\n";
	}

}
