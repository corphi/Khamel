<?php

namespace Khamel;



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
		while ($q->get_indent() >= $min_input_indent) {
			$this->output .= $q->get_line() . Khamel::NEWLINE;

			$q->move_next();
		}
	}
}
