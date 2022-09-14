<?php

namespace SqlToCodeGenerator\generation\metadata;

use SqlToCodeGenerator\generation\builder\PhpClassBuilder;
use SqlToCodeGenerator\generation\builder\PhpFieldBuilder;
use SqlToCodeGenerator\generation\builder\PhpFunctionBuilder;
use SqlToCodeGenerator\generation\builder\PhpFunctionLineBuilder;
use SqlToCodeGenerator\generation\builder\PhpFunctionParameterBuilder;
use SqlToCodeGenerator\sql\PdoContainer;
use SqlToCodeGenerator\sql\SqlDao;
use SqlToCodeGenerator\sql\SqlUtils;

class Bean {

	public string $sqlTable;
	public string $basePackage;
	public string $beanNamespace;
	public string $daoNamespace;
	public string $className;
	/** @var BeanProperty[] */
	public array $properties = array();
	/** @var ForeignBean[] */
	public array $foreignBeans = array();
	/** @var string[][] */
	public array $colNamesByUniqueConstraintName = array();

	public function getDaoName(): string {
		return $this->className . 'Dao';
	}

	public function getPhpClassFileContent(string $objectUtilsPrefix): string {
		$classBuilder = new PhpClassBuilder();
		$classBuilder->basePackage = $this->basePackage;
		$classBuilder->className = $this->className;
		$classBuilder->namespace = $this->beanNamespace;
		$classBuilder->imports = array(
			"$objectUtilsPrefix\ObjectUtils",
			"stdClass",
		);

		foreach ($this->properties as $property) {
			$classBuilder->fieldBuilders[] = $property->getPhpFieldBuilder();
		}

		if ($this->foreignBeans) {
			foreach ($this->foreignBeans as $foreignBean) {
				$foreignBeanFieldBuilder = new PhpFieldBuilder();

				$classBuilder->fieldBuilders[] = $foreignBeanFieldBuilder;

				$var = lcfirst($foreignBean->toBean->className);

				if ($foreignBean->isArray) {
					$foreignBeanFieldBuilder->type = 'array';
					$foreignBeanFieldBuilder->defaultValue = 'array()';
					$foreignBeanFieldBuilder->fieldName = SqlUtils::getArrayVarAsString($var);
					$foreignBeanFieldBuilder->customTypeHint = $foreignBean->toBean->className . '[]';
				} else {
					$foreignBeanFieldBuilder->type = $foreignBean->toBean->className;
					$foreignBeanFieldBuilder->fieldName = $var;
				}
			}
		}

		$functionBuilder = new PhpFunctionBuilder();

		$functionBuilder->isStatic = true;
		$functionBuilder->name = 'getFromStdClass';
		$functionBuilder->returnType = $this->className;

		$functionParameter = new PhpFunctionParameterBuilder();
		$functionParameter->name = 'stdClass';
		$functionParameter->type = 'stdClass';
		$functionBuilder->parameterBuilders[] = $functionParameter;

		$functionLine = new PhpFunctionLineBuilder();
		$functionLine->content = "return ObjectUtils::getFromStdClass(\$stdClass, self::class);";
		$functionBuilder->lineBuilders[] = $functionLine;

		$classBuilder->functionBuilders[] = $functionBuilder;

		return $classBuilder->getFileContent();
	}

	public function getPhpDaoFileContent(): string {
		$classBuilder = new PhpClassBuilder();
		$classBuilder->basePackage = $this->basePackage;
		$classBuilder->className = $this->getDaoName();
		$classBuilder->namespace = $this->daoNamespace;
		$classBuilder->imports = array(
				"LogicException",
				SqlDao::class,
				"$this->basePackage\\$this->beanNamespace\\$this->className",
		);
		$classBuilder->extends = 'SqlDao';

		$primaryField = null;
		$propertiesBySqlName = array();
		$getSqlColFromFieldMatchContents = array();
		foreach ($this->properties as $property) {
			$fieldBuilder = new PhpFieldBuilder();
			$classBuilder->fieldBuilders[] = $fieldBuilder;

			$fieldBuilder->fieldName = strtoupper($property->sqlName) . '_SQL';
			$fieldBuilder->isConst = true;
			$fieldBuilder->defaultValue = "'$property->sqlName'";

			if ($property->columnKey === BeanPropertyColKey::PRI) {
				$primaryField = $property;
			}

			$propertiesBySqlName[$property->sqlName] = $property;
			$getSqlColFromFieldMatchContents[] = "'$property->name' => '$property->sqlName'";
		}

		// get table function
		$getTableFunctionBuilder = new PhpFunctionBuilder();
		$classBuilder->functionBuilders[] = $getTableFunctionBuilder;

		$getTableFunctionBuilder->visibility = 'protected';
		$getTableFunctionBuilder->name = 'getTable';
		$getTableFunctionBuilder->returnType = 'string';

		$getTableLineBuilder = new PhpFunctionLineBuilder();
		$getTableFunctionBuilder->lineBuilders[] = $getTableLineBuilder;

		$getTableLineBuilder->content = "return '" . $this->sqlTable . "';";

		// get class function
		$getClassFunctionBuilder = new PhpFunctionBuilder();
		$classBuilder->functionBuilders[] = $getClassFunctionBuilder;

		$getClassFunctionBuilder->visibility = 'protected';
		$getClassFunctionBuilder->name = 'getClass';
		$getClassFunctionBuilder->returnType = 'string';

		$getClassLineBuilder = new PhpFunctionLineBuilder();
		$getClassFunctionBuilder->lineBuilders[] = $getClassLineBuilder;

		$getClassLineBuilder->content = "return " . $this->className . "::class;";

		// sql getter function
		$sqlGetterFunctionBuilder = new PhpFunctionBuilder();
		$classBuilder->functionBuilders[] = $sqlGetterFunctionBuilder;

		$sqlGetterFunctionBuilder->visibility = 'public';
		$sqlGetterFunctionBuilder->name = 'get';
		$sqlGetterFunctionBuilder->returnType = 'array';

		$stringParams = array('where', 'groupBy', 'orderBy', 'limit');
		$documentationLines = array_map(
				static fn (string $param) => "@param string \$$param",
				$stringParams
		);
		$documentationLines[] = "@return " . $this->className . "[]";
		$documentationLines[] = "@noinspection SenselessProxyMethodInspection because method here for type hinting";
		$sqlGetterFunctionBuilder->documentationLines = $documentationLines;

		foreach ($stringParams as $stringParam) {
			$sqlGetterParamBuilder = new PhpFunctionParameterBuilder();
			$sqlGetterFunctionBuilder->parameterBuilders[] = $sqlGetterParamBuilder;

			$sqlGetterParamBuilder->name = $stringParam;
			$sqlGetterParamBuilder->type = 'string';
			$sqlGetterParamBuilder->defaultValue = "''";
		}

		$sqlGetterLineBuilder = new PhpFunctionLineBuilder();
		$sqlGetterFunctionBuilder->lineBuilders[] = $sqlGetterLineBuilder;

		$sqlGetterLineBuilder->content = "return parent::get(\$where, \$groupBy, \$orderBy, \$limit);";

		// save elements function
		$saveElementsFunctionBuilder = new PhpFunctionBuilder();
		$classBuilder->functionBuilders[] = $saveElementsFunctionBuilder;

		$saveElementsFunctionBuilder->visibility = 'public';
		$saveElementsFunctionBuilder->name = 'saveElements';
		$saveElementsFunctionBuilder->returnType = 'void';
		$saveElementsFunctionBuilder->documentationLines = array(
				"@param " . $this->className . "[] \$elements",
				"@noinspection SenselessProxyMethodInspection because method here for type hinting",
		);

		$saveElementsParamBuilder = new PhpFunctionParameterBuilder();
		$saveElementsFunctionBuilder->parameterBuilders[] = $saveElementsParamBuilder;

		$saveElementsParamBuilder->name = 'elements';
		$saveElementsParamBuilder->type = 'array';

		$saveElementsLineBuilder = new PhpFunctionLineBuilder();
		$saveElementsFunctionBuilder->lineBuilders[] = $saveElementsLineBuilder;

		$saveElementsLineBuilder->content = "parent::saveElements(\$elements);";

		// getSqlColFromField function
		$getSqlColFromFieldFunctionBuilder = new PhpFunctionBuilder();
		$classBuilder->functionBuilders[] = $getSqlColFromFieldFunctionBuilder;

		$getSqlColFromFieldFunctionBuilder->visibility = 'protected';
		$getSqlColFromFieldFunctionBuilder->name = 'getSqlColFromField';
		$getSqlColFromFieldFunctionBuilder->returnType = 'string';

		$getSqlColFromFieldParamBuilder = new PhpFunctionParameterBuilder();
		$getSqlColFromFieldFunctionBuilder->parameterBuilders[] = $getSqlColFromFieldParamBuilder;

		$getSqlColFromFieldParamBuilder->name = 'field';
		$getSqlColFromFieldParamBuilder->type = 'string';

		$getSqlColFromFieldFunctionBuilder->lineBuilders[] = PhpFunctionLineBuilder::create("return match (\$field) {");
		foreach ($getSqlColFromFieldMatchContents as $index => $getSqlColFromFieldMatchContent) {
			$getSqlColFromFieldFunctionBuilder->lineBuilders[] = PhpFunctionLineBuilder::create("$getSqlColFromFieldMatchContent,", $index === 0 ? 1 : 0);
		}
		$getSqlColFromFieldFunctionBuilder->lineBuilders[] = PhpFunctionLineBuilder::create("default => throw new LogicException('Unexpected field: ' . \$field),");
		$getSqlColFromFieldFunctionBuilder->lineBuilders[] = PhpFunctionLineBuilder::create("};", -1);

		if ($primaryField) {
			$primaryFieldFunctionName = ucfirst($primaryField->name);

			$primaryFieldDeleteFunctionBuilder = new PhpFunctionBuilder();
			$classBuilder->functionBuilders[] = $primaryFieldDeleteFunctionBuilder;

			$primaryFieldDeleteFunctionBuilder->name = "deleteThrough$primaryFieldFunctionName";
			$primaryFieldDeleteFunctionBuilder->returnType = 'int';
			$primaryFieldDeleteFunctionBuilder->documentationLines = array(
				"@param $this->className[] \$elements",
			);

			$primaryFieldDeleteFunctionParam = new PhpFunctionParameterBuilder();
			$primaryFieldDeleteFunctionBuilder->parameterBuilders[] = $primaryFieldDeleteFunctionParam;

			$primaryFieldDeleteFunctionParam->name = 'elements';
			$primaryFieldDeleteFunctionParam->type = 'array';

			array_push(
					$primaryFieldDeleteFunctionBuilder->lineBuilders,
					PhpFunctionLineBuilder::create("\$uniqueKeys = array();"),
					PhpFunctionLineBuilder::create("foreach (\$elements as \$element) {"),
					PhpFunctionLineBuilder::create("\$uniqueKeys[\$element->$primaryField->name] = \$element->$primaryField->name;", 1),
					PhpFunctionLineBuilder::create("}", -1),
					PhpFunctionLineBuilder::create("\$whereIn = \"'\" . implode(\"', '\", \$uniqueKeys) . \"'\";",),
					PhpFunctionLineBuilder::create("return \$this->deleteData(\"id IN (\$whereIn)\");",),
			);

			$primaryFieldVarName = SqlUtils::getArrayVarAsString($primaryField->name);
			$primaryFieldGetFunctionBuilder = new PhpFunctionBuilder();
			$classBuilder->functionBuilders[] = $primaryFieldGetFunctionBuilder;

			$primaryFieldGetFunctionBuilder->name = "getFrom$primaryFieldFunctionName";
			$primaryFieldGetFunctionBuilder->returnType = "array";
			$primaryFieldGetFunctionBuilder->documentationLines = array(
				"@return $this->className[]",
			);
			$primaryFieldGetFunctionBuilder->parameterBuilders[] = PhpFunctionParameterBuilder::create(
					'array',
					$primaryFieldVarName
			);
			$primaryFieldGetFunctionBuilder->lineBuilders[] = PhpFunctionLineBuilder::create(
					"return \$this->get('$primaryField->sqlName IN (\"' . implode('\", \"', \$$primaryFieldVarName) . '\")');"
			);

			foreach ($this->colNamesByUniqueConstraintName as $colNames) {
				$endOfMethodNames = array();
				$uniqueFieldsParams = array();
				foreach ($colNames as $colName) {
					if (!array_key_exists($colName, $propertiesBySqlName)) {
						throw new LogicException('Missing ' . $colName . ' in $mulFieldsBySqlName '
								. 'for class ' . $this->className);
					}
					$property = $propertiesBySqlName[$colName];

					$endOfMethodNames[] = ucfirst($property->name);
					$uniqueFieldsParams[] = "'$property->name'";
				}
				$endOfMethodName = implode('And', $endOfMethodNames);
				$uniqueFieldsParam = implode(',', $uniqueFieldsParams);

				$multipleGetFunctionBuilder = new PhpFunctionBuilder();
				$classBuilder->functionBuilders[] = $multipleGetFunctionBuilder;

				$multipleGetFunctionBuilder->name = "restoreIdsThrough$endOfMethodName";
				$multipleGetFunctionBuilder->returnType = "void";
				$multipleGetFunctionBuilder->documentationLines = array(
						"@param $this->className[] \$elements",
				);
				$multipleGetFunctionBuilder->parameterBuilders[] = PhpFunctionParameterBuilder::create(
						'array',
						'elements'
				);
				$multipleGetFunctionBuilder->lineBuilders[] = PhpFunctionLineBuilder::create(
						"\$this->restoreIds([$uniqueFieldsParam], \$elements);"
				);
			}
		}

		foreach ($this->foreignBeans as $foreignBean) {
			$classNameInMethod = $foreignBean->isArray
					? SqlUtils::getArrayVarAsString($foreignBean->toBean->className)
					: $foreignBean->toBean->className;
			$foreignBeanOnPropertyName = $foreignBean->onProperty->name;
			$foreignBeanOnPropertySqlName = $foreignBean->onProperty->sqlName;
			$foreignBeanWithPropertyName = $foreignBean->withProperty->name;

			$completeFunctionBuilder = new PhpFunctionBuilder();
			$classBuilder->functionBuilders[] = $completeFunctionBuilder;

			$completeFunctionBuilder->name = "completeWith$classNameInMethod";
			$completeFunctionBuilder->returnType = "void";
			$completeFunctionBuilder->documentationLines = array(
					"@param $this->className[] \$elements",
			);
			$completeFunctionBuilder->parameterBuilders[] = PhpFunctionParameterBuilder::create(
					'array',
					'elements'
			);

			$field = $foreignBean->isArray
					? SqlUtils::getArrayVarAsString($foreignBean->toBean->className)
					: $foreignBean->toBean->className;

			array_push(
					$completeFunctionBuilder->lineBuilders,
					PhpFunctionLineBuilder::create("\$fkIds = array();"),
					PhpFunctionLineBuilder::create("foreach (\$elements as \$element) {"),
					PhpFunctionLineBuilder::create("\$fkIds[\$element->$foreignBeanWithPropertyName] = \$element->$foreignBeanWithPropertyName;", 1),
					PhpFunctionLineBuilder::create("}", -1),
					PhpFunctionLineBuilder::create("\$fkDao = new " . $foreignBean->toBean->getDaoName() . "();"),
					PhpFunctionLineBuilder::create("\$fkElements = \$fkDao->get('$foreignBeanOnPropertySqlName IN (\"' . implode('\", \"', \$fkIds) . '\")');"),
					PhpFunctionLineBuilder::create("\$fkElementsByFkProperty = array();"),
					PhpFunctionLineBuilder::create("foreach (\$fkElements as \$fkElement) {"),
					PhpFunctionLineBuilder::create("if (!array_key_exists(\$fkElement->$foreignBeanOnPropertyName, \$fkElementsByFkProperty)) {", 1),
					PhpFunctionLineBuilder::create("\$fkElementsByFkProperty[\$fkElement->$foreignBeanOnPropertyName] = array();", 1),
					PhpFunctionLineBuilder::create("}", -1),
					PhpFunctionLineBuilder::create("\$fkElementsByFkProperty[\$fkElement->$foreignBeanOnPropertyName][] = \$fkElement;"),
					PhpFunctionLineBuilder::create("}", -1),
					PhpFunctionLineBuilder::create("foreach (\$elements as \$element) {"),
			);
			if ($foreignBean->isArray) {
				array_push(
						$completeFunctionBuilder->lineBuilders,
						PhpFunctionLineBuilder::create(
								"\$element->" . lcfirst($field) . " = \$fkElementsByFkProperty[\$element->$foreignBeanWithPropertyName] ?? array();",
								1
						),
						PhpFunctionLineBuilder::create("foreach (\$element->" . lcfirst($field) . " as \$fkElement) {"),
						PhpFunctionLineBuilder::create("\$fkElement->" . lcfirst($this->className) . " = \$element;", 1),
						PhpFunctionLineBuilder::create("}", -1),
				);
			} else {
				array_push(
						$completeFunctionBuilder->lineBuilders,
						PhpFunctionLineBuilder::create("\$element->" . lcfirst($field) . " ="
								. " \$fkElementsByFkProperty[\$element->$foreignBeanWithPropertyName][0] ?? null;"),
						PhpFunctionLineBuilder::create("\$element->" . lcfirst($field) . "->" . lcfirst($this->className) . " = \$element;"),
				);
			}
			$completeFunctionBuilder->lineBuilders[] = PhpFunctionLineBuilder::create("}", -1);
		}

		return $classBuilder->getFileContent();
	}

	public function getJsClassFileContent(): string {
		$fileContent = "export class $this->className {\n";

		foreach ($this->properties as $property) {
			$fileContent .= "	" . $property->getJsDeclaringField() . ";\n";
		}
		if ($this->foreignBeans) {
			$fileContent .= "\n";
			foreach ($this->foreignBeans as $foreignBean) {
				$var = lcfirst($foreignBean->toBean->className);

				if ($foreignBean->isArray) {
					$fileContent .= "	/** @type {" . $foreignBean->toBean->className . "[]} */\n";
					$fileContent .= "	" . SqlUtils::getArrayVarAsString($var) . ";\n";
				} else {
					$fileContent .= "	/** @type {" . $foreignBean->toBean->className . "} */\n";
					$fileContent .= "	" . $var . ";\n";
				}
			}
		}

		$fileContent .= "\n";
		$fileContent .= "	/**\n";
		$fileContent .= "	 * @param {Object} rawObject\n";
		$fileContent .= "	 * @return {" . $this->className . "}\n";
		$fileContent .= "	 */\n";
		$fileContent .= "	static getInstanceFromObject(rawObject) {\n";
		$fileContent .= "		return Object.assign(new " . $this->className . "(), rawObject);\n";
		$fileContent .= "	}\n";

		$fileContent .= "\n}";
		$fileContent .= "\n";

		return $fileContent;
	}

	public function getPhpTestFileContent(string $testNamespacePart): string {
		$fileContent = "<?php\n\n";
		$fileContent .= "namespace $this->basePackage\\"
				. $testNamespacePart . "\\"
				. $this->beanNamespace . ";\n";
		$fileContent .= "\n";
		$fileContent .= "use PHPUnit\Framework\TestCase;\n";
		$fileContent .= "use $this->basePackage\\"
				. $this->beanNamespace
				. "\\" . $this->className . ";\n";
		$fileContent .= "\n";
		$fileContent .= "/**\n";
		$fileContent .= " * This code is generated. Do not edit it\n";
		$fileContent .= " */\n";
		$fileContent .= "class " . $this->className . "Test extends TestCase {\n\n";
		$fileContent .= "	public function testConstructor(): void {\n";
		$fileContent .= "		\$this->assertNotNull($this->className::class);\n";
		$fileContent .= "	}\n";
		$fileContent .= "}\n";

		return $fileContent;
	}

	public function getPhpDaoTestFileContent(string $testNamespacePart): string {
		$fileContent = "<?php\n\n";
		$fileContent .= "namespace $this->basePackage\\$testNamespacePart\\$this->daoNamespace;\n";
		$fileContent .= "\n";
		$fileContent .= "use PHPUnit\Framework\TestCase;\n";
		$fileContent .= "use " . PdoContainer::class . ";\n";
		$fileContent .= "use $this->basePackage\\$this->daoNamespace\\"
				. $this->getDaoName() . ";\n";
		$fileContent .= "\n";
		$fileContent .= "/**\n";
		$fileContent .= " * This code is generated. Do not edit it\n";
		$fileContent .= " */\n";
		$fileContent .= "class " . $this->getDaoName() . "Test extends TestCase {\n\n";
		$fileContent .= "	public function testConstructor(): void {\n";
		$fileContent .= "		\$this->assertNotNull(new " . $this->getDaoName() . "(\$this->createMock(PdoContainer::class)));\n";
		$fileContent .= "	}\n";
		$fileContent .= "}\n";

		return $fileContent;
	}

}
