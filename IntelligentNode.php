<?php

namespace Khamel;



/**
 * A node that parses its children.
 * Opposite of DumbNode which does not parse.
 */
class IntelligentNode extends AbstractNode
{
	/**
	 * Creates a new node that will at least output its children.
	 * @param KhamelQueue $q
	 * @param int $output_indent
	 */
	public function __construct(KhamelQueue $q, $output_indent, $min_input_indent)
	{
		parent::__construct($output_indent);

		$this->parse_children($q, $output_indent, $min_input_indent);
	}

	public function __toString()
	{
		return $this->stringify_children();
	}
}
