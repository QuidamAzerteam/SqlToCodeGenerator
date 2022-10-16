<?php

namespace SqlToCodeGenerator\test\fileGeneration;

use PDO;
use PDOStatement;
use PHPUnit\Framework\TestCase;
use SqlToCodeGenerator\codeGeneration\metadata\Bean;
use SqlToCodeGenerator\CodeGenerator;
use SqlToCodeGenerator\log\NoInfoLogger;
use SqlToCodeGenerator\sqlToMetaCode\dao\SqlToMetaCodeDao;
use SqlToCodeGenerator\utils\FileUtils;

class CodeGeneratorTest extends TestCase {

	protected function setUp(): void {
		mkdir('phpDirName');
		mkdir('jsDirName');
		mkdir('testsDirName');
	}

	protected function tearDown(): void {
		FileUtils::recursiveDelete('phpDirName');
		FileUtils::recursiveDelete('jsDirName');
		FileUtils::recursiveDelete('testsDirName');
	}

	public function testEmptyGeneration(): void {
		$pdoStatement = $this->createMock(PDOStatement::class);
		$pdoStatement->method('fetchAll')->willReturn(array());

		$pdo = $this->createMock(PDO::class);
		$pdo->method('prepare')->willReturn($pdoStatement);

		$sqlToMetaCodeDao = $this->createMock(SqlToMetaCodeDao::class);

		$generator = CodeGenerator::create(
				sqlToMetaCodeDao: $sqlToMetaCodeDao,
				log: new NoInfoLogger(),
				basePackage: 'basePackage',
				phpSourceDirectory: 'phpDirName',
				jsSourceDirectory: 'jsDirName',
				genDirName: 'genDirName',
				beanDirName: 'beanDirName',
				enumsDirName: 'enumsDirName',
				daoDirName: 'daoDirName',
				testDirName: 'testsDirName',
		);

		$generator->generate();

		$this->assertTrue(file_exists('phpDirName/genDirName'));
		$this->assertTrue(file_exists('phpDirName/genDirName/beanDirName'));
		$this->assertTrue(file_exists('phpDirName/genDirName/enumsDirName'));
		$this->assertTrue(file_exists('phpDirName/genDirName/daoDirName'));

		$this->assertTrue(file_exists('jsDirName/genDirName/beanDirName'));
		$this->assertTrue(file_exists('jsDirName/genDirName/enumsDirName'));

		$this->assertTrue(file_exists('testsDirName/genDirName'));
		$this->assertTrue(file_exists('testsDirName/genDirName/beanDirName'));
		$this->assertTrue(file_exists('testsDirName/genDirName/enumsDirName'));
		$this->assertTrue(file_exists('testsDirName/genDirName/daoDirName'));
	}

	public function testSimpleBeanGeneration(): void {
		$pdoStatement = $this->createMock(PDOStatement::class);
		$pdoStatement->method('fetchAll')->willReturn(array());

		$pdo = $this->createMock(PDO::class);
		$pdo->method('prepare')->willReturn($pdoStatement);

		$sqlToMetaCodeDao = $this->createMock(SqlToMetaCodeDao::class);
		$bean = new Bean();
		$bean->sqlTable = 'sql_table';
		$sqlToMetaCodeDao->method('getBeansFromSql')->willReturn(array($bean));

		$generator = CodeGenerator::create(
				sqlToMetaCodeDao: $sqlToMetaCodeDao,
				log: new NoInfoLogger(),
				basePackage: 'basePackage',
				phpSourceDirectory: 'phpDirName',
				jsSourceDirectory: 'jsDirName',
				genDirName: 'genDirName',
				beanDirName: 'beanDirName',
				enumsDirName: 'enumsDirName',
				daoDirName: 'daoDirName',
				testDirName: null,
		);

		$generator->generate();

		$this->assertTrue(file_exists('phpDirName/genDirName/beanDirName/SqlTable.php'));
		$this->assertTrue(file_exists('phpDirName/genDirName/daoDirName/SqlTableDao.php'));
		$this->assertTrue(file_exists('jsDirName/genDirName/beanDirName/SqlTable.js'));
	}

}
