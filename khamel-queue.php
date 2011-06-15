<?php

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
	public function get_line($forced_input_indent = NULL)
	{
		if (is_null($forced_input_indent))
		{
			return $this->line;
		}
		if (is_null($this->indent))
		{
			echo "KhamelQueue::get_line() with NULL indent\n"; // FIXME: Turn into an exception/remove
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
		return !is_null($this->line);
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
		$this->indent = NULL;
		$this->line = NULL;

		return $this;
	}
}

/**
 * A queue of lines for processing with Khamel.
 * Somehow inspired by Java iterators.
 */
class KhamelQueue extends SimpleQueue
{
	/**
	 * The input file handle.
	 * @var resource
	 */
	protected $handle;
	/**
	 * Constructor; creates a new queue by opening a file.
	 * @param string $filename
	 */
	public function __construct($filename)
	{
		$this->handle = fopen($filename, 'r');
	}


	/**
	 * Processes the next line.
	 * @return KhamelQueue
	 */
	public function move_next()
	{
		$line = fgets($this->handle);
		if ($line === false)
		{
			fclose($this->handle);
			return parent::move_next();
		}

		$line = rtrim($line, "\r\n");
		$this->indent = strlen($line) - strlen($this->line = ltrim($line));

		return $this;
	}
}
