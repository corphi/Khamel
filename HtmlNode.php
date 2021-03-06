<?php

namespace Khamel;



/**
 * An XHTML node.
 */
class HtmlNode extends IntelligentNode
{
	/**
	 * Whether this element is self-closing.
	 * @var bool
	 */
	protected $is_empty;

	/**
	 * Constructor; parses an XHTML node from the queue.
	 * @param KhamelQueue $q
	 * @param integer $output_indent
	 */
	public function __construct(KhamelQueue $q, $output_indent)
	{
		$line = $q->get_line();
		if (!preg_match('@^(\%[^() =#.]+)?([#.][^() =]*)?@', $line, $matches)) {
			throw new \Exception('HtmlNode::__construct(): No node in <code>' . htmlspecialchars($line) . '</code>');
		}

		// Tag
		if (isset($matches[1]) && $matches[1]) {
			$tag = substr($matches[1], 1);
			unset($matches[1]);
		} else {
			$tag = 'div';
		}

		// Identifier parts and/or CSS classes
		if (isset($matches[2]) && $matches[2]) {
			if (preg_match_all('@[#.][^#.]*@', $matches[2], $foo)) {
				foreach ($foo[0] as $bar) { // Buffer them all
					if ($bar[0] == '#') {
						$id[] = '"' . substr($bar, 1) . '"';
					} else {
						$class[] = '"' . substr($bar, 1) . '"';
					}
				}
			}
			unset($foo);
		}
		$line = substr($line, strlen($matches[0]));
		unset($matches);

		// TODO: Object reference

		// Attributes
		while (isset($line[0]) && ($line[0] === '(' || $line[0] === '{')) {
			// HTML-style attributes
			if ($line[0] === '(') {
				$line = substr($line, 1);

				while (preg_match('@([^=]+)=(".*?"|[^"].*?)\s*\)?@', $line, $match)) { // FIXME: Add support for shorthand attributes
					if ($match[1] === 'class' || $match[1] === 'id') {
						// Append attribute value to list
						${$match[1]}[] = $match[2];
					} else {
						$attr[$match[1]] = $match[2];
					}
					$line = substr($line, strlen($match[0]));

					if (substr($match[0], -1) === ')') {
						break;
					}
				}
				continue; // searching for attribute hashes
			}

			// Ruby-style attributes
			while (preg_match('@(?:".+?"|\'.+?\'|:.+?)\s*(=>)\s*(.*?),@', $line, $match)) {
				if ($match[2] === '=>') { // Attribute
					
				} else { // Function call
					
				}
			}
		}

		$line = ltrim($line);

		if (isset($line[0])) { // First child
			// Wrap the child (which will be a text node) into a SimpleQueue and add it before all other children.
			$this->children[] = new TextNode(new SimpleQueue($line), $output_indent + 1);
		}

		if (isset($id)) { // Merge identifier
			if (function_exists('\\Shy\\array_flatten')) {
				$id = \Shy\array_flatten($id);
			}
			$attr['id'] = implode('_', array_filter($id));
			$attr['id'] = str_replace('"_"', '_', $attr['id']);
			unset($id);
		}
		if (isset($class)) { // Merge CSS classes
			if (function_exists('\\Shy\\array_flatten')) {
				$class = \Shy\array_flatten($class);
			}
			$attr['class'] = implode(' ', $class);
			$attr['class'] = str_replace('" "', ' ', $attr['class']);
			unset($class);
		}

		parent::__construct($q, $output_indent + 1, $q->get_indent() + 1);

		$this->is_inline = Khamel::is_inline_element($tag);

		// Concatenate attributes
		if (isset($attr)) {
			foreach ($attr as $k => $v) {
				$tag .= " $k=$v";
			}
		}
		$this->tag = $tag;
	}

	/**
	 * The opening tag.
	 * @var string
	 */
	private $tag;

	public function __toString()
	{
		$tag = $this->tag;
		unset($this->tag);

		$output = "<$tag";
		preg_match('@^\S+@', $tag, $matches);
		$tag = $matches[0];

		if (Khamel::is_empty_element($tag)) {
			return "$output />";
		}
		$output .= '>' . $this->stringify_children();

		if ($this->has_only_inline_children()) {
			return "$output</$tag>";
		}
		return $output . Khamel::NEWLINE . Khamel::spaces($this->output_indent) . "</$tag>";
	}
}
