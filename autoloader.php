<?php

namespace Khamel;



/**
 * Try to load a Khamel class.
 * @param string $name
 */
function autoloader($name)
{
	if (substr($name, 0, 7) !== 'Khamel\\') {
		return false;
	}
	return include(__DIR__ . '/' . str_replace('\\', '/', substr($name, 7)) . '.php');
}

spl_autoload_register('Khamel\\autoloader');
