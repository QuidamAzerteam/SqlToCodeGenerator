<?php

namespace SqlToCodeGenerator\log;

class NoInfoLogger extends BasicLogger {

	public function info(string $message): void {}

}
