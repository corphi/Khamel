<?php

namespace Khamel;



/**
 * Simple text node. Also does evaluations and unescaping.
 * May not have children; effectively delegates parsing them to its parent node.
 */
class TextNode extends AbstractNode
{
	/**
	 * Output text.
	 * @var string
	 */
	protected $output;

	/**
	 * Constructor; parses a text node from the queue.
	 * Also allows passing a SimpleQueue.
	 * @param SimpleQueue $q
	 * @param int $output_indent
	 */
	public function __construct(SimpleQueue $q, $output_indent)
	{
		parent::__construct($q, $output_indent);

		$this->output = $q->get_line();
		$q->move_next();

		$this->is_inline = true;
	}

	public function __toString()
	{
		if (isset($this->output[0])) {
			if ($this->output[0] === '=') {
				return '<?php echo htmlspecialchars(' . ltrim(substr($this->output, 1)) . '); ?>';
			}
			if ($this->output[0] === '\\') {
				return substr($this->output, 1);
			}
		}
		return $this->output;
	}
}
