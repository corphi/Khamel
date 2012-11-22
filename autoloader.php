<?php

namespace Khamel;



function autoloader($name)
{
	if (substr($name, 0, 7) !== 'Khamel\\') {
		return false;
	}
	return include __DIR__ . '/' . str_replace('\\', '/', substr($name, 7)) . '.php';
}
spl_autoload_register('Khamel\\autoloader');
