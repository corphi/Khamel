<?php

namespace Khamel;



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
		if ($line === false) {
			fclose($this->handle);
			return parent::move_next();
		}

		$line = rtrim($line, "\r\n");
		$this->indent = strlen($line) - strlen($this->line = ltrim($line));

		return $this;
	}
}
