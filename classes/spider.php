<?php
require dirname(__FILE__) . '/../vendor/autoload.php';

use Guzzle\Http\Client;
use Guzzle\Http\Response;
use Guzzle\Http\Request;
use Guzzle\Common\Exception\MultiTransferException;

class Spider
{
    /**
     * @var Client Guzzle HTTP Client
     */
    protected $client;
    
    /**
     * @var string Base URL
     */
    protected $baseUrl;
    
    /**
     * @var string Path for data files
     */
    protected $path;


    public function __construct($baseUrl, $path)
    {
        $this->baseUrl = $baseUrl;
        $this->client = new Client($this->baseUrl);
        $this->path = $path;
    }

    /**
    * Get request for one page load
    * 
    * @param int $p
    * @return Request
    */
    private function getSinglePageRequest($p)
    {
        return $this->client->get(
            '',
            [],
            [
                'query' => [ 'p' => $p ],
            ]
        );
    }
    
    /**
    * Get request for page load
    * 
    * @param int|array $page
    * @return Request
    */
    protected function getPageRequest($page)
    {
        if (is_int($page)) {
            return $this->getSinglePageRequest($page);
        }
        if (is_array($page)) {
            $requests = [];
            foreach ($page as $p) {
                $requests[] = $this->getSinglePageRequest($p);
            }
            return $requests;
        }
        return null;
    }

    /**
     * Load page source
     * 
     * @param int $p
     * @return Response
     */
    protected function loadPage($p = 0)
    {
        return $this->getPageRequest($p)->send();
    }

    /**
     * 
     * @param string $html
     * @return stdClass Parsed page data
     */
    protected function parsePage($html)
    {
        $data = new stdClass();
        $dom = new DomDocument();
        $dom->recover = true;
        $dom->strictErrorChecking = false;
        @$dom->loadHTML($html);
        $xpath = new DOMXpath($dom);

        $numPages = $xpath->query('//div[@class="pager top"]');
        // 'Страница 1 из 31'
        $text = $numPages->item(0)->nodeValue;
        if (preg_match('/(\d+)\D+(\d+)/u', $text, $matches)) {
            $data->page = intval($matches[1]);
            $data->pages = intval($matches[2]);
        }

        $liNodes = $xpath->query('//ul[contains(@class, "catalog")]/li');
        if ($liNodes->length) {
            foreach ($liNodes as $li) {
                $techCharNodes = $xpath->query('.//p[@class="tech-char"]', $li);
                $techCharText = $techCharNodes->item(0)->nodeValue;
                $techCharArr = preg_split('/•/u', $techCharText);
                $hdd = new stdClass();
                $hdd->type = trim($techCharArr[0]);
                $hdd->capacity = intval($techCharArr[1]);
                $hdd->interface = trim($techCharArr[2]);
                $hdd->formFactor = trim($techCharArr[3]);

                $titleUrlNodes = $xpath->query('.//div[@class="title-box"]/h3/a', $li);
                $hdd->title = trim($titleUrlNodes->item(0)->nodeValue);
                $hdd->url = $this->baseUrl . $titleUrlNodes->item(0)->attributes->getNamedItem("href")->nodeValue;

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
                        $price = new stdClass();
                        $k = new stdClass();
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
                $data->hdd[] = $hdd;
            }
        }
        return $data;
    }
    
    /**
     * Parse responses
     * 
     * @param Response[] $responses
     * @return array
     */
    protected function parseResponses(array $responses)
    {
        $hdd = [];
        foreach ($responses as $response) {
            $html = $response->getBody();
            $data = $this->parsePage($html);
            $hdd = array_merge($hdd, $data->hdd);
        }
        return $hdd;
    }

    public function run()
    {
        $pageOne = $this->loadPage();
        if (!$pageOne->isSuccessful()) {
            echo 'Can\'t load first page';
            return;
        }
        $html = $pageOne->getBody();
        $data = $this->parsePage($html);
        $hdd = $data->hdd;

        $requests = $this->getPageRequest(range(1, $data->pages - 1));

        try {
            $responses = $this->client->send($requests);
            $hdd = array_merge($hdd, $this->parseResponses($responses));
            usort($hdd, function($a, $b) {
                return strcmp($a->title, $b->title);
            });
            $fileName = $this->path . time() . '.json';
            $latestName = $this->path . 'latest.json';
            file_put_contents(
                $fileName,
                json_encode(
                    $hdd,
                    JSON_UNESCAPED_UNICODE
                )
            );
            if (file_exists($latestName)) {
                unlink($latestName);
            }
            symlink($fileName, $latestName);
            echo 'Done', PHP_EOL;
        } catch (MultiTransferException $e) {

            echo "The following exceptions were encountered:\n";
            foreach ($e as $exception) {
                echo $exception->getMessage() . "\n";
            }

            echo "The following requests failed:\n";
            foreach ($e->getFailedRequests() as $request) {
                echo $request . "\n\n";
            }

            echo "The following requests succeeded:\n";
            foreach ($e->getSuccessfulRequests() as $request) {
                echo $request . "\n\n";
            }
        }
    }
}
