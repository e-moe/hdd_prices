<?php
namespace PriceParser;

use Symfony\Component\DomCrawler\Crawler;

class HotlineMcParser extends HotlineParser
{
    const URL_PATH = '/computer/flash-karty/';

    public function __construct()
    {
        parent::__construct(self::URL_PATH);
    }

    /**
     * Parse input html string
     * 
     * @param string $html
     * @return boolean Result success
     */
    public function parse($html)
    {
        parent::parse($html);
        
        $crawler = new Crawler($html);

        try {
            $this->pages = intval($crawler->filter('.pagination .pages')->last()->text());
            $this->page = intval($crawler->filter('.pagination .pages.active')->text());

            $liNodes = $crawler->filter('ul.products-list .product-item')->reduce(function (Crawler $node, $i) {
                return !boolval($node->filter('.product-item-ad')->count());
            });
        } catch (\InvalidArgumentException $e) {
            return false;
        }

        $liNodes->each(function (Crawler $node) {
            try {
                $techCharText = $node->filter('div.info-description div.text')->first()->text();
                $techCharArr = preg_split('/•/u', $techCharText);
                $hdd = new \stdClass();
                $hdd->type = trim($techCharArr[0]);
                $hdd->capacity = intval($techCharArr[2]);
                $hdd->interface = trim($techCharArr[1]);
                $hdd->inTheBox = trim($techCharArr[3]);

                $titleUrl = $node->filter('div.info-description p.h4 a')->first();
                $hdd->title = trim($titleUrl->text());
                $hdd->url = self::HOST_URL . $titleUrl->attr('href');

                $hdd->price = (object)['avg' => null, 'min' => null, 'max' => null];
                $hdd->ratio = (object)['avg' => null, 'min' => null, 'max' => null];
                $hdd->price->avg = intval(preg_replace('/\s/u', '', $node->filter('div.item-price span.value')->first()->text()));
                $hdd->ratio->avg = round($hdd->price->avg / $hdd->capacity, 4);
                $priceRange = $node->filter('div.text-sm');
                if ($priceRange->count()) {
                    if (preg_match('/([\d|\s]+)\D+([\d|\s]+)/u', $priceRange->text(), $matches)) {
                        $priceArr = array_map(
                            function ($v) {
                                return preg_replace('/\s/u', '', $v);
                            },
                            $matches
                        );
                        $hdd->price->min = intval(preg_replace('/\s/u', '', $priceArr[1]));
                        $hdd->ratio->min = round($hdd->price->min / $hdd->capacity, 4);
                        $hdd->price->max = intval(preg_replace('/\s/u', '', $priceArr[2]));
                        $hdd->ratio->max = round($hdd->price->max / $hdd->capacity, 4);
                    }
                }
                $this->items[] = $hdd;
            } catch (\Exception $e) {
                echo "can't parse\n";
            }
        });
        return true;
    }

}