<?php

namespace Khamel;



/**
 * A node with an input indent of 0.
 */
class RootNode extends IntelligentNode
{
	/**
	 * Creates a new node that will only output its children.
	 * @param KhamelQueue $q
	 * @param int $output_indent
	 */
	public function __construct(KhamelQueue $q, $output_indent)
	{
		parent::__construct($q, $output_indent, 0);
	}
}
