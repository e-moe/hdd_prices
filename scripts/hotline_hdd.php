<?php

require dirname(__FILE__) . '/../classes/spider.php';

$spider = new Spider('http://hotline.ua/computer/zhestkie-diski', dirname(__FILE__) . '/../data/');
$spider->run();