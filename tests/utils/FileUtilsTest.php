<?php

namespace SqlToCodeGenerator\test\utils;

use SqlToCodeGenerator\utils\FileUtils;
use PHPUnit\Framework\TestCase;

class FileUtilsTest extends TestCase {

	public function testCreateDir() {
		$dirName = 'dirName';

		FileUtils::createDir($dirName);
		$this->assertTrue(file_exists($dirName));
		$this->assertTrue(is_dir($dirName));

		if (file_exists($dirName)) {
			rmdir($dirName);
		}
	}

	public function testRecursiveDelete() {
		$dirName = 'dirName';
		$underDirName = 'dirName1';

		mkdir($dirName);
		mkdir($dirName . '/' . $underDirName);

		FileUtils::recursiveDelete($dirName);
		$this->assertFalse(file_exists($dirName . '/' . $underDirName));
		$this->assertFalse(file_exists($dirName));
	}

}
