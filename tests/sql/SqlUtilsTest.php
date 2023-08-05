<?php

namespace SqlToCodeGenerator\test\sql;

use PHPUnit\Framework\TestCase;
use SqlToCodeGenerator\sql\PdoContainer;
use SqlToCodeGenerator\sql\SqlUtils;

class SqlUtilsTest extends TestCase {

	public function testInitFromScratch(): void {
		SqlUtils::initFromScratch(
				'dbName',
				'host',
				'port',
				'user',
				'password',
		);

		$this->assertNotNull(SqlUtils::getPdoContainer());
	}

	public function testInitFromPdoContainer(): void {
		$pdoContainer = $this->createMock(PdoContainer::class);
		SqlUtils::initFromPdoContainer($pdoContainer);

		$this->assertSame($pdoContainer, SqlUtils::getPdoContainer());
	}

}
