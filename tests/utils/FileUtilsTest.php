<?php

namespace SqlToCodeGenerator\test\utils;

use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\TestCase;
use SqlToCodeGenerator\utils\FileUtils;

class FileUtilsTest extends TestCase {

	private const DIR_NAME = 'dirName';

	private static function cleanup(): void {
		if (file_exists(self::DIR_NAME)) {
			if (is_dir(self::DIR_NAME)) {
				FileUtils::recursiveDelete(self::DIR_NAME);
			} else {
				unlink(self::DIR_NAME);
			}
		}
	}

	public static function setUpBeforeClass(): void {
		self::cleanup();
	}

	public function tearDown(): void {
		self::cleanup();
	}

	public function testCreateDir() {
		FileUtils::createDir(self::DIR_NAME);
		$this->assertTrue(file_exists(self::DIR_NAME));
		$this->assertTrue(is_dir(self::DIR_NAME));
	}

	public function testCreateDirFailBecauseExists() {
		file_put_contents(self::DIR_NAME, '');
		$this->expectException(LogicException::class);
		FileUtils::createDir(self::DIR_NAME);
	}

	public function testRecursiveDelete() {
		$underDirName = 'dirName1';

		mkdir(self::DIR_NAME);
		mkdir(self::DIR_NAME . '/' . $underDirName);

		FileUtils::recursiveDelete(self::DIR_NAME);
		$this->assertFalse(file_exists(self::DIR_NAME . '/' . $underDirName));
		$this->assertFalse(file_exists(self::DIR_NAME));
	}

	public function testRecursiveDeleteBadDirPath() {
		$this->expectException(InvalidArgumentException::class);
		file_put_contents(self::DIR_NAME, '');
		FileUtils::recursiveDelete(self::DIR_NAME);
	}

}
