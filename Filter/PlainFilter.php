<?php

namespace Khamel\Filter;

use Khamel\DumbNode;



class PlainFilter extends DumbNode
{
	public function __toString()
	{
		return $this->output;
	}
}
