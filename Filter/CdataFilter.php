<?php

namespace Khamel\Filter;

use Khamel\IntelligentNode;



class CdataFilter extends IntelligentNode
{
	public function __toString()
	{
		if ($this->output === Khamel::NEWLINE) {
			return '';
		}
		return '<![CDATA[ ' . Khamel::NEWLINE
			. Khamel::spaces($this->output) . $this->output . Khamel::NEWLINE
			. ' ]]>' . Khamel::NEWLINE;
	}
}
