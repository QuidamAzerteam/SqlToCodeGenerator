<?php

namespace SqlToCodeGenerator\utils;

use InvalidArgumentException;
use RuntimeException;

abstract class FileUtils {

	private final function __construct() {}

	public static function recursiveDelete($dirPath): void {
		if (!file_exists($dirPath)) {
			return;
		}
		if (!is_dir($dirPath)) {
			throw new InvalidArgumentException("$dirPath must be a directory");
		}
		if (!str_ends_with($dirPath, '/')) {
			$dirPath .= '/';
		}
		$files = glob($dirPath . '*', GLOB_MARK);
		foreach ($files as $file) {
			if (is_dir($file)) {
				self::recursiveDelete($file);
			} else {
				unlink($file);
			}
		}
		rmdir($dirPath);
	}

	public static function createDir(string $dirPath): void {
		if (!mkdir($dirPath) && !is_dir($dirPath)) {
			throw new RuntimeException(sprintf('Directory "%s" was not created', $dirPath));
		}
	}

}
