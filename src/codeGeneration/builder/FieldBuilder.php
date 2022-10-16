<?php

namespace SqlToCodeGenerator\codeGeneration\builder;

use LogicException;
use SqlToCodeGenerator\codeGeneration\enums\Visibility;
use SqlToCodeGenerator\codeGeneration\utils\CheckUtils;

class FieldBuilder {

	/**
	 * @param string $fieldName
	 * @param Visibility $visibility
	 * @param string $phpType
	 * @param string $jsType
	 * @param string|null $defaultValue
	 * @param bool $isNullable
	 * @param bool $isConst
	 * @param string|null $customTypeHint
	 * @param string[] $comments
	 */
	public function __construct(
			private string $fieldName,
			private Visibility $visibility = Visibility::PUBLIC,
			private string $phpType = '',
			private string $jsType = '',
			private ?string $defaultValue = null,
			private bool $isNullable = false,
			private bool $isConst = false,
			private ?string $customTypeHint = null,
			private array $comments = array()
	) {
		CheckUtils::checkPhpFieldName($fieldName);
		CheckUtils::checkPhpType($phpType);
		// $jsType is only written in documentation, so not need to test
	}

	/**
	 * @see __construct
	 */
	public static function create(
			string $fieldName,
			Visibility $visibility = Visibility::PUBLIC,
			string $phpType = '',
			string $jsType = '',
			?string $defaultValue = null,
			bool $isNullable = false,
			bool $isConst = false,
			?string $customTypeHint = null,
			array $comments = array()
	): static {
		return new static(
				fieldName: $fieldName,
				visibility: $visibility,
				phpType: $phpType,
				jsType: $jsType,
				defaultValue: $defaultValue,
				isNullable: $isNullable,
				isConst: $isConst,
				customTypeHint: $customTypeHint,
				comments: $comments,
		);
	}

	public function getFieldName(): string {
		return $this->fieldName;
	}

	public function getVisibility(): Visibility {
		return $this->visibility;
	}

	public function getPhpType(): string {
		return $this->phpType;
	}

	public function getJsType(): string {
		return $this->jsType;
	}

	public function getDefaultValue(): ?string {
		return $this->defaultValue;
	}

	public function isNullable(): bool {
		return $this->isNullable;
	}

	public function isConst(): bool {
		return $this->isConst;
	}

	public function getCustomTypeHint(): ?string {
		return $this->customTypeHint;
	}

	/**
	 * @return string[]
	 */
	public function getComments(): array {
		return $this->comments;
	}

	public function setVisibility(Visibility $visibility): static {
		$this->visibility = $visibility;
		return $this;
	}

	public function setPhpType(string $phpType): static {
		CheckUtils::checkPhpType($phpType);
		$this->phpType = $phpType;
		return $this;
	}

	public function setJsType(string $jsType): static {
		$this->jsType = $jsType;
		return $this;
	}

	public function setFieldName(string $fieldName): static {
		CheckUtils::checkPhpFieldName($fieldName);
		$this->fieldName = $fieldName;
		return $this;
	}

	public function setDefaultValue(?string $defaultValue): static {
		$this->defaultValue = $defaultValue;
		return $this;
	}

	public function setIsNullable(bool $isNullable): static {
		$this->isNullable = $isNullable;
		return $this;
	}

	public function setIsConst(bool $isConst): static {
		$this->isConst = $isConst;
		return $this;
	}

	public function setCustomTypeHint(?string $customTypeHint): static {
		$this->customTypeHint = $customTypeHint;
		return $this;
	}

	/**
	 * @param string[] $comments
	 */
	public function setComments(array $comments): static {
		$this->comments = $comments;
		return $this;
	}

	public function addComments(string ...$comments): static {
		array_push($this->comments, ...$comments);
		return $this;
	}

	public function getPhpFileContent(string $prependLinesBy): string {
		$fileContent = '';

		$defaultAsString = $this->defaultValue ? ' = ' . $this->defaultValue : '';
		$phpTypeWithNullableString = $this->phpType;
		if ($this->isNullable) {
			$phpTypeWithNullableString .= '|null';
		}

		if ($this->customTypeHint) {
			$fileContent .= $prependLinesBy . "/** @type $this->customTypeHint */\n";
		}
		$varParts = array();
		if ($this->isConst) {
			if ($this->isNullable) {
				throw new LogicException('A nullable constant have no sense');
			}
			if (!$this->defaultValue) {
				throw new LogicException('A const without a default value will always be empty');
			}
			array_push(
					$varParts,
					'final',
					$this->visibility->value,
					'const',
					'$' . $this->fieldName . $defaultAsString
			);
		} else {
			if (!$this->phpType) {
				throw new LogicException('A PHP field that is not constant must have a type');
			}
			array_push(
					$varParts,
					$this->visibility->value,
					$phpTypeWithNullableString,
					'$' . $this->fieldName . $defaultAsString
			);
		}
		$fileContent .= $prependLinesBy . implode(' ', array_filter($varParts)) . ";";
		if ($this->comments) {
			$fileContent .= ' // ' . implode('. ', $this->comments);
		}

		return $fileContent;
	}

	public function getJsFileContent(string $prependLinesBy): string {
		if (!$this->jsType) {
			throw new LogicException('A JS field must have a type');
		}

		$jsTypeWithNullableString = $this->customTypeHint ?: $this->jsType;
		if ($this->isNullable) {
			$jsTypeWithNullableString .= '|null';
		}

		$fileContent = $prependLinesBy . "/** @type " . '{' . $jsTypeWithNullableString . '}' . " */\n";
		$fileContent .= $prependLinesBy . "$this->fieldName";
		if ($this->defaultValue) {
			$fileContent .= " = $this->defaultValue";
		}
		if ($this->comments) {
			$fileContent .= ' // ' . implode('. ', $this->comments);
		}

		return $fileContent;
	}

}
