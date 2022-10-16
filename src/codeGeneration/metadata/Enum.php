<?php

namespace SqlToCodeGenerator\codeGeneration\metadata;

use PHPUnit\Framework\TestCase;
use SqlToCodeGenerator\codeGeneration\bean\Line;
use SqlToCodeGenerator\codeGeneration\builder\ClassBuilder;
use SqlToCodeGenerator\codeGeneration\builder\EnumBuilder;
use SqlToCodeGenerator\codeGeneration\builder\FunctionBuilder;

class Enum {

	public string $basePackage;
	public string $namespace;
	public string $name;
	/** @var string[] */
	public array $values = array();
	public string|null $sqlComment = null;

	public function getFullName(): string {
		return "\\$this->basePackage\\$this->namespace\\$this->name";
	}

	private function getBaseEnumBuilder(): EnumBuilder {
		$enumBuilder = EnumBuilder::create(
				basePackage: $this->basePackage,
				namespace: $this->namespace,
				name: $this->name,
				fields: $this->values,
		);

		if ($this->sqlComment) {
			$enumBuilder->addDocLines($this->sqlComment);
		}

		return $enumBuilder;
	}

	public function getPhpFileContent(): string {
		$enumBuilder = $this->getBaseEnumBuilder();

		$phpFunctionBuilder = FunctionBuilder::create(
				name: 'getShortText',
				returnType: 'string',
				isFinal: true,
				lines: array(
					Line::create("return match(\$this) {"),
				),
		);
		$enumBuilder->addPhpFunctionBuilders($phpFunctionBuilder);

		foreach ($this->values as $valueIndex => $value) {
			$valueAsShortText = ucwords(
					implode(
							' ',
							explode('_', strtolower($value))
					)
			);
			$phpFunctionBuilder->addLines(Line::create(
					"self::$value => '$valueAsShortText',",
					$valueIndex === 0 ? 1 : 0 // Only first line get increment
			));
		}
		$phpFunctionBuilder->addLines(Line::create("};",-1));

		return $enumBuilder->getPhpFileContent();
	}

	public function getJsFileContent(): string {
		return $this->getBaseEnumBuilder()->getJsFileContent();
	}

	public function getPhpTestFileContent(string $testNamespacePart): string {
		$classBuilder = ClassBuilder::create(
				basePackage: $this->basePackage,
				namespace: "$testNamespacePart\\$this->namespace",
				name: "{$this->name}Test",
				extends: 'TestCase',
				imports: array(
					TestCase::class,
					"$this->basePackage\\$this->namespace\\$this->name",
				),
		);

		foreach ($this->values as $value) {
			$valueAsShortText = implode(
					'',
					array_map(
							static fn (string $word) => ucwords($word),
							explode('_', strtolower($value))
					)
			);

			$phpFunctionBuilder = FunctionBuilder::create(
					name: "test$valueAsShortText",
					returnType: 'void',
					lines: array(
						Line::create("\$this->assertNotNull($this->name::$value);"),
					),
			);
			$classBuilder->addPhpFunctionBuilders($phpFunctionBuilder);
		}

		return $classBuilder->getPhpFileContent();
	}

}
