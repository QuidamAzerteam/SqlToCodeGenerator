<?php

namespace SqlToCodeGenerator\generation\metadata;

class Enum {
	public string $basePackage;
	public string $namespace;
	public string $name;
	/** @var string[] */
	public array $values = array();
	public string|null $sqlComment = null;

	public function getPhpFileContent(): string {
		$fileContent = "<?php\n\n";

		$fileContent .= "namespace $this->basePackage\\" . $this->namespace . ";\n";
		$fileContent .= "\n";

		$phpDocElements = array();
		if ($this->sqlComment) {
			$phpDocElements[] = $this->sqlComment;
		}
		if ($phpDocElements) {
			$fileContent .= "/**\n";
			foreach ($phpDocElements as $phpDocElement) {
				$fileContent .= " * $phpDocElement\n";
			}
			$fileContent .= " */\n";
		}

		$fileContent .= "enum $this->name: string {\n";

		foreach ($this->values as $value) {
			$fileContent .= "	case $value = '$value';\n";
		}
		$fileContent .= "\n";

		$fileContent .= "	public function getShortText(): string {\n";
		$fileContent .= "		return match(\$this) {\n";
		foreach ($this->values as $value) {
			$valueAsShortText = ucwords(
					implode(
							' ',
							explode('_', strtolower($value))
					)
			);

			$fileContent .= "			self::$value => '$valueAsShortText',\n";
		}
		$fileContent .= "		};\n";
		$fileContent .= "	}\n";

		$fileContent .= "}";
		$fileContent .= "\n";

		return $fileContent;
	}

	public function getJsFileContent(): string {
		$fileContent = "";

		$fileContent .= "export const $this->name = {\n";

		foreach ($this->values as $index => $value) {
			$fileContent .= "	$value: " . ($index + 1) . ",\n";
		}
		$fileContent .= "\n";
		$fileContent .= "	/**\n";
		$fileContent .= "	 * @return {int[]} {@see " . $this->name . "}\n";
		$fileContent .= "	 */\n";
		$fileContent .= "	getValues: () => [\n";
		foreach ($this->values as $value) {
			$fileContent .= "		" . $this->name . ".$value,\n";
		}
		$fileContent .= "	],\n";

		$fileContent .= "\n}";
		$fileContent .= "\n";

		return $fileContent;
	}

	public function getPhpTestFileContent(string $testNamespacePart): string {
		$fileContent = "<?php\n\n";
		$fileContent .= "namespace $this->basePackage\\"
				. $testNamespacePart . "\\"
				. $this->namespace . ";\n";
		$fileContent .= "\n";
		$fileContent .= "use PHPUnit\Framework\TestCase;\n";
		$fileContent .= "use $this->basePackage\\"
				. $this->namespace . "\\$this->name;\n";
		$fileContent .= "\n";
		$fileContent .= "/**\n";
		$fileContent .= " * This code is generated. Do not edit it\n";
		$fileContent .= " */\n";
		$fileContent .= "class " . $this->name . "Test extends TestCase {\n\n";

		foreach ($this->values as $value) {
			$valueAsShortText = implode(
					'',
					array_map(
							static fn (string $word) => ucwords($word),
							explode('_', strtolower($value))
					)
			);

			$fileContent .= "	public function test" . $valueAsShortText . "(): void {\n";
			$fileContent .= "		\$this->assertNotNull($this->name::$value);\n";
			$fileContent .= "	}\n\n";
		}

		$fileContent .= "}\n";

		return $fileContent;
	}
}
