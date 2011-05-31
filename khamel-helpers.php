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
 * Includes a file at the current position.
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
 * Wraps content into a <pre> element and indents it properly (i.e. does not indent it).
 * TODO: Allow attributes on <pre> tag.
 */
class PreHelper extends AbstractNode
{
	/**
	 * What will be the output.
	 * @var string
	 */
	protected $output = Khamel::NEWLINE;

	/**
	 * Constructor; creates a new helper for <pre> elements.
	 * @param KhamelQueue $q
	 * @param int $output_indent
	 * @param int $input_indent
	 */
	public function __construct(KhamelQueue $q, $output_indent, $input_indent)
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
		return '<pre>' . $this->output . '</pre>';
	}
}


/**
 * A node that wraps its contents into an HTML snippet, but doesn’t indent it.
 */
abstract class DumbNode extends AbstractNode
{
	/**
	 * What will be the output.
	 * @var string
	 */
	protected $output = Khamel::NEWLINE;

	/**
	 * Constructor;
	 * @param KhamelQueue $q
	 * @param int $output_indent
	 * @param int $min_input_indent
	 */
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
}

/**
 * Wraps content into a <script> element and escapes properly.
 * TODO: Allow attributes on <script> tag.
 */
class JavascriptHelper extends DumbNode
{
	public function __toString()
	{
		if ($this->output == Khamel::NEWLINE)
		{
			return '';
		}
		return '<script type="text/javascript">' . Khamel::NEWLINE . '// <![CDATA[' . $this->output . '// ]]>' . Khamel::NEWLINE . '</script>';
	}
}

/**
 * Wraps content into a <style> element and escapes properly.
 * TODO: Allow attributes on <style> tag.
 */
class CssHelper extends WrapperNode
{
	public function __toString()
	{
		if ($this->output != Khamel::NEWLINE)
		{
			return '';
		}
		return '<style type="text/css">' . Khamel::NEWLINE . '/* <![CDATA[ */' . $this->output . '/* ]]> */' . Khamel::NEWLINE . '</style>';
	}
}

?>