<?php

namespace SqlToCodeGenerator\codeGeneration\metadata;

use LogicException;
use PHPUnit\Framework\TestCase;
use SqlToCodeGenerator\codeGeneration\bean\Line;
use SqlToCodeGenerator\codeGeneration\builder\ClassBuilder;
use SqlToCodeGenerator\codeGeneration\builder\FieldBuilder;
use SqlToCodeGenerator\codeGeneration\builder\FunctionBuilder;
use SqlToCodeGenerator\codeGeneration\builder\FunctionParameterBuilder;
use SqlToCodeGenerator\codeGeneration\enums\Visibility;
use SqlToCodeGenerator\codeGeneration\utils\VariableUtils;
use SqlToCodeGenerator\sql\PdoContainer;
use SqlToCodeGenerator\sql\SqlDao;

class Bean {

	public string $sqlTable;
	public string $basePackage;
	public string $beanNamespace;
	public string $daoNamespace;
	/** @var BeanProperty[] */
	public array $properties = array();
	/** @var ForeignBean[] */
	public array $foreignBeans = array();
	/** @var string[][] */
	public array $colNamesByUniqueConstraintName = array();

	public function getDaoName(): string {
		return $this->getClassName() . 'Dao';
	}

	public function getClassName(): string {
		return SqlDao::sqlToCamelCase($this->sqlTable);
	}

	public function getPhpClassFileContent(): string {
		$classBuilder = ClassBuilder::create(
				basePackage: $this->basePackage,
				namespace: $this->beanNamespace,
				name: $this->getClassName(),
		);

		foreach ($this->properties as $property) {
			$classBuilder->addFieldBuilders($property->getFieldBuilder());
		}

		if ($this->foreignBeans) {
			foreach ($this->foreignBeans as $foreignBean) {
				$var = lcfirst($foreignBean->toBean->getClassName());

				if ($foreignBean->isArray) {
					$foreignBeanFieldBuilder = FieldBuilder::create(VariableUtils::getPluralOfVarName($var))
							->setPhpType('array')
							->setDefaultValue('array()')
							->setCustomTypeHint($foreignBean->toBean->getClassName() . '[]');
				} else {
					$foreignBeanFieldBuilder = FieldBuilder::create($var)
							->setPhpType($foreignBean->toBean->getClassName());
				}
				$classBuilder->addFieldBuilders($foreignBeanFieldBuilder);
			}
		}

		return $classBuilder->getPhpFileContent();
	}

	public function getPhpDaoFileContent(): string {
		$classBuilder = ClassBuilder::create(
				basePackage: $this->basePackage,
				namespace: $this->daoNamespace,
				name: $this->getDaoName(),
				extends: 'SqlDao',
				imports: array(
					LogicException::class,
					SqlDao::class,
					"$this->basePackage\\$this->beanNamespace\\{$this->getClassName()}",
				),
		);

		$primaryField = null;
		$propertiesBySqlName = array();
		$getSqlColFromFieldMatchContents = array();
		foreach ($this->properties as $property) {
			$fieldBuilder = FieldBuilder::create(strtoupper($property->sqlName) . '_SQL')
					->setIsConst(true)
					->setDefaultValue("'$property->sqlName'");
			$classBuilder->addFieldBuilders($fieldBuilder);

			if ($property->columnKey === BeanPropertyColKey::PRI) {
				$primaryField = $property;
			}

			$propertiesBySqlName[$property->sqlName] = $property;
			$getSqlColFromFieldMatchContents[] = "'{$property->getName()}' => '$property->sqlName'";
		}

		$getTableFunctionBuilder = FunctionBuilder::create(
				name: 'getTable',
				returnType: 'string',
				visibility: Visibility::PROTECTED,
				lines: array(
					Line::create("return '$this->sqlTable';"),
				),
		);
		$classBuilder->addPhpFunctionBuilders($getTableFunctionBuilder);

		$getClassFunctionBuilder = FunctionBuilder::create(
				name: 'getClass',
				returnType: 'string',
				visibility: Visibility::PROTECTED,
				lines: array(
					Line::create("return {$this->getClassName()}::class;"),
				),
		);
		$classBuilder->addPhpFunctionBuilders($getClassFunctionBuilder);

		$sqlGetterFunctionBuilder = FunctionBuilder::create(
				name: 'get',
				returnType: 'array',
				lines: array(
					Line::create("return parent::get(\$where, \$groupBy, \$orderBy, \$limit);"),
				),
		);
		$classBuilder->addPhpFunctionBuilders($sqlGetterFunctionBuilder);

		$stringParams = array('where', 'groupBy', 'orderBy', 'limit');
		$documentationLines = array_map(
				static fn (string $param) => "@param string \$$param",
				$stringParams
		);
		$documentationLines[] = "@return {$this->getClassName()}[]";
		$documentationLines[] = "@noinspection SenselessProxyMethodInspection because method here for type hinting";
		$sqlGetterFunctionBuilder->addDocumentationLines(...$documentationLines);

		foreach ($stringParams as $stringParam) {
			$sqlGetterFunctionBuilder->addParameterBuilders(FunctionParameterBuilder::create(
					name: $stringParam,
					type: 'string',
					defaultValue: "''",
			));
		}

		$saveElementsFunctionBuilder = FunctionBuilder::create(
				name: 'saveElements',
				returnType: 'void',
				documentationLines: array(
					"@param {$this->getClassName()}[] \$elements",
					"@noinspection SenselessProxyMethodInspection because method here for type hinting",
				),
				lines: array(
					Line::create("parent::saveElements(\$elements);"),
				),
		);
		$classBuilder->addPhpFunctionBuilders($saveElementsFunctionBuilder);

		$saveElementsFunctionBuilder->addParameterBuilders(FunctionParameterBuilder::create(
				name: 'elements',
				type: 'array',
		));

		$getSqlColFromFieldFunctionBuilder = FunctionBuilder::create(
				name: 'getSqlColFromField',
				returnType: 'string',
				visibility: Visibility::PROTECTED,
		);
		$classBuilder->addPhpFunctionBuilders($getSqlColFromFieldFunctionBuilder);

		$getSqlColFromFieldFunctionBuilder->addParameterBuilders(FunctionParameterBuilder::create(
				name: 'field',
				type: 'string',
		));

		$getSqlColFromFieldFunctionBuilder->addLines(Line::create("return match (\$field) {"));
		foreach ($getSqlColFromFieldMatchContents as $index => $getSqlColFromFieldMatchContent) {
			$getSqlColFromFieldFunctionBuilder->addLines(Line::create("$getSqlColFromFieldMatchContent,", $index === 0 ? 1 : 0));
		}
		$getSqlColFromFieldFunctionBuilder->addLines(Line::create("default => throw new LogicException('Unexpected field: ' . \$field),"));
		$getSqlColFromFieldFunctionBuilder->addLines(Line::create("};", -1));

		if ($primaryField) {
			$primaryFieldFunctionName = ucfirst($primaryField->getName());

			$primaryFieldDeleteFunctionBuilder = FunctionBuilder::create(
					name: "deleteThrough$primaryFieldFunctionName",
					returnType: 'int',
					documentationLines: array(
						"@param {$this->getClassName()}[] \$elements",
					),
			);
			$classBuilder->addPhpFunctionBuilders($primaryFieldDeleteFunctionBuilder);

			$primaryFieldDeleteFunctionBuilder->addParameterBuilders(FunctionParameterBuilder::create(
					name: 'elements',
					type: 'array',
			));

			$primaryFieldDeleteFunctionBuilder->addLines(
					Line::create("\$uniqueKeys = array();"),
					Line::create("foreach (\$elements as \$element) {"),
					Line::create("\$uniqueKeys[\$element->{$primaryField->getName()}] = \$element->{$primaryField->getName()};", 1),
					Line::create("}", -1),
					Line::create("\$whereIn = \"'\" . implode(\"', '\", \$uniqueKeys) . \"'\";"),
					Line::create("return \$this->deleteData(\"id IN (\$whereIn)\");"),
			);

			$primaryFieldVarName = VariableUtils::getPluralOfVarName($primaryField->getName());
			$primaryFieldGetFunctionBuilder = FunctionBuilder::create(
					name: "getFrom$primaryFieldFunctionName",
					returnType: 'array',
					documentationLines: array(
						"@return {$this->getClassName()}[]",
					),
			);
			$classBuilder->addPhpFunctionBuilders($primaryFieldGetFunctionBuilder);

			$primaryFieldGetFunctionBuilder->addParameterBuilders(FunctionParameterBuilder::create(
					'array',
					$primaryFieldVarName
			));
			$primaryFieldGetFunctionBuilder->addLines(Line::create(
					"return \$this->get('$primaryField->sqlName IN (\"' . implode('\", \"', \$$primaryFieldVarName) . '\")');"
			));

			foreach ($this->colNamesByUniqueConstraintName as $colNames) {
				$endOfMethodNames = array();
				$uniqueFieldsParams = array();
				foreach ($colNames as $colName) {
					if (!array_key_exists($colName, $propertiesBySqlName)) {
						throw new LogicException('Missing ' . $colName . ' in $mulFieldsBySqlName '
								. 'for class ' . $this->getClassName());
					}
					$property = $propertiesBySqlName[$colName];

					$endOfMethodNames[] = ucfirst($property->getName());
					$uniqueFieldsParams[] = "'{$property->getName()}'";
				}
				$endOfMethodName = implode('And', $endOfMethodNames);
				$uniqueFieldsParam = implode(',', $uniqueFieldsParams);

				$multipleGetFunctionBuilder = FunctionBuilder::create(
						name: "restoreIdsThrough$endOfMethodName",
						returnType: 'void',
						documentationLines: array(
							"@see SqlDao::restoreIds",
							"@param{$this->getClassName()}[] \$elements",
						),
				);
				$classBuilder->addPhpFunctionBuilders($multipleGetFunctionBuilder);

				$multipleGetFunctionBuilder->addParameterBuilders(FunctionParameterBuilder::create(
						type: 'array',
						name: 'elements'
				));
				$multipleGetFunctionBuilder->addLines(Line::create(
						"\$this->restoreIds([$uniqueFieldsParam], \$elements);"
				));
			}
		}

		foreach ($this->foreignBeans as $foreignBean) {
			$classNameInMethod = $foreignBean->isArray
					? VariableUtils::getPluralOfVarName($foreignBean->toBean->getClassName())
					: $foreignBean->toBean->getClassName();
			$foreignBeanOnPropertyName = $foreignBean->onProperty->getName();
			$foreignBeanOnPropertySqlName = $foreignBean->onProperty->sqlName;
			$foreignBeanWithPropertyName = $foreignBean->withProperty->getName();

			$completeFunctionBuilder = FunctionBuilder::create(
					name: "completeWith$classNameInMethod",
					returnType: 'void',
					documentationLines: array(
						"@param {$this->getClassName()}[] \$elements",
					),
			);
			$classBuilder->addPhpFunctionBuilders($completeFunctionBuilder);

			$completeFunctionBuilder->addParameterBuilders(FunctionParameterBuilder::create(
					type: 'array',
					name: 'elements'
			));

			$field = $foreignBean->isArray
					? VariableUtils::getPluralOfVarName($foreignBean->toBean->getClassName())
					: $foreignBean->toBean->getClassName();

			$completeFunctionBuilder->addLines(
					Line::create("\$fkIds = array();"),
					Line::create("foreach (\$elements as \$element) {"),
					Line::create("\$fkIds[\$element->$foreignBeanWithPropertyName] = \$element->$foreignBeanWithPropertyName;", 1),
					Line::create("}", -1),
					Line::create("\$fkDao = new {$foreignBean->toBean->getDaoName()}();"),
					Line::create("\$fkElements = \$fkDao->get('$foreignBeanOnPropertySqlName IN (\"' . implode('\", \"', \$fkIds) . '\")');"),
					Line::create("\$fkElementsByFkProperty = array();"),
					Line::create("foreach (\$fkElements as \$fkElement) {"),
					Line::create("if (!array_key_exists(\$fkElement->$foreignBeanOnPropertyName, \$fkElementsByFkProperty)) {", 1),
					Line::create("\$fkElementsByFkProperty[\$fkElement->$foreignBeanOnPropertyName] = array();", 1),
					Line::create("}", -1),
					Line::create("\$fkElementsByFkProperty[\$fkElement->$foreignBeanOnPropertyName][] = \$fkElement;"),
					Line::create("}", -1),
					Line::create("foreach (\$elements as \$element) {"),
			);
			if ($foreignBean->isArray) {
				$completeFunctionBuilder->addLines(
						Line::create(
								"\$element->" . lcfirst($field) . " = \$fkElementsByFkProperty[\$element->$foreignBeanWithPropertyName] ?? array();",
								1
						),
						Line::create("foreach (\$element->" . lcfirst($field) . " as \$fkElement) {"),
						Line::create("\$fkElement->" . lcfirst($this->getClassName()) . " = \$element;", 1),
						Line::create("}", -1),
				);
			} else {
				$completeFunctionBuilder->addLines(
						Line::create("\$element->" . lcfirst($field) . " ="
								. " \$fkElementsByFkProperty[\$element->$foreignBeanWithPropertyName][0] ?? null;"),
						Line::create("\$element->" . lcfirst($field) . "->" . lcfirst($this->getClassName()) . " = \$element;"),
				);
			}
			$completeFunctionBuilder->addLines(Line::create("}", -1));
		}

		return $classBuilder->getPhpFileContent();
	}

	public function getJsClassFileContent(): string {
		$classBuilder = ClassBuilder::create(
				basePackage: $this->basePackage,
				namespace: $this->beanNamespace,
				name: $this->getClassName(),
		);

		foreach ($this->properties as $property) {
			$classBuilder->addFieldBuilders($property->getFieldBuilder());
		}
		foreach ($this->foreignBeans as $foreignBean) {
			$var = lcfirst($foreignBean->toBean->getClassName());

			if ($foreignBean->isArray) {
				$fieldBuilder = FieldBuilder::create(VariableUtils::getPluralOfVarName($var))
						->setJsType($foreignBean->toBean->getClassName() . '[]');
			} else {
				$fieldBuilder = FieldBuilder::create($var)->setJsType($foreignBean->toBean->getClassName());
			}
			$classBuilder->addFieldBuilders($fieldBuilder);
		}

		$jsFunctionBuilder = FunctionBuilder::create(
				name: 'getInstanceFromObject',
				returnType: $this->getClassName(),
				isStatic: true,
				parameterBuilders: array(
					FunctionParameterBuilder::create(
							type: '',
							name: 'rawObject',
							defaultValue: '',
					),
				),
				lines: array(
					Line::create("return Object.assign(new {$this->getClassName()}(), rawObject);"),
				),
		);
		$classBuilder->addJsFunctionBuilders($jsFunctionBuilder);

		return $classBuilder->getJsFileContent();
	}

	public function getPhpTestFileContent(): string {
		$classBuilder = ClassBuilder::create(
				basePackage: $this->basePackage,
				namespace: $this->beanNamespace,
				name: "{$this->getClassName()}Test",
				extends: 'TestCase',
				imports: array(
					TestCase::class,
					"$this->basePackage\\$this->beanNamespace\\{$this->getClassName()}",
				),
		);

		$phpFunctionBuilder = FunctionBuilder::create(
				name: 'testConstructor',
				returnType: 'void',
				isFinal: true,
				lines: array(
					Line::create("\$this->assertNotNull({$this->getClassName()}::class);"),
				),
		);
		$classBuilder->addPhpFunctionBuilders($phpFunctionBuilder);

		return $classBuilder->getPhpFileContent();
	}

	public function getPhpDaoTestFileContent(): string {
		$classBuilder = ClassBuilder::create(
				basePackage: $this->basePackage,
				namespace: $this->beanNamespace,
				name: $this->getDaoName() . 'Test',
				extends: 'TestCase',
				imports: array(
					TestCase::class,
					PdoContainer::class,
					"$this->basePackage\\$this->daoNamespace\\{$this->getDaoName()}",
				),
		);

		$phpFunctionBuilder = FunctionBuilder::create(
				name: 'testConstructor',
				returnType: 'void',
				isFinal: true,
				lines: array(
					Line::create("\$this->assertNotNull(new {$this->getDaoName()}(\$this->createMock(PdoContainer::class)));"),
				),
		);
		$classBuilder->addPhpFunctionBuilders($phpFunctionBuilder);

		return $classBuilder->getPhpFileContent();
	}

}
