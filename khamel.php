<?php

include 'khamel-queue.php';
include 'khamel-helpers.php';


class PhpCompiler
{
	public static function execute($code)
	{
		$filename = '/tmp/' . md5($code) . '.php';
		file_put_contents($filename, $code);

		include $filename;
	}
}

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
	 * @var int
	 */
	protected $output_indent;
	/**
	 * Constructor; only saves the output indent (for use in __toString).
	 * @param int $output_indent
	 */
	protected function __construct($output_indent)
	{
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
	 * @param int $output_indent
	 * @return void
	 */
	protected function parse_children(KhamelQueue $q, $output_indent, $min_input_indent)
	{
		$q->move_next();
		while ($q->get_indent() >= $min_input_indent)
		{
			$line = $q->get_line();
			if (is_null($line))
			{
				break;
			}

			if (!isset($line[0]))
			{
				$line = '\\'; // Fake escaping… nothing.
			}

			switch ($line[0])
			{ // TODO: still missing ~, !
				case '-':
					$this->children[] = new PhpNode($q, $output_indent);
					break;
				case '/':
					$this->children[] = new CommentNode($q, $output_indent);
					break;
				case '%':
				case '#':
				case '.':
					$this->children[] = new HtmlNode($q, $output_indent);
					break;
				case ':':
					$this->children[] = Khamel::create_helper(substr($line, 1), $q, $output_indent, $min_input_indent);
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
		if (isset($this->children))
		{
			$is_after_text_node = false;
			foreach ($this->children as $child)
			{
				if (!$child->is_inline || ($is_after_text_node && $child instanceof TextNode))
				{
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

		if (!$this->children)
		{
			return $output;
		}

		$is_after_inline_node = $this->has_only_inline_children(); // Only insert a line break at the beginning if it also has block children
		$is_after_text_node = false;
		$indent = Khamel::spaces($this->output_indent + 1);
		$child = reset($this->children);
		do
		{
			if ($child->is_inline && $is_after_inline_node && !($child instanceof TextNode && $is_after_text_node)) // Directly concatenate inline elements
			{
				$output .= $child;
			}
			else // Insert a line break before, after and between blocks and between TextNodes
			{
				$output .= Khamel::NEWLINE . $indent . $child;
			}

			$is_after_inline_node = $child->is_inline;
			$is_after_text_node = $child instanceof TextNode;
		}
		while ($child = next($this->children));

		return $output;
	}

	public abstract function __toString();
}

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
		parent::__construct($output_indent);

		$this->output = $q->get_line();
		$q->move_next();

		$this->is_inline = true;
	}

	public function __toString()
	{
		if (isset($this->output[0]))
		{
			if ($this->output[0] == '=')
			{
				return '<?php echo htmlspecialchars(' . ltrim(substr($this->output, 1)) . '); ?>';
			}
			if ($this->output[0] == '\\')
			{
				return substr($this->output, 1);
			}
		}
		return $this->output;
	}
}

/**
 * An XHTML node.
 */
class HtmlNode extends IntelligentNode
{
	/**
	 * Whether this element is self-closing.
	 * @var bool
	 */
	protected $is_empty;

	/**
	 * Constructor; parses an XHTML node from the queue.
	 * @param KhamelQueue $q
	 * @param int $output_indent
	 */
	public function __construct(KhamelQueue $q, $output_indent)
	{
		$line = $q->get_line();
		if (!preg_match('@^(\%[^() =#.]+)?([#.][^() =]*)?@', $line, $matches))
		{
			die('HtmlNode::__construct(): No node in <code>' . htmlspecialchars($line) . '</code>');
		}

		if (isset($matches[1]) && $matches[1]) // Tag
		{
			$tag = substr($matches[1], 1);
			unset($matches[1]);
		}
		else
		{
			$tag = 'div';
		}

		if (isset($matches[2]) && $matches[2]) // Identifier parts and/or CSS classes
		{
			if (preg_match_all('@[#.][^#.]*@', $matches[2], $foo))
			{
				foreach ($foo[0] as $bar) // Buffer them all
				{
					if ($bar[0] == '#')
					{
						$id[] = substr($bar, 1);
					}
					else
					{
						$class[] = substr($bar, 1);
					}
				}
			}
			unset($foo);
		}
		$line = substr($line, strlen($matches[0]));
		unset($matches);

		// TODO: Object reference; how does it actually work?

		while (isset($line[0]) && ($line[0] == '(' || $line[0] == '{')) // Attributes
		{
			if ($line[0] == '(') // HTML-style attributes
			{
				$line = substr($line, 1);

				while (preg_match('@([^=]+)=(".*?"|[^"].*?)\s*\)?@', $line, $match)) // FIXME: Add support for shorthand attributes
				{
					if ($match[1] == 'class' || $match[1] == 'id')
					{
						// Append attribute value to list
						${$match[1]}[] = $match[2];
					}
					else
					{
						$attr[$match[1]] = $match[2];
					}
					$line = substr($line, strlen($match[0]));

					if (substr($match[0], -1) == ')')
					{
						break;
					}
				}
				continue; // searching for attribute hashes
			}

			// Ruby-style attributes
			while (preg_match('@(?:".+?"|\'.+?\'|:.+?)\s*(=>)\s*(.*?),@', $line, $match))
			{
				if ($match[2] == '=>') // Attribute
				{
					
				}
				else // Function call
				{
					
				}
			}
		}

		$line = ltrim($line);

		if (isset($line[0])) // First child
		{
			// Wrap the child (which will be a text node) into a SimpleQueue and add it before all other children.
			$this->children[] = new TextNode(new SimpleQueue($line), $output_indent + 1);
		}

		if (isset($id)) // Merge identifier
		{
			if (isset($attr['id']))
			{
				$attr['id'] = $id + $attr['id'];
			}
			unset($id);
		}
		if (isset($attr['id']))
		{
			$attr['id'] = implode('_', array_filter(array_flatten($attr['id'])));
		}
		if (isset($class)) // Merge CSS classes
		{
			$attr['class'] = (isset($attr['class']) ? substr($attr['class'], 0, -1) . ' ' : '"') . implode(' ', $class) . '"';
			unset($class);
		}
		else if (isset($attr['class']))
		{
			$attr['class'] = '"' . implode(' ', $attr['class']) . '"';
		}

		parent::__construct($q, $output_indent + 1, $q->get_indent() + 1);

		$this->is_inline = Khamel::is_inline_element($tag);

		// Concatenate attributes
		if (isset($attr))
		{
			foreach ($attr as $k => $v)
			{
				$tag .= " $k=$v";
			}
		}
		$this->tag = $tag;
	}

	/**
	 * The opening tag.
	 * @var string
	 */
	private $tag;

	public function __toString()
	{
		$tag = $this->tag;
		unset($this->tag);

		$output = "<$tag";
		preg_match('@^\S+@', $tag, $matches);
		$tag = $matches[0];

		if (Khamel::is_empty_element($tag))
		{
			return "$output />";
		}
		$output .= '>' . $this->stringify_children();

		if ($this->has_only_inline_children())
		{
			return "$output</$tag>";
		}
		return $output . Khamel::NEWLINE . Khamel::spaces($this->output_indent) . "</$tag>";
	}
}

/**
 * A PHP node.
 */
class PhpNode extends IntelligentNode
{
	/**
	 * Constructor; creates a new PHP node.
	 * @param KhamelQueue $q
	 * @param int $output_indent
	 */
	public function __construct(KhamelQueue $q, $output_indent)
	{
		$line = $q->get_line();
		parent::__construct($q, $output_indent, $q->get_indent() + 1);


/*		switch (substr($line, 0, strpos($zeile, array(' ', '('))))
		{
			case 'if':
			case 'else':
			case 'while':
			case 'for':
			case 'foreach':
			case 'elseif':
			case 'switch':
				$line .= ':'; // FIXME: Only add it if it’s not there.
				break;

			default:
				$line .= ';'; // FIXME: Same thing.
		}*/

		$line = ltrim(substr($line, 1));

		if ($line[0] == '#' || substr($line, 0, 2) == '//')
		{
			// Don’t output anything.
			$this->children = array();
			$this->output = '';
			return;
		}

		$this->output = "<?php $line ?>";
	}

	/**
	 * The output.
	 * @var string
	 */
	private $output;

	public function __toString()
	{
		return $this->output;
	}
}

/**
 * A comment. TODO: Can do conditional ones too,
 */
class CommentNode extends IntelligentNode
{
	/**
	 * Constructor; creates a new comment node. Does parse its children.
	 * @param KhamelQueue $q
	 * @param int $output_indent
	 * @param int $input_indent
	 */
	public function __construct(KhamelQueue $q, $output_indent, $min_input_indent)
	{
		parent::__construct($q, $output_indent, $min_input_indent);
	}

	public function __toString()
	{
		// TODO: Indent correctly.
		return '<!-- -->';
	}
}

/**
 * A !DOCTYPE declaration, only supports XHTML 1.1 and (X)HTML 5.
 */
class DoctypeNode extends AbstractNode
{
	protected $output = '';

	/**
	 * Constructor; creates a new doctype.
	 * @param KhamelQueue $q
	 * @param int $output_indent
	 */
	public function __construct(KhamelQueue $q, $output_indent)
	{
		parent::__construct($output_indent);

		if ($q->get_line() == '!!! 1.1')
		{
			$this->output = ' PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd"';
		}

		$q->move_next();
	}
	public function __toString()
	{
		return "<!DOCTYPE html{$this->output}>";
	}
}

/**
 * A node that parses its children.
 * Opposite of DumbNode which does not parse.
 */
class IntelligentNode extends AbstractNode
{
	/**
	 * Creates a new node that will at least output its children.
	 * @param KhamelQueue $q
	 * @param int $output_indent
	 */
	public function __construct(KhamelQueue $q, $output_indent, $min_input_indent)
	{
		parent::__construct($output_indent);

		$this->parse_children($q, $output_indent, $min_input_indent);
	}

	public function __toString()
	{
		return $this->stringify_children();
	}
}

/**
 * A node with an input indent of 0.
 */
class RootNode extends IntelligentNode
{
	/**
	 * Creates a new node that will only output its children.
	 * @param KhamelQueue $q
	 * @param int $output_indent
	 */
	public function __construct(KhamelQueue $q, $output_indent)
	{
		parent::__construct($q, $output_indent, 0);
	}
}

/**
 * Khamel parses a subset of the HAML commands and caches the result.
 * Needs PHP 5.1.
 */
class Khamel extends RootNode
{
	public static $template_path, $cache_path;

	public static $root_indent = -1;

	/**
	 * Input filename.
	 * @var string
	 */
	protected $filename;

	/**
	 * Whether the given HTML element is self-closing.
	 * @param string $tag
	 * @return bool
	 */
	public static function is_empty_element($tag)
	{
		static $empty_elements = array('area', 'base', 'br', 'col', 'hr', 'img', 'input', 'link', 'meta', 'param');
		return in_array($tag, $empty_elements);
	}
	/**
	 * Whether the given HTML element is inline.
	 * @param string $tag
	 * @return bool
	 */
	public static function is_inline_element($tag)
	{
		static $inline_elements = array('a', 'abbr', 'acronym', 'bdo', 'big', 'button', 'br', 'cite', 'del', 'dfn', 'em',  'img', 'input', 'ins', 'kbd', 'label', 'param', 'q', 'samp', 'small', 'span', 'strong', 'sub', 'sup', 'var');
		return in_array($tag, $inline_elements);
	}

	/**
	 * A line break.
	 * @var string
	 */
	const NEWLINE = '
';

	/**
	 * Generates the specified amount of spaces.
	 * @param int $number
	 * @return string
	 */
	public static function spaces($number)
	{
		static $spacestring = '            ';
		while ($number > strlen($spacestring))
		{
			$spacestring .= $spacestring;
		}

		return substr($spacestring, 0, max(0, $number));
	}

	/**
	 * Returns an instance of the requested helper class.
	 * @param string $name
	 */
	public static function create_helper($name, KhamelQueue $q, $output_indent, $input_indent)
	{
		switch ($name)
		{
			case 'cache':
			case 'include':
			case 'pre':
			case 'javascript':
			case 'css':
				$classname = ucfirst($name) . 'Helper';
				return new $classname($q, $output_indent, $input_indent);
		}

		if (isset(self::$custom) && isset(self::$custom[$name]))
		{
			return self::$custom[$name];
		}

		// TODO: Process errors
	}


	/**
	 * Constructor; creates a new Khamel object by parsing a file.
	 * @param string $filename
	 * @param mixed $subject
	 */
	public function __construct($filename, $subject = null)
	{
		if (!isset(self::$template_path))
		{
			throw new Exception('Khamel::__construct(): Khamel::$template_path must be set.');
		}
		if (!isset(self::$cache_path))
		{
			throw new Exception('Khamel::__construct(): Khamel::$cache_patch must be set.');
		}

		$this->filename = self::$template_path . "/$filename.haml";
		parent::__construct(new KhamelQueue($this->filename), self::$root_indent);

		$this->subject = $subject;
	}

	public function __toString()
	{
		$tmp_filename = self::$cache_path . '/' . substr(md5($this->filename . filemtime($this->filename)), 0, 10) . '.php';
		if (!file_exists($tmp_filename)
			// FIXME: Remove the hack to strip the newline at the beginning. It is there because all nodes are child nodes by default.
			// This is only a symptom, the cure needs to be applied in AbstractNode::stringify_children().
			&& !file_put_contents($tmp_filename, substr(parent::__toString(), strlen(self::NEWLINE))))
		{
			return 'Khamel::__toString(): Buffering failed.';
		}

		// TODO: Import variables
		ob_start(null);
		include $tmp_filename;
		$output = ob_get_contents();
		ob_end_clean();

		return $output;
	}
}

echo '<?xml version="1.0" encoding="UTF-8" ?>', Khamel::NEWLINE;

Khamel::$template_path = '.';
Khamel::$cache_path = '/tmp';

$khamel = new Khamel('moo');
echo $khamel;
