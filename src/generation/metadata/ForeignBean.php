<?php

namespace SqlToCodeGenerator\generation\metadata;

class ForeignBean {
	public Bean $toBean;
	public BeanProperty $withProperty;
	public BeanProperty $onProperty;
	public bool $isArray = false;
}
