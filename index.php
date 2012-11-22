<?php

require_once 'khamel.php';



echo '<?xml version="1.0" encoding="UTF-8"?>', Khamel::NEWLINE;

Khamel::$template_path = '.';
Khamel::$cache_path = ini_get('session.save_path');

$khamel = new Khamel('moo');
echo $khamel;
