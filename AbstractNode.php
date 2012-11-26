<?php

namespace Khamel;



/**
 * Base class for nodes.
 */
abstract class AbstractNode
{
	/**
	 * Whether this node should be displayed inline (used for line breaking purposes).
	 * @var bool
	 */
	protected $is_inline;

	/**
	 * Output indent for this node.
	 * @var integer
	 */
	protected $output_indent;

	/**
	 * @var KhamelQueue
	 */
	protected $queue;

	/**
	 * @return KhamelQueue
	 */
	public function get_queue()
	{
		return $this->queue;
	}

	/**
	 * @param KhamelQueue $q
	 * @param integer $output_indent
	 */
	protected function __construct(KhamelQueue $q, $output_indent)
	{
		$this->queue = $q;
		$this->output_indent = $output_indent;
	}

	/**
	 * Holds all child nodes.
	 * @var array
	 */
	protected $children;

	/**
	 * Parses all child nodes an adds them to the list.
	 * @param KhamelQueue $q
	 * @param integer $output_indent
	 */
	protected function parse_children(KhamelQueue $q, $output_indent, $min_input_indent)
	{
		$q->move_next();
		while ($q->get_indent() >= $min_input_indent)
		{
			$line = $q->get_line();
			if ($line === null) {
				break;
			}

			if (!isset($line[0])) {
				$line = '\\'; // Fake escaping… nothing.
			}

			switch ($line[0])
			{ // TODO: still missing ~
				case '-':
					$this->children[] = new PhpNode($q, $output_indent);
					break;
				case '/':
					$this->children[] = new CommentNode($q, $output_indent);
					break;
				case '%':
				case '#':
				case '.':
				case '[':
				case '(':
				case '{':
					$this->children[] = new HtmlNode($q, $output_indent);
					break;
				case ':':
					$this->children[] = Khamel::create_filter($q, $output_indent, $min_input_indent);
					break;
				case '!':
					if (substr($line, 0, 3) == '!!!')
					{
						$this->children[] = new DoctypeNode($q, $output_indent);
						break;
					}
				default:
					$this->children[] = new TextNode($q, $output_indent);
			}
		}
	}

	/**
	 * Whether all child nodes are inline.
	 * @return bool
	 */
	protected function has_only_inline_children()
	{
		if (isset($this->children)) {
			$is_after_text_node = false;
			foreach ($this->children as $child) {
				if (!$child->is_inline || ($is_after_text_node && $child instanceof TextNode)) {
					return false;
				}

				$is_after_text_node = $child instanceof TextNode;
			}
		}
		return true;
	}

	/**
	 * Packages all child nodes into a string.
	 * @return string
	 */
	protected function stringify_children()
	{
		$output = '';

		if (!$this->children) {
			return $output;
		}

		$is_after_inline_node = $this->has_only_inline_children(); // Only insert a line break at the beginning if it also has block children
		$is_after_text_node = false;
		$indent = Khamel::spaces($this->output_indent + 1);
		$child = reset($this->children);
		do {
			if ($child->is_inline && $is_after_inline_node
				&& !($child instanceof TextNode && $is_after_text_node)
			) {
				// Directly concatenate inline elements
				$output .= $child;
			} else {
				// Insert a line break before, after and between blocks and between TextNodes
				$output .= Khamel::NEWLINE . $indent . $child;
			}

			$is_after_inline_node = $child->is_inline;
			$is_after_text_node = $child instanceof TextNode;
		} while ($child = next($this->children));

		return $output;
	}

	public abstract function __toString();
}
