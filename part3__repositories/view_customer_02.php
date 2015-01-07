<?php

require_once __DIR__ . '/vendor/autoload.php';

$client = new Predis\Client('tcp://127.0.0.1:6379');
echo $client->get($argv[1]);