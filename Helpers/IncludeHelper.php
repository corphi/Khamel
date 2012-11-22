<?php

namespace Khamel\Helpers;

use Khamel\RootNode;
use Khamel\Khamel;



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
