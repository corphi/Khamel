<?php

namespace Khamel;



/**
 * The queue
 * that doesn’t do
 	* a thing.
 * It’s basically a wrapper for a single line string.
 */
class SimpleQueue
{
	/**
	 * Returns the current line. Can fake a specified input indent.
	 * @param int $forced_input_indent
	 * @return string
	 */
	public function get_line($forced_input_indent = null)
	{
		if (is_null($forced_input_indent)) {
			return $this->line;
		}
		if (is_null($this->indent)) {
			throw new Exception('SimpleQueue::get_line() with NULL indent. This should never happen. Report this bug at github.com/corphi/Khamel and include the file that you were processing.');
		}
		return Khamel::spaces($this->indent - $forced_input_indent) . $this->line;
	}
	/**
	 * The current line’s indent.
	 * @var int
	 */
	protected $indent;
	/**
	 * Returns the current line’s indent.
	 * @return int
	 */
	public function get_indent()
	{
		return $this->indent;
	}


	/**
	 * Whether there is a current line.
	 * @return bool
	 */
	public function is_valid()
	{
		return $this->line !== null;
	}


	public function __construct($string)
	{
		$this->line = $string;
	}

	/**
	 * Moves to the next line; in this case: discard current line.
	 */
	public function move_next()
	{
		$this->indent = null;
		$this->line = null;

		return $this;
	}
}
