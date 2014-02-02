<?php
namespace PriceParser;

class HotlineSsdParser extends HotlineParser
{
    const URL_PATH = '/computer/diski-ssd';

    public function __construct()
    {
        parent::__construct(self::URL_PATH);
    }

    public function parse($html)
    {
        parent::parse($html);
        
        $dom = new \DomDocument();
        $dom->recover = true;
        $dom->strictErrorChecking = false;
        @$dom->loadHTML($this->html);
        $xpath = new \DOMXpath($dom);

        $numPages = $xpath->query('//div[@class="pager top"]');
        // 'Страница 1 из 31'
        $text = $numPages->item(0)->nodeValue;
        if (!preg_match('/(\d+)\D+(\d+)/u', $text, $matches)) {
            return false;
        }
        $this->page = intval($matches[1]);
        $this->pages = intval($matches[2]);

        $liNodes = $xpath->query('//ul[contains(@class, "catalog")]/li');
        if (!$liNodes->length) {
            return false;
        }
        foreach ($liNodes as $li) {
            $techCharNodes = $xpath->query('.//p[@class="tech-char"]', $li);
            $techCharText = $techCharNodes->item(0)->nodeValue;
            $techCharArr = preg_split('/•/u', $techCharText);
            $hdd = new \stdClass();
            $hdd->type = trim($techCharArr[0]);
            $hdd->capacity = intval(preg_replace('/\D/u', '', $techCharArr[1]));
            $hdd->interface = trim($techCharArr[2]);
            $hdd->flashType = trim($techCharArr[3]);

            $titleUrlNodes = $xpath->query('.//div[@class="title-box"]/h3/a', $li);
            $hdd->title = trim($titleUrlNodes->item(0)->nodeValue);
            $hdd->url = self::HOST_URL . $titleUrlNodes->item(0)->attributes->getNamedItem("href")->nodeValue;

            $priceNodes = $xpath->query('.//div[@class="price"]/span', $li);
            if (!$priceNodes->length) {
                $hdd->price = (object)['avg' => null, 'min' => null, 'max' => null];
                $hdd->k = (object)['avg' => null, 'min' => null, 'max' => null];
            } else {
                if (preg_match('/([\d|\s]+)\D+(([\d|\s]+)\D+([\d|\s]+))?/u', $priceNodes->item(0)->nodeValue, $matches)) {
                    $priceArr = array_map(
                        function($v){
                            return preg_replace('/\s/u', '', $v);
                        },
                        $matches
                    );
                    $price = new \stdClass();
                    $k = new \stdClass();
                    $price->avg = intval($priceArr[1]);
                    $k->avg = round($price->avg / $hdd->capacity, 4);
                    if (count($priceArr) > 3) {
                        $price->min = intval($priceArr[3]);
                        $k->min = round($price->min / $hdd->capacity, 4);
                        $price->max = intval($priceArr[4]);
                        $k->max = round($price->max / $hdd->capacity, 4);
                    }
                    $hdd->price = $price;
                    $hdd->k = $k;
                }
            }
            $this->items[] = $hdd;
        }
        return true;
    }

}