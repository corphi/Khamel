<?php

namespace Khamel;



/**
 * A !DOCTYPE declaration, only supports XHTML 1.1 and (X)HTML 5.
 */
class DoctypeNode extends AbstractNode
{
	protected $output = '';

	/**
	 * Constructor; creates a new doctype.
	 * @param KhamelQueue $q
	 * @param int $output_indent
	 */
	public function __construct(KhamelQueue $q, $output_indent)
	{
		parent::__construct($output_indent);

		if ($q->get_line() == '!!! 1.1') {
			$this->output = ' PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd"';
		}

		$q->move_next();
	}
	public function __toString()
	{
		return "<!DOCTYPE html{$this->output}>";
	}
}
