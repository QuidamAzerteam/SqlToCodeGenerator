<?php

namespace SqlToCodeGenerator\test\sqlToMetaCode;

use SqlToCodeGenerator\sqlToMetaCode\bean\Table;
use SqlToCodeGenerator\sqlToMetaCode\SqlToMetaCodeUtils;
use PHPUnit\Framework\TestCase;

class SqlToMetaCodeUtilsTest extends TestCase {

	public function testNoPropertySoNoBeans(): void {
		$this->assertEmpty(SqlToMetaCodeUtils::getBeansFromMetaCodeBeans(
				[new Table(
						'',
						'',
						'',
						'',
						'',
						null,
						null,
						null,
						null,
						null,
						null,
						null,
						null,
						null,
						null,
						null,
						null,
						null,
						null,
						null,
						'',
						null,
						null,
				)],
				array(),
				array()
		));
	}

}
