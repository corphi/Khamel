<?php

namespace Khamel\Helpers;

use Khamel\DumbNode;



/**
 * Wraps content into a <script> element and escapes properly.
 * TODO: Allow attributes on <script> tag.
 */
class JavascriptHelper extends DumbNode
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
