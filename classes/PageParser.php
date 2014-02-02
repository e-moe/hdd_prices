<?php
namespace PriceParser;

abstract class PageParser
{
    protected $page = 0;
    protected $pages = 0;
    protected $items = [];
    protected $html = '';

    public function getPage()
    {
        return $this->page;
    }
    
    public function getPages()
    {
        return $this->pages;
    }
    
    public function getItems()
    {
        return $this->items;
    }

    public function getHtml()
    {
        return $this->html;
    }
    
    protected function reset()
    {
        $this->html = '';
        $this->items = [];
        $this->page = 0;
        $this->pages = 0;
    }

    public function parse($html)
    {
        $this->reset();
        $this->html = $html;
    }
}