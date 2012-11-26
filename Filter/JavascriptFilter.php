<?php

namespace Khamel\Filter;

use Khamel\DumbNode;
use Khamel\Khamel;



/**
 * Wraps content into a <script> element and escapes properly.
 * TODO: Allow attributes on <script> tag.
 */
class JavascriptFilter extends DumbNode
{
	public function __toString()
	{
		if ($this->output === Khamel::NEWLINE) {
			return '';
		}
		return '<script type="text/javascript">' . Khamel::NEWLINE
		. '// <![CDATA[' . $this->output . '// ]]>' . Khamel::NEWLINE
		. Khamel::spaces($this->output_indent) . '</script>';
	}
}
