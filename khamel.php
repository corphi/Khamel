<?php

/**
 * Eine Warteschlange für mittels Khamel zu verarbeitende Zeilen.
 */
class KhamelQueue
{
	/**
	 * Die Zeilen.
	 * @var array
	 */
	protected $arr;
	/**
	 * Konstruktor; erstellt eine neue Warteschlange aus dem Array.
	 * @param array $arr
	 */
	public function __construct(array $arr)
	{
		$this->arr = $arr;
	}

	/**
	 * Ob es eine weitere Zeile gibt.
	 * @return bool
	 */
	public function has_next()
	{
		return count($this->arr) > 0;
	}

	/**
	 * Die Zeile.
	 * @var string
	 */
	protected $line;
	/**
	 * Liefert die aktuelle Zeile zurück. Kann so tun, als wäre ein bestimmter Einzug vorgegeben.
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
	 * Der Einzug.
	 * @var int
	 */
	protected $indent;
	/**
	 * Liefert den Einzug der aktuellen Zeile zurück.
	 * @return int
	 */
	public function get_indent()
	{
		return $this->indent;
	}

	/**
	 * Arbeitet die nächste Zeile ab.
	 * Vorher überprüfen, ob noch eine weitere Zeile vorhanden ist!
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
 * 
 */
abstract class AbstractNode
{
	/**
	 * Ob dieser Knoten inline dargestellt wird.
	 * @var bool
	 */
	protected $is_inline;

	/**
	 * Einzug der Ausgabe.
	 * @var int
	 */
	protected $output_indent;
	/**
	 * Konstruktor; speichert den Ausgabeeinzug (zur Verwendung in __toString).
	 * @param int $output_indent
	 */
	protected function __construct($output_indent)
	{
		$this->output_indent = $output_indent;
	}

	/**
	 * Enthält alle Kindknoten.
	 * @var array
	 */
	protected $children;

	/**
	 * Parst alle Unterknoten und fügt sie hinzu.
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
	 * Ob sämtliche Kindelemente inline sind.
	 * @return bool
	 */
	protected function has_only_inline_children()
	{
		$kinder_inline = true;
		if (isset($this->children))
		{
			foreach ($this->children as $kind)
			{
				$kinder_inline = $kind->is_inline;
				if (!$kinder_inline)
				{
					break;
				}
			}
		}
		return $kinder_inline;
	}

	/**
	 * Verpackt die Kindelemente in einen String.
	 * @return string
	 */
	protected function stringify_children()
	{
		$ausgabe = '';

		if (!$this->children)
		{
			return $ausgabe;
		}

		$voriger_inline = $this->has_only_inline_children(); // Am Anfang einen Zeilenumbruch einfügen lassen, wenn es auch Block-Kinder gibt.
		$einzug = Khamel::spaces($this->output_indent + 1);
		$kind = reset($this->children);
		do
		{
			if ($kind->is_inline && $voriger_inline) // Inline-Elemente direkt aneinanderhängen
			{
				$ausgabe .= $kind;
			}
			else // Vor, nach und zwischen Blöcken einen Zeilenumbruch einfügen
			{
				$ausgabe .= Khamel::NEWLINE . $einzug . $kind;
			}
			$voriger_inline = $kind->is_inline;
		}
		while ($kind = next($this->children));

		return $ausgabe;
	}

	public abstract function __toString();
}

/**
 * Textknoten. TODO: Macht auch Auswertungen.
 */
class TextNode extends AbstractNode
{
	/**
	 * Auszugebender Text.
	 * @var string
	 */
	protected $string;

	/**
	 * Konstruktor; parst einen Textknoten aus der Warteschlange.
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
		'<?php echo htmlspecialchars(' . ltrim(substr($zeile, 1)) . '); ?>';
	}
}

/**
 * Bildet einen XHTML-Knoten ab.
 */
class HtmlNode extends AbstractNode
{
	/**
	 * Ob dieses HTML-Element inhaltsleer ist.
	 * @var bool
	 */
	protected $is_empty;

	/**
	 * Konstruktor; parst einen neuen XHTML-Knoten.
	 * @param KhamelQueue $q
	 * @param int $output_indent
	 */
	public function __construct(KhamelQueue $q, $output_indent)
	{
		parent::__construct($output_indent);

		if (!preg_match('@^(\.[^() =%#]+)?(#[^() =%]+)?(\%[^() =]+)?(\([^()]+\))? *(=?.+)?$@', $q->get_line(), $treffer))
		{
			die('Parsen gescheitert: ' . htmlspecialchars($q->get_line()));
		}

		$tag = 'div';
		if (isset($treffer[1]) && $treffer[1]) // CSS-Klassen
		{
			$class = htmlspecialchars(str_replace('.', ' ', substr($treffer[1], 1))) . '"';
		}
		if (isset($treffer[2]) && $treffer[2]) // Bezeichner
		{
			$id = '"' . htmlspecialchars(substr($treffer[2], 1)) . '"';
		}
		if (isset($treffer[3]) && $treffer[3]) // Tag
		{
			$tag = substr($treffer[3], 1);
		}

		if (isset($treffer[5]) && $treffer[5]) // Erstgeborenes
		{
			$qq = new KhamelQueue(array($treffer[5]));
			$this->children[] = new TextNode($qq->move_next(), $output_indent + 1);
		}

		if (isset($treffer[4])) // Attribute
		{
			$muh = substr($treffer[4], 1, -1); // Umgebende Klammern entfernen
			while (preg_match('@^([^ =]+)(=("[^"]*"|[^"][^ ]*))?([ ]+|$)@', $muh, $treffer))
			{
				$ding = $treffer[1];
				if (!$treffer[2]) // Leeres Attribut
				{
					$attr[$ding] = $ding;
				}
				else if ($treffer[3][0] == '"') // Attribut mit Wert
				{
					$attr[$ding] = $treffer[3];
				}
				else // Attribut mit Auswertung
				{
					$attr[$ding] = '"<?php echo htmlspecialchars(' . ltrim($treffer[3]) . '); ?>"';
				}
				$muh = substr($muh, strlen($treffer[0])); // Geparstes Attribut abschneiden
			}
		}

		if (isset($id)) // Bezeichner überschreiben
		{
			$attr['id'] = $id;
			unset($id);
		}
		if (isset($class)) // CSS-Klassen ergänzen
		{
			$attr['class'] = isset($attr['class']) ? substr($attr['class'], 0, -1) . ' ' . $class : '"' . $class;
			unset($class);
		}

		$this->parse_children($q, $output_indent + 1, $q->get_indent() + 1);
		$this->is_inline = Khamel::is_inline_element($tag);

		// Attribute zusammensetzen
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
	 * Das öffnende Tag
	 * @var string
	 */
	private $tag;

	public function __toString()
	{
		$tag = $this->tag;
		unset($this->tag);

		$ausgabe = "<$tag";
		preg_match('@^[^ ]+@', $tag, $treffer);
		$tag = $treffer[0];

		if (Khamel::is_empty_element($tag))
		{
			return "$ausgabe />";
		}
		$ausgabe .= '>' . $this->stringify_children();

		if ($this->has_only_inline_children())
		{
			return "$ausgabe</$tag>";
		}
		return $ausgabe . Khamel::NEWLINE . Khamel::spaces($this->output_indent) . "</$tag>";
	}
}

/**
 * Stellt einen PHP-Knoten dar.
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

		$zeile = $q->get_line();

/*		switch (substr($zeile, 0, strpos($zeile, array(' ', '('))))
		{
			case 'if':
			case 'else':
			case 'while':
			case 'for':
			case 'foreach':
			case 'elseif':
			case 'switch':
				$zeile .= ':'; // FIXME: Nur anhängen, wenn es noch nicht da ist.
				break;

			default:
				$zeile .= ';'; // FIXME: Ebenso.
		}*/

		$this->parse_children($q, $output_indent, $q->get_indent() + 1);

		$zeile = substr($zeile, 1);

		if ($zeile[0] == '#' || substr($zeile, 0, 2) == '//')
		{
			// Nichts ausgeben
			$this->children = array();
			$this->code = '';
			return;
		}

		$this->code = "<?php $zeile ?>";
	}

	/**
	 * Die Ausgabe.
	 * @var string
	 */
	private $code;

	public function __toString()
	{
		return $this->code;
	}
}

/**
 * Ein Kommentar, kann auch Conditional Comments.
 */
class CommentNode extends AbstractNode
{
	/**
	 * Konstruktor; 
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
	 * Konstruktor; erstellt einen neuen Doctype für XHTML 1.1.
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
 * Ein Knoten mit dem Eingabe-Einzug 0.
 */
class RootNode extends AbstractNode
{
	/**
	 * Erstellt einen neuen Knoten ohne eigene Ausgabe. Er gibt nur seine Kindknoten aus.
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
 * Khamel parst eine Untermenge der HAML-Befehle und cacht das Ergebnis.
 * Ab PHP 5.
 */
class Khamel extends RootNode
{
	public static $template_path, $cache_path;

	/**
	 * Der Dateiname des geparsten Templates.
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
	 * Ein Zeilenumbruch.
	 * @var string
	 */
	const NEWLINE = '
';

	/**
	 * Generiert entsprechend viele Leerzeichen hintereinander.
	 * @param int $anzahl
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
	 * 
	 * @param string $name
	 */
	public static function get_helper($name)
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

		// TODO: Fehlerfall behandeln
	}


	/**
	 * Erstellt ein neues Khamel-Objekt, in dem die übergebene Datei geparst wird.
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
			return 'Konnte nicht puffern.';
		}

		ob_start(null);
		include $filename;
		$ausgabe = ob_get_contents();
		ob_end_clean();

		return $ausgabe;
	}
}

?>