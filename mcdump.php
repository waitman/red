<?php

$mc = new Memcached();
$mc->addServer('127.0.0.1',11211);

$d = $mc->get('redconfig_config');
var_dump($d);

$d = $mc->get('redconfig_system');
var_dump($d);

$d = $mc->get('redconfig_feature');
var_dump($d);
 
echo '------------------------------------'."\n";

$d = $mc->get('redpconfig_1');
var_dump($d);

