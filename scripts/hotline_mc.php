<?php

require dirname(__FILE__) . '/../vendor/autoload.php';

use PriceParser\HotlineMcParser;
use PriceParser\Spider;

$parser = new HotlineMcParser();
$spider = new Spider($parser, dirname(__FILE__) . '/../data/mc/');
$spider->run();
