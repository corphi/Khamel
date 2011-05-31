<?php

/**
 *
 */
class CacheHelper extends AbstractNode
{
	public function __construct(KhamelQueue $q, $output_indent, $min_input_indent)
	{
		parent::__construct($output_indent);
		$this->parse_children($q, $output_indent, $min_input_indent);
	}

	public function __toString()
	{
		return parent::stringify_children();
	}
}

/**
 *
 */
class IncludeHelper extends RootNode
{
	public function __construct(KhamelQueue $q, $output_indent)
	{
		$filename = substr($q->get_line(), 9); // FIXME: Allow variable file names
		$this->file = Khamel::$template_path . "/$filename.haml";
		$qq = new KhamelQueue($this->file);

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
class JavascriptHelper extends AbstractNode
{
	public function __construct(KhamelQueue $q, $output_indent, $min_input_indent)
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
class CssHelper extends AbstractNode
{
	/**
	 * What will be the output.
	 * @var string
	 */
	private $output = Khamel::NEWLINE;

	public function __construct(KhamelQueue $q, $output_indent, $min_input_indent)
	{
		parent::__construct($output_indent);

		$q->move_next();
		while ($q->get_indent() >= $min_input_indent)
		{
			$this->output .= $q->get_line() . Khamel::NEWLINE;

			$q->move_next();
		}
	}

	public function __toString()
	{
		return '<style type="text/css">' . Khamel::NEWLINE . '/* <![CDATA[ */' . $this->output . '/* ]]> */' . Khamel::NEWLINE . '</style>';
	}
}

?>