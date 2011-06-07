<?php

/**
 * TODO: Will allow caching of snippets that contain dynamic contents. Someday.
 */
class CacheHelper extends IntelligentNode
{
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
class PreHelper extends IntelligentNode
{
	public function __toString()
	{
		return '<pre>' . parent::__toString() . '</pre>';
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
		$this->is_inline = false;

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
		return '<script type="text/javascript">' . Khamel::NEWLINE
			. '// <![CDATA[' . $this->output . '// ]]>' . Khamel::NEWLINE
			. Khamel::spaces($this->output_indent) . '</script>';
	}
}

/**
 * Wraps content into a <style> element and escapes properly.
 * TODO: Allow attributes on <style> tag.
 */
class CssHelper extends DumbNode
{
	public function __toString()
	{
		if ($this->output == Khamel::NEWLINE)
		{
			return '';
		}
		return '<style type="text/css">' . Khamel::NEWLINE
			. '/* <![CDATA[ */' . $this->output . '/* ]]> */' . Khamel::NEWLINE
			. Khamel::spaces($this->output_indent) . '</style>';
	}
}
