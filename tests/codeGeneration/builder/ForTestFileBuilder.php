<?php

namespace SqlToCodeGenerator\test\codeGeneration\builder;

use LogicException;
use SqlToCodeGenerator\codeGeneration\builder\FileBuilder;

class ForTestFileBuilder extends FileBuilder {

	public function getFileTypeWithName(): string {
		throw new LogicException('This class is only here for testing, not for usage');
	}

	public function getFieldsPhpFileContent(string $baseIndentation = ''): string {
		throw new LogicException('This class is only here for testing, not for usage');
	}

	public function getFieldsJsFileContent(): string {
		throw new LogicException('This class is only here for testing, not for usage');
	}
}
