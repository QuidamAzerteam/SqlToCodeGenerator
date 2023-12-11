<?php

namespace SqlToCodeGenerator\codeGeneration\metadata;

use PHPUnit\Framework\TestCase;
use SqlToCodeGenerator\codeGeneration\bean\Line;
use SqlToCodeGenerator\codeGeneration\builder\ClassBuilder;
use SqlToCodeGenerator\codeGeneration\builder\EnumBuilder;
use SqlToCodeGenerator\codeGeneration\builder\FunctionBuilder;
use SqlToCodeGenerator\codeGeneration\builder\FunctionParameterBuilder;

class Enum {

	public string $basePackage;
	public string $namespace;
	public string $name;
	/** @var string[] */
	public array $values = [];
	public string|null $sqlComment = null;

	public function getFullNamespace(): string {
		return "\\$this->basePackage\\$this->namespace";
	}

	public function getFullName(): string {
		return "{$this->getFullNamespace()}\\$this->name";
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

		$getShortTextFunction = FunctionBuilder::create(
				name: 'getShortText',
				returnType: 'string',
				isFinal: true,
				lines: [
					Line::create("return match(\$this) {"),
				],
		);
		$enumBuilder->addPhpFunctionBuilders($getShortTextFunction);
		$tryFromFunction = FunctionBuilder::create(
				name: 'tryFrom',
				returnType: '?self',
				isStatic: true,
				parameterBuilders: [FunctionParameterBuilder::create(
						name: 'value',
						type: 'string',
				)],
				lines: [
					Line::create("foreach($this->name::cases() as \$case) {"),
					Line::create("if (strcasecmp(\$case->name, \$value) === 0) {", 1),
					Line::create("return \$case;", 1),
					Line::create("}", -1),
					Line::create("}", -1),
					Line::create("return null;"),
				],
		);
		$enumBuilder->addPhpFunctionBuilders($tryFromFunction);


		foreach ($this->values as $valueIndex => $value) {
			$valueAsShortText = ucwords(
					implode(
							' ',
							explode('_', strtolower($value)),
					),
			);
			$getShortTextFunction->addLines(Line::create(
					"self::$value => '$valueAsShortText',",
					// Only first line get increment
					$valueIndex === 0 ? 1 : 0,
			));
		}
		$getShortTextFunction->addLines(Line::create("};", -1));

		return $enumBuilder->getPhpFileContent();
	}

	public function getJsFileContent(): string {
		return $this->getBaseEnumBuilder()->getJsFileContent();
	}

	public function getPhpTestFileContent(string $testNamespacePart): string {
		$classBuilder = ClassBuilder::create(
				basePackage: $this->basePackage,
				namespace: "$testNamespacePart\\$this->namespace",
				name: ucfirst($this->name) . 'Test',
				extends: 'TestCase',
				imports: [
					TestCase::class,
					"$this->basePackage\\$this->namespace\\$this->name",
				],
		);

		foreach ($this->values as $value) {
			$valueAsShortText = implode(
					'',
					array_map(
							static fn(string $word) => ucwords($word),
							explode('_', strtolower($value)),
					),
			);

			$phpFunctionBuilder = FunctionBuilder::create(
					name: "test$valueAsShortText",
					returnType: 'void',
					lines: [
						Line::create("\$this->assertNotNull($this->name::$value);"),
					],
			);
			$classBuilder->addPhpFunctionBuilders($phpFunctionBuilder);
		}

		return $classBuilder->getPhpFileContent();
	}

}
