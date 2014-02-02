<?php

require dirname(__FILE__) . '/../vendor/autoload.php';

use PriceParser\HotlineHddParser;
use PriceParser\Spider;

$parser = new HotlineHddParser();
$spider = new Spider($parser, dirname(__FILE__) . '/../data/hdd/');
$spider->run();
