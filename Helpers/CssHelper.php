<?php

namespace Khamel\Helpers;

use Khamel\DumbNode;
use Khamel\Khamel;



/**
 * Wraps content into a <style> element and escapes properly.
 * TODO: Allow attributes on <style> tag.
 */
class CssHelper extends DumbNode
{
	public function __toString()
	{
		if ($this->output === Khamel::NEWLINE) {
			return '';
		}
		return '<style type="text/css">' . Khamel::NEWLINE
		. '/* <![CDATA[ */' . $this->output . '/* ]]> */' . Khamel::NEWLINE
		. Khamel::spaces($this->output_indent) . '</style>';
	}
}
