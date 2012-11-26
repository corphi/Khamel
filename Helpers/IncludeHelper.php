<?php

namespace Khamel\Helpers;

use Khamel\RootNode;
use Khamel\Khamel;
use Khamel\KhamelQueue;



/**
 * Includes a file at the current position.
 */
class IncludeHelper extends RootNode
{
	/**
	 * @param KhamelQueue $q
	 * @param integer $output_indent
	 */
	public function __construct(KhamelQueue $q, $output_indent)
	{
		$filename = ltrim(substr($q->get_line(), 9));
		$q->move_next();
		if ($filename && $filename[0] === '$') {
			$filename = eval("return $filename;");
		}
		$filename = Khamel::$template_path . "/$filename.haml";
		$qq = new KhamelQueue($filename);

		parent::__construct($qq, $output_indent);
	}
}
