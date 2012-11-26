<?php

namespace Khamel\Filter;

use Khamel\IntelligentNode;



/**
 * Wraps content into a <pre> element and indents it properly (i.e. does not indent it).
 * TODO: Allow attributes on <pre> tag.
 */
class PreFilter extends IntelligentNode
{
	public function __toString()
	{
		return '<pre>' . parent::__toString() . '</pre>';
	}
}
