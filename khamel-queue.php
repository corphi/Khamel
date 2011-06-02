<?php

/**
 * A queue of lines for processing with Khamel.
 * Somehow inspired by Java iterators.
 */
class KhamelQueue
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
	 * Whether there is a current line.
	 * @return bool
	 */
	public function is_valid()
	{
		return !is_null($this->line);
	}

	/**
	 * The current line.
	 * @var string
	 */
	protected $line;
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
	 * Processes the next line.
	 * @return KhamelQueue
	 */
	public function move_next()
	{
		$line = fgets($this->handle);
		if ($line === false)
		{
			fclose($this->handle);
			$this->indent = NULL;
			$this->line = NULL;
		}
		else
		{
			$line = rtrim($line, "\r\n");
			$this->indent = strlen($line) - strlen($this->line = ltrim($line));
		}

		return $this;
	}
}
