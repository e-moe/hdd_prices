<?php

require dirname(__FILE__) . '/../vendor/autoload.php';

use PriceParser\HotlineSsdParser;
use PriceParser\Spider;

$parser = new HotlineSsdParser();
$spider = new Spider($parser, dirname(__FILE__) . '/../data/ssd/');
$spider->run();
