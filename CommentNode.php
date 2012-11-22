<?php

namespace Khamel;



/**
 * A comment. TODO: Can do conditional ones too,
 */
class CommentNode extends IntelligentNode
{
	/**
	 * Constructor; creates a new comment node. Does parse its children.
	 * @param KhamelQueue $q
	 * @param int $output_indent
	 * @param int $min_input_indent
	 */
	public function __construct(KhamelQueue $q, $output_indent, $min_input_indent)
	{
		parent::__construct($q, $output_indent, $min_input_indent);
	}

	public function __toString()
	{
		// TODO: Indent correctly.
		return '<!-- -->';
	}
}
