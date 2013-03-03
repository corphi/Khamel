<?php

namespace Khamel;



/**
 * Try to load a Khamel class.
 * @param string $name
 * @return boolean
 */
function autoloader($name)
{
	if (substr($name, 0, 7) !== 'Khamel\\') {
		return false;
	}

	$name = __DIR__ . str_replace('\\', DIRECTORY_SEPARATOR, substr($name, 6)) . '.php';
	return is_file($name) && include($name);
}

spl_autoload_register('Khamel\\autoloader');
