<?php

namespace SqlToCodeGenerator\codeGeneration\attribute;

enum ClassFieldEnum {

	case PRIMARY;
	case UNIQUE;
	case IMMUTABLE;
	case GENERATED;

}
