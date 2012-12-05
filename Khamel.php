<?php

namespace Khamel;



/**
 * Khamel parses a subset of the HAML commands and caches the result.
 * Needs PHP 5.1.
 * 
 * @author Philipp Cordes
 * @license GNU General Public License, version 3
 */
class Khamel extends RootNode
{
	/**
	 * @var string
	 */
	public static $template_path, $cache_path;

	/**
	 * @var integer
	 */
	public static $root_indent = -1;

	/**
	 * Whether the given HTML element is self-closing.
	 * @param string $tag
	 * @return boolean
	 */
	public static function is_empty_element($tag)
	{
		static $empty_elements = array('area', 'base', 'br', 'col', 'hr', 'img', 'input', 'link', 'meta', 'param', 'wbr');
		return in_array($tag, $empty_elements);
	}
	/**
	 * Whether the given HTML element is inline.
	 * @param string $tag
	 * @return boolean
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
	 * Generates the specified amount of double spaces.
	 * @param integer $number
	 * @return string
	 */
	public static function spaces($number)
	{
		static $spacestring = '            ';
		$number <<= 1;
		while ($number > strlen($spacestring)) {
			$spacestring .= $spacestring;
		}

		return substr($spacestring, 0, max(0, $number));
	}

	/**
	 * Returns an instance of the requested filter class.
	 * @param KhamelQueue $q
	 * @param integer $output_indent
	 * @param integer $input_indent
	 */
	public static function create_filter(KhamelQueue $q, $output_indent, $input_indent)
	{
		$name = substr($q->get_line(), 1);
		list($name) = explode(' ', $name);
		switch ($name) {
			case 'cdata':
			case 'css':
			case 'include':
			case 'javascript':
			case 'plain':
			case 'pre':
				$classname = 'Khamel\\Filter\\' . ucfirst($name) . 'Filter';
				return new $classname($q, $output_indent, $input_indent + 1);
		}

		if (isset(self::$custom) && isset(self::$custom[$name])) {
			return self::$custom[$name];
		}

		// TODO: Process errors
		$q->move_next();
	}


	/**
	 * Constructor; creates a new Khamel object by parsing a file.
	 * @param string $filename
	 */
	public function __construct($filename)
	{
		if (!isset(self::$template_path)) {
			throw new \Exception('Khamel::__construct(): Khamel::$template_path must be set.');
		}
		if (!isset(self::$cache_path)) {
			throw new \Exception('Khamel::__construct(): Khamel::$cache_patch must be set.');
		}

		$filename = self::$template_path . "/$filename.haml";
		parent::__construct(new KhamelQueue($filename), self::$root_indent);
	}

	public function __toString()
	{
		$tmp_filename = $this->get_queue()->get_filename();
		$base_name = basename($tmp_filename, '.haml');

		$tmp_filename = md5($tmp_filename . filemtime($tmp_filename), true);
		$tmp_filename = substr(base64_encode($tmp_filename), 0, 8);
		$tmp_filename = str_replace('/', '_', $tmp_filename); // base64 may contain slashes
		$tmp_filename = self::$cache_path . "/$base_name-$tmp_filename.php";
		unset($base_name);

		if (true || !file_exists($tmp_filename)) {
			// FIXME: Activate buffering
			if (!file_put_contents($tmp_filename, substr(parent::__toString(), strlen(self::NEWLINE)))) {
				// FIXME: Remove the hack to strip the newline at the beginning. It is there because all nodes are child nodes by default.
				// This is only a symptom, the cure needs to be applied in AbstractNode::stringify_children().
				return 'Khamel::__toString(): Buffering failed.';
			}
		}

		// TODO: Import variables
		ob_start(null);
		include $tmp_filename;
		$output = ob_get_contents();
		ob_end_clean();

		return $output ?: $tmp_filename;
	}
}
