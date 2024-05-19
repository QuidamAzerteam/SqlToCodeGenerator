<?php

namespace SqlToCodeGenerator\codeGeneration\metadata;

use DateTime;
use LogicException;
use PHPUnit\Framework\TestCase;
use SqlToCodeGenerator\codeGeneration\attribute\ClassField;
use SqlToCodeGenerator\codeGeneration\attribute\ClassFieldEnum;
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

	public string $sqlDatabase;
	public string $sqlTable;
	public string $basePackage;
	public string $beanNamespace;
	public string $daoNamespace;
	/** @var BeanProperty[] */
	public array $properties = [];
	/** @var ForeignBeanField[] */
	public array $foreignBeanFields = [];
	/** @var string[][] */
	public array $colNamesByUniqueConstraintName = [];

	public function getUniqueIdentifier(): string {
		return implode('_', [$this->sqlDatabase, $this->sqlTable]);
	}

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
				docLines: ['Bean of `' . $this->sqlDatabase . '.' . $this->sqlTable . '`'],
		);

		foreach ($this->properties as $property) {
			$classBuilder->addFieldBuilders($property->getFieldBuilder());
			if ($property->columnKey?->toClassFieldEnum() !== null) {
				$classBuilder->addImports(
						ClassField::class,
						ClassFieldEnum::class,
				);
			} else if ($property->propertyType === BeanPropertyType::DATE) {
				$classBuilder->addImports(DateTime::class);
			}
		}

		if ($this->foreignBeanFields) {
			foreach ($this->foreignBeanFields as $foreignBeanField) {
				$classBuilder->addFieldBuilders($foreignBeanField->getAsFieldBuilderForPhp());
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
				imports: [
					LogicException::class,
					SqlDao::class,
					"$this->basePackage\\$this->beanNamespace\\{$this->getClassName()}",
				],
				docLines: ['`' . $this->sqlDatabase . '.' . $this->sqlTable . '` DAO of {@see '
						. $this->getClassName() . '} bean.'],
		);

		$primaryField = null;
		$propertiesBySqlName = [];
		$getSqlColFromFieldMatchContents = [];
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
				lines: [
					Line::create("return '$this->sqlTable';"),
				],
		);
		$classBuilder->addPhpFunctionBuilders($getTableFunctionBuilder);

		$getClassFunctionBuilder = FunctionBuilder::create(
				name: 'getClass',
				returnType: 'string',
				visibility: Visibility::PROTECTED,
				lines: [
					Line::create("return {$this->getClassName()}::class;"),
				],
		);
		$classBuilder->addPhpFunctionBuilders($getClassFunctionBuilder);

		$sqlGetterFunctionBuilder = FunctionBuilder::create(
				name: 'get',
				returnType: 'array',
				lines: [
					Line::create("return parent::get(\$where, \$groupBy, \$orderBy, \$limit);"),
				],
		);
		$classBuilder->addPhpFunctionBuilders($sqlGetterFunctionBuilder);

		$stringParams = ['where', 'groupBy', 'orderBy', 'limit'];
		$documentationLines = array_map(
				static fn(string $param) => "@param string \$$param",
				$stringParams,
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
				documentationLines: [
					"@param {$this->getClassName()}[] \$elements",
					"@noinspection SenselessProxyMethodInspection because method here for type hinting",
				],
				lines: [
					Line::create("parent::saveElements(\$elements);"),
				],
				parameterBuilders: [FunctionParameterBuilder::create(
						name: 'elements',
						type: 'array',
				)],
		);
		$classBuilder->addPhpFunctionBuilders($saveElementsFunctionBuilder);

		$updateFunctionBuilder = FunctionBuilder::create(
				name: 'update',
				returnType: 'void',
				documentationLines: [
					"@param {$this->getClassName()} \$item",
				],
				lines: [
					Line::create("parent::updateItem(\$item);"),
				],
				parameterBuilders: [FunctionParameterBuilder::create(
						name: 'item',
						type: $this->getClassName(),
				)],
		);
		$classBuilder->addPhpFunctionBuilders($updateFunctionBuilder);

		$updateFunctionBuilder = FunctionBuilder::create(
				name: 'insert',
				returnType: 'void',
				documentationLines: [
					"@param {$this->getClassName()} \$item",
				],
				lines: [
					Line::create("parent::insertItem(\$item);"),
				],
				parameterBuilders: [FunctionParameterBuilder::create(
						name: 'item',
						type: $this->getClassName(),
				)],
		);
		$classBuilder->addPhpFunctionBuilders($updateFunctionBuilder);

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
			$getSqlColFromFieldFunctionBuilder->addLines(Line::create(
					"$getSqlColFromFieldMatchContent,", $index === 0 ? 1 : 0,
			));
		}
		$getSqlColFromFieldFunctionBuilder->addLines(Line::create(
				"default => throw new LogicException('Unexpected field: ' . \$field),",
		));
		$getSqlColFromFieldFunctionBuilder->addLines(Line::create("};", -1));

		if ($primaryField) {
			$primaryFieldFunctionName = ucfirst(VariableUtils::getPluralOfVarName($primaryField->getName()));

			$primaryFieldDeleteFunctionBuilder = FunctionBuilder::create(
					name: "deleteThrough$primaryFieldFunctionName",
					returnType: 'int',
					documentationLines: [
						"@param {$this->getClassName()}[] \$elements",
					],
			);
			$classBuilder->addPhpFunctionBuilders($primaryFieldDeleteFunctionBuilder);

			$primaryFieldDeleteFunctionBuilder->addParameterBuilders(FunctionParameterBuilder::create(
					name: 'elements',
					type: 'array',
			));

			$primaryFieldDeleteFunctionBuilder->addLines(
					Line::create("\$uniqueKeys = [];"),
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
					documentationLines: [
						"@return {$this->getClassName()}[]",
					],
			);
			$classBuilder->addPhpFunctionBuilders($primaryFieldGetFunctionBuilder);

			$primaryFieldGetFunctionBuilder->addParameterBuilders(FunctionParameterBuilder::create(
					'array',
					$primaryFieldVarName,
			));
			$primaryFieldGetFunctionBuilder->addLines(Line::create(
					"return \$this->get('$primaryField->sqlName IN (\"' . implode('\", \"', \$$primaryFieldVarName) . '\")');",
			));

			foreach ($this->colNamesByUniqueConstraintName as $colNames) {
				$endOfMethodNames = [];
				$uniqueFieldsParams = [];
				foreach ($colNames as $colName) {
					if (!array_key_exists($colName, $propertiesBySqlName)) {
						throw new LogicException("Missing \"$colName\" col in \$propertiesBySqlName for class {$this->getClassName()}");
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
						documentationLines: [
							"@see SqlDao::restoreIds",
							"@param{$this->getClassName()}[] \$elements",
						],
				);
				$classBuilder->addPhpFunctionBuilders($multipleGetFunctionBuilder);

				$multipleGetFunctionBuilder->addParameterBuilders(FunctionParameterBuilder::create(
						type: 'array',
						name: 'elements',
				));
				$multipleGetFunctionBuilder->addLines(Line::create(
						"\$this->restoreIds([$uniqueFieldsParam], \$elements);",
				));
			}
		}

		foreach ($this->foreignBeanFields as $foreignBeanField) {
			if ($foreignBeanField->isArray) {
				$classNameInMethod = VariableUtils::getPluralOfVarName($foreignBeanField->toBean->getClassName());
			} else {
				// Array are reverse beans, so keep this logic in the else here
				$classNameInMethod = ucfirst(SqlDao::sqlToCamelCase($foreignBeanField->withProperty->getSqlNameWithoutId()));
			}
			$foreignBeanOnPropertyName = $foreignBeanField->onProperty->getName();
			$foreignBeanOnPropertySqlName = $foreignBeanField->onProperty->sqlName;
			$foreignBeanWithPropertyName = $foreignBeanField->withProperty->getName();

			$completeFunctionBuilder = FunctionBuilder::create(
					name: "completeWith$classNameInMethod",
					returnType: 'void',
					documentationLines: [
						"@param {$this->getClassName()}[] \$elements",
					],
			);
			$classBuilder->addPhpFunctionBuilders($completeFunctionBuilder);

			$arrayVarName = 'elements';
			$arrayParameterName = 'element';
			$completeFunctionBuilder->addParameterBuilders(FunctionParameterBuilder::create(
					type: 'array',
					name: $arrayVarName,
			));

			$completeFunctionBuilder->addLines(
					Line::create("if (!\$$arrayVarName) {"),
					Line::create("return;", 1),
					Line::create("}", -1),
					Line::create("foreach (\$$arrayVarName as \$$arrayParameterName) {"),
					Line::create("\$fkIds[\$element->$foreignBeanWithPropertyName] = \$element->$foreignBeanWithPropertyName;", 1),
					Line::create("}", -1),
					Line::create("\$fkDao = new {$foreignBeanField->toBean->getDaoName()}();"),
					Line::create("\$fkElements = \$fkDao->get('$foreignBeanOnPropertySqlName "
							. "IN (\"' . implode('\", \"', \$fkIds) . '\")');"),
					Line::create("\$fkElementsByFkProperty = [];"),
					Line::create("foreach (\$fkElements as \$fkElement) {"),
					Line::create("if (!array_key_exists(\$fkElement->$foreignBeanOnPropertyName, \$fkElementsByFkProperty)) {", 1),
					Line::create("\$fkElementsByFkProperty[\$fkElement->$foreignBeanOnPropertyName] = [];", 1),
					Line::create("}", -1),
					Line::create("\$fkElementsByFkProperty[\$fkElement->$foreignBeanOnPropertyName][] = \$fkElement;"),
					Line::create("}", -1),
					Line::create("foreach (\$$arrayVarName as \$$arrayParameterName) {"),
			);
			if ($foreignBeanField->isArray) {
				$completeFunctionBuilder->addLines(
						Line::create(
								"\$$arrayParameterName->" . lcfirst($classNameInMethod)
										. " = \$fkElementsByFkProperty[\$element->$foreignBeanWithPropertyName] ?? [];",
								1,
						),
				);
			} else {
				$completeFunctionBuilder->addLines(
						Line::create("\$$arrayParameterName->" . lcfirst($classNameInMethod) . " ="
								. " \$fkElementsByFkProperty[\$$arrayParameterName->$foreignBeanWithPropertyName][0] ?? null;", 1),
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
				docLines: ['Bean of `' . $this->sqlDatabase . '.' . $this->sqlTable . '`'],
		);

		foreach ($this->properties as $property) {
			$classBuilder->addFieldBuilders($property->getFieldBuilder());
		}
		foreach ($this->foreignBeanFields as $foreignBeanField) {
			$classBuilder->addFieldBuilders($foreignBeanField->getAsFieldBuilderForJs());
		}

		$jsFunctionBuilder = FunctionBuilder::create(
				name: 'getInstanceFromObject',
				returnType: $this->getClassName(),
				isStatic: true,
				parameterBuilders: [
					FunctionParameterBuilder::create(
							type: '',
							name: 'rawObject',
							defaultValue: '',
					),
				],
				lines: [
					Line::create("return Object.assign(new {$this->getClassName()}(), rawObject);"),
				],
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
				imports: [
					TestCase::class,
					"$this->basePackage\\$this->beanNamespace\\{$this->getClassName()}",
				],
		);

		$phpFunctionBuilder = FunctionBuilder::create(
				name: 'testConstructor',
				returnType: 'void',
				isFinal: true,
				lines: [
					Line::create("\$this->assertNotNull({$this->getClassName()}::class);"),
				],
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
				imports: [
					TestCase::class,
					PdoContainer::class,
					"$this->basePackage\\$this->daoNamespace\\{$this->getDaoName()}",
				],
		);

		$phpFunctionBuilder = FunctionBuilder::create(
				name: 'testConstructor',
				returnType: 'void',
				isFinal: true,
				lines: [
					Line::create("\$this->assertNotNull(new {$this->getDaoName()}(\$this->createMock(PdoContainer::class)));"),
				],
		);
		$classBuilder->addPhpFunctionBuilders($phpFunctionBuilder);

		return $classBuilder->getPhpFileContent();
	}

}
