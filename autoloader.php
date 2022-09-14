<?php

spl_autoload_register(static function($className) {
	$ownNamespaceStart = 'SqlToCodeGenerator';
	$srcDirectoryName = 'src';

	$filePath = '';
	if (str_starts_with($className, $ownNamespaceStart)) {
		$className = str_replace($ownNamespaceStart, '', $className);
		$filePath = rtrim(__DIR__, '/') . '/' . $srcDirectoryName . '/'
				. trim(str_replace('\\', '/', $className), '/') . '.php';

		if (file_exists($filePath)) {
			require_once $filePath;
		}
		// Debug only
//		else {
//			throw new \Exception('requiring "' . $className . '" on file path "' . $filePath . '"');
//		}
	}
});
