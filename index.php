<?php

require_once 'autoloader.php';

use Khamel\Khamel;



echo '<?xml version="1.0" encoding="UTF-8"?>', Khamel::NEWLINE;

Khamel::$template_path = '.';
Khamel::$cache_path = ini_get('session.save_path');

$khamel = new Khamel('moo');
echo $khamel;
