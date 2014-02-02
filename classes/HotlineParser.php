<?php
namespace PriceParser;

abstract class HotlineParser extends PageParser
{
    const HOST_URL = 'http://hotline.ua';
    
    private $path;

    public function __construct($path)
    {
        $this->path = $path;
    }
    
    public function getParseUrl()
    {
        return self::HOST_URL . $this->path;
    }
}