<?php

namespace Khamel;



/**
 * Khamel parses a subset of the HAML commands and caches the result.
 * Needs PHP 5.1.
 */
class Khamel extends RootNode
{
	public static $template_path, $cache_path;

	public static $root_indent = -1;

	/**
	 * Input filename.
	 * @var string
	 */
	protected $filename;

	/**
	 * Whether the given HTML element is self-closing.
	 * @param string $tag
	 * @return bool
	 */
	public static function is_empty_element($tag)
	{
		static $empty_elements = array('area', 'base', 'br', 'col', 'hr', 'img', 'input', 'link', 'meta', 'param', 'wbr');
		return in_array($tag, $empty_elements);
	}
	/**
	 * Whether the given HTML element is inline.
	 * @param string $tag
	 * @return bool
	 */
	public static function is_inline_element($tag)
	{
		static $inline_elements = array('a', 'abbr', 'acronym', 'b', 'bdi', 'bdo', 'big', 'button', 'br', 'cite', 'code', 'del', 'dfn', 'em', 'i', 'img', 'input', 'ins', 'kbd', 'label', 'mark', 'param', 'q', 'rb', 'rp', 'rt', 'ruby', 's', 'samp', 'small', 'span', 'strong', 'sub', 'sup', 'time', 'u', 'var', 'wbr');
		return in_array($tag, $inline_elements);
	}

	/**
	 * A line break.
	 * @var string
	 */
	const NEWLINE = '
';

	/**
	 * Generates the specified amount of spaces.
	 * @param int $number
	 * @return string
	 */
	public static function spaces($number)
	{
		static $spacestring = '            ';
		while ($number > strlen($spacestring))
		{
			$spacestring .= $spacestring;
		}

		return substr($spacestring, 0, max(0, $number));
	}

	/**
	 * Returns an instance of the requested helper class.
	 * @param string $name
	 */
	public static function create_helper($name, KhamelQueue $q, $output_indent, $input_indent)
	{
		switch ($name) {
			case 'include':
			case 'pre':
			case 'javascript':
			case 'css':
				$classname = ucfirst($name) . 'Helper';
				return new $classname($q, $output_indent, $input_indent);
		}

		if (isset(self::$custom) && isset(self::$custom[$name])) {
			return self::$custom[$name];
		}

		// TODO: Process errors
	}


	/**
	 * Constructor; creates a new Khamel object by parsing a file.
	 * @param string $filename
	 * @param mixed $subject
	 */
	public function __construct($filename, $subject = null)
	{
		if (!isset(self::$template_path)) {
			throw new Exception('Khamel::__construct(): Khamel::$template_path must be set.');
		}
		if (!isset(self::$cache_path)) {
			throw new Exception('Khamel::__construct(): Khamel::$cache_patch must be set.');
		}

		$this->filename = self::$template_path . "/$filename.haml";
		parent::__construct(new KhamelQueue($this->filename), self::$root_indent);

		$this->subject = $subject;
	}

	public function __toString()
	{
		$tmp_filename = self::$cache_path . '/' . substr(md5($this->filename . filemtime($this->filename)), 0, 10) . '.php';
		if (!file_exists($tmp_filename)
			// FIXME: Remove the hack to strip the newline at the beginning. It is there because all nodes are child nodes by default.
			// This is only a symptom, the cure needs to be applied in AbstractNode::stringify_children().
			&& !file_put_contents($tmp_filename, substr(parent::__toString(), strlen(self::NEWLINE)))
		) {
			return 'Khamel::__toString(): Buffering failed.';
		}

		// TODO: Import variables
		ob_start(null);
		include $tmp_filename;
		$output = ob_get_contents();
		ob_end_clean();

		return $output;
	}
}