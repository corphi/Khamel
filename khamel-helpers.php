<?php

/**
 * 
 */
class CacheHelper extends AbstractNode
{
	public function __construct(KhamelQueue $q, $output_indent, $input_indent)
	{
		parent::__construct($output_indent);
	}
}

/**
 * 
 */
class IncludeHelper extends RootNode
{
	public function __construct(KhamelQueue $q, $output_indent, $input_indent)
	{
		$filename = substr($q->get_line(), 9);
		$qq = new KhamelQueue(file($filename, Khamel::$template_path . "/$filename.haml", FILE_IGNORE_NEW_LINES));

		parent::__construct($qq, $output_indent);
	}
}

/**
 * 
 */
class PreHelper extends AbstractNode
{
	public function __construct(KhamelQueue $q, $output_indent, $input_indent)
	{
		parent::__construct($output_indent);
	}

	public function __toString()
	{
		return '<pre>' . '</pre>';
	}
}

/**
 * 
 */
class ScriptHelper extends AbstractNode
{
	public function __construct(KhamelQueue $q, $output_indent, $input_indent)
	{
		parent::__construct($output_indent);
	}

	public function __toString()
	{
		return '<script type="text/javascript">' . Khamel::NEWLINE . '// <![CDATA[' . '// ]]>' . Khamel::NEWLINE . '</script>';
	}
}

/**
 * 
 */
class StyleHelper extends AbstractNode
{
	public function __construct(KhamelQueue $q, $output_indent, $input_indent)
	{
		parent::__construct($output_indent);
	}

	public function __toString()
	{
		return '<style type="text/css">' . Khamel::NEWLINE . '/* <![CDATA[ */' . '/* ]]> */' . Khamel::NEWLINE . '</style>';
	}
}

?>