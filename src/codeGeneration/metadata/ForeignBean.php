<?php

namespace SqlToCodeGenerator\codeGeneration\metadata;

class ForeignBean {

	public Bean $toBean;
	public BeanProperty $withProperty;
	public BeanProperty $onProperty;
	public bool $isArray = false;

}
