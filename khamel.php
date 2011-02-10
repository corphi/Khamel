<?php

/**
 * A queue of lines for processing with Khamel.
 * Somehow inspired by Java iterators.
 */
class KhamelQueue
{
	/**
	 * The lines.
	 * @var array
	 */
	protected $arr;
	/**
	 * Constructor; creates a new queue from an array.
	 * @param array $arr
	 */
	public function __construct(array $arr)
	{
		$this->arr = $arr;
	}

	/**
	 * Whether there are further lines.
	 * @return bool
	 */
	public function has_next()
	{
		return count($this->arr) > 0;
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
			echo "get_line() während NULL\n";
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
	 * Always check whether there actually is a next line!
	 * @return KhamelQueue
	 */
	public function move_next()
	{
		$zeile = reset($this->arr);
		if (!$zeile)
		{
			$this->indent = NULL;
			$this->line = NULL;
		}
		else
		{
			$this->indent = strlen($zeile) - strlen($this->line = ltrim($zeile));
			unset($this->arr[key($this->arr)]);
		}

		return $this;
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
		if (!$q->has_next())
		{
			return;
		}

		$q->move_next();
		while ($q->get_indent() >= $min_input_indent)
		{
			$zeile = $q->get_line();
			switch ($zeile[0])
			{
				case '-':
					$this->children[] = new PhpNode($q, $output_indent);
					break;
				case '/':
					$this->children[] = new CommentNode($q, $output_indent);
					break;
				case '%':
				case '.':
				case '#':
					$this->children[] = new HtmlNode($q, $output_indent);
					break;
				case ':':
					$this->children[] = Khamel::get_helper(substr($zeile, 1), $q, $output_indent);
					break;
				case '!':
					if ($zeile == '!!! 1.1')
					{
						$this->children[] = new DoctypeNode($q, $output_indent);
						break;
					}
				default:
					$this->children[] = new TextNode($q, $output_indent);
			}

			if (is_null($q->get_indent()))
			{
				break;
			}
		}
	}

	/**
	 * Whether all child nodes are inline.
	 * @return bool
	 */
	protected function has_only_inline_children()
	{
		$children_inline = true;
		if (isset($this->children))
		{
			foreach ($this->children as $child)
			{
				$children_inline = $child->is_inline;
				if (!$children_inline)
				{
					break;
				}
			}
		}
		return $children_inline;
	}

	/**
	 * Packages all child nodes into a string
	 * @return string
	 */
	protected function stringify_children()
	{
		$output = '';

		if (!$this->children)
		{
			return $output;
		}

		$previous_inline = $this->has_only_inline_children(); // Only insert a line break at the beginning if it also has block children
		$indent = Khamel::spaces($this->output_indent + 1);
		$child = reset($this->children);
		do
		{
			if ($child->is_inline && $previous_inline) // Directly concatenate inline elements
			{
				$output .= $child;
			}
			else // Insert a line break before, after and between blocks
			{
				$output .= Khamel::NEWLINE . $indent . $child;
			}
			$previous_inline = $child->is_inline;
		}
		while ($child = next($this->children));

		return $output;
	}

	public abstract function __toString();
}

/**
 * Simple text node. TODO: Can do evaluations as well.
 */
class TextNode extends AbstractNode
{
	/**
	 * Output text.
	 * @var string
	 */
	protected $string;

	/**
	 * Constructor; parses a text node from the queue.
	 * @param KhamelQueue $q
	 * @param int $output_indent
	 */
	public function __construct(KhamelQueue $q, $output_indent)
	{
		parent::__construct($output_indent);

		$this->string = $q->get_line();
		$q->move_next();

		$this->is_inline = true;
	}

	public function __toString()
	{
		if (gettype($this->string) != 'string')
		{
			return print_r($this->string, 1) . ':' . $this->string;
		}
		return $this->string;
		'<?php echo htmlspecialchars(' . ltrim(substr($this->string, 1)) . '); ?>';
	}
}

/**
 * An XHTML node.
 */
class HtmlNode extends AbstractNode
{
	/**
	 * Whether this element is empty.
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
		parent::__construct($output_indent);

		if (!preg_match('@^(\.[^() =%#]+)?(#[^() =%]+)?(\%[^() =]+)?(\([^()]+\))? *(=?.+)?$@', $q->get_line(), $matches))
		{
			die('Parsen gescheitert: ' . htmlspecialchars($q->get_line()));
		}

		$tag = 'div';
		if (isset($matches[1]) && $matches[1]) // CSS classes
		{
			$class = htmlspecialchars(str_replace('.', ' ', substr($matches[1], 1))) . '"';
		}
		if (isset($matches[2]) && $matches[2]) // Identifier
		{
			$id = '"' . htmlspecialchars(substr($matches[2], 1)) . '"';
		}
		if (isset($matches[3]) && $matches[3]) // Tag
		{
			$tag = substr($matches[3], 1);
		}

		if (isset($matches[5]) && $matches[5]) // First child
		{
			$qq = new KhamelQueue(array($matches[5]));
			$this->children[] = new TextNode($qq->move_next(), $output_indent + 1);
		}

		if (isset($matches[4])) // Attributes
		{
			$moo = substr($matches[4], 1, -1); // Remove surrounding parentheses
			while (preg_match('@^([^ =]+)(=("[^"]*"|[^"][^ ]*))?([ ]+|$)@', $moo, $matches))
			{
				$foo = $matches[1];
				if (!$matches[2]) // Empty attribute
				{
					$attr[$foo] = $foo;
				}
				else if ($matches[3][0] == '"') // Attribute with value
				{
					$attr[$foo] = $matches[3];
				}
				else // Attribute with evaluation
				{
					$attr[$foo] = '"<?php echo htmlspecialchars(' . ltrim($matches[3]) . '); ?>"';
				}
				$moo = substr($moo, strlen($matches[0])); // Move to next attribute
			}
		}

		if (isset($id)) // Overwrite identifier
		{
			$attr['id'] = $id;
			unset($id);
		}
		if (isset($class)) // Append CSS classes
		{
			$attr['class'] = isset($attr['class']) ? substr($attr['class'], 0, -1) . ' ' . $class : '"' . $class;
			unset($class);
		}

		$this->parse_children($q, $output_indent + 1, $q->get_indent() + 1);
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
		preg_match('@^[^ ]+@', $tag, $matches);
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
class PhpNode extends AbstractNode
{
	/**
	 * Konstruktor; erstellt einen neuen PHP-Knoten, indem er ihn aus dem Array parst.
	 * @param KhamelQueue $q
	 * @param int $output_indent
	 */
	public function __construct(KhamelQueue $q, $output_indent)
	{
		parent::__construct($output_indent);

		$line = $q->get_line();

/*		switch (substr($line, 0, strpos($zeile, array(' ', '('))))
		{
			case 'if':
			case 'else':
			case 'while':
			case 'for':
			case 'foreach':
			case 'elseif':
			case 'switch':
				$zeile .= ':'; // FIXME: Only add it if it’s not there.
				break;

			default:
				$zeile .= ';'; // FIXME: Same thing.
		}*/

		$this->parse_children($q, $output_indent, $q->get_indent() + 1);

		$zeile = ltrim(substr($zeile, 1));

		if ($zeile[0] == '#' || substr($zeile, 0, 2) == '//')
		{
			// Don’t output anything.
			$this->children = array();
			$this->code = '';
			return;
		}

		$this->code = "<?php $zeile ?>";
	}

	/**
	 * The output.
	 * @var string
	 */
	private $code;

	public function __toString()
	{
		return $this->code;
	}
}

/**
 * A comment. Can do conditional ones too,
 */
class CommentNode extends AbstractNode
{
	/**
	 * Constructor; 
	 * @param KhamelQueue $q
	 * @param int $output_indent
	 * @param int $input_indent
	 */
	public function __construct(KhamelQueue $q, $output_indent, $input_indent)
	{
		parent::__construct($output_indent);

		$this->parse_children($q, $output_indent, $q->get_indent() + 1);
	}

	public function __toString()
	{
		return '<!-- -->';
	}
}

/**
 * 
 */
class DoctypeNode extends AbstractNode
{
	/**
	 * Constructor; creates a new doctype for XHTML 1.1.
	 * @param KhamelQueue $q
	 * @param int $output_indent
	 */
	public function __construct(KhamelQueue $q, $output_indent)
	{
		parent::__construct($output_indent);

		$q->move_next();
	}
	public function __toString()
	{
		return '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">';
	}
}

/**
 * A node with input indent 0.
 */
class RootNode extends AbstractNode
{
	/**
	 * Creates a new node that will only output its children.
	 * @param KhamelQueue $q
	 * @param int $output_indent
	 */
	public function __construct(KhamelQueue $q, $output_indent)
	{
		parent::__construct($output_indent);

		$this->parse_children($q, $output_indent, 0);
	}

	public function __toString()
	{
		return $this->stringify_children();
	}
}

/**
 * Khamel parses a subset of the HAML commands and caches the result.
 * Ab PHP 5.
 */
class Khamel extends RootNode
{
	public static $template_path, $cache_path;

	/**
	 * Input filename.
	 * @var string
	 */
	protected $file;

	/**
	 * Ob solche HTML-Elemente inhaltsleer sind.
	 * @param string $tag
	 * @return bool
	 */
	public static function is_empty_element($tag)
	{
		static $empty_elements = array('area', 'base', 'br', 'col', 'hr', 'img', 'input', 'link', 'meta', 'param');
		return in_array($tag, $empty_elements);
	}
	/**
	 * Ob solche HTML-Elemente inline sind.
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
	public static function create_helper($name)
	{
		switch ($name)
		{
			case 'cache':
			case 'include':
			case 'pre':
			case 'script':
			case 'style':
				$classname = ucfirst($name) . 'Helper';
				return new $classname();
		}

		if (isset(self::$custom[$name]))
		{
			return self::$custom[$name];
		}

		// TODO: Process errors
	}


	/**
	 * Constructor; creates a new Khamel object by parsing a file.
	 * @param string $filename
	 */
	public function __construct($filename)
	{
		$this->file = self::$template_path . "/$filename.haml";
		$arr = file($this->file, FILE_IGNORE_NEW_LINES);
		$queue = new KhamelQueue($arr);
		parent::__construct($queue, -1);
	}

	public function __toString()
	{
		$filename = self::$cache_path . '/' . substr(md5($this->file . filemtime($this->file)), 0, 10) . '.php';
		if (!file_put_contents($filename, parent::__toString()))
		{
			return 'Buffering failed.';
		}

		// TODO: Import variables
		ob_start(null);
		include $filename;
		$ausgabe = ob_get_contents();
		ob_end_clean();

		return $ausgabe;
	}
}

?>