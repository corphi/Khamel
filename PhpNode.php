<?php

namespace Khamel;



/**
 * A PHP node.
 */
class PhpNode extends IntelligentNode
{
	/**
	 * Constructor; creates a new PHP node.
	 * @param KhamelQueue $q
	 * @param int $output_indent
	 */
	public function __construct(KhamelQueue $q, $output_indent)
	{
		$line = $q->get_line();
		parent::__construct($q, $output_indent, $q->get_indent() + 1);


/*		switch (substr($line, 0, strpos($zeile, array(' ', '('))))
		 {
			case 'if':
			case 'else':
			case 'while':
			case 'for':
			case 'foreach':
			case 'elseif':
			case 'switch':
			$line .= ':'; // FIXME: Only add it if it’s not there.
			break;

			default:
			$line .= ';'; // FIXME: Same thing.
		}*/

		$line = ltrim(substr($line, 1));

		if ($line[0] == '#' || substr($line, 0, 2) == '//') {
			// Don’t output anything.
			$this->children = array();
			$this->output = '';
			return;
		}

		$this->output = "<?php $line ?>";
	}

	/**
	 * The output.
	 * @var string
	 */
	private $output;

	public function __toString()
	{
		return $this->output;
	}
}
