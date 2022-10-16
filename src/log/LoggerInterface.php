<?php

namespace SqlToCodeGenerator\log;

interface LoggerInterface {

	public function error(string $message): void;
	public function info(string $message): void;

}
