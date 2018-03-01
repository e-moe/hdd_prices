<?php
namespace PriceParser;

use Guzzle\Http\Client;
use Guzzle\Http\Message\Response;
use Guzzle\Http\Request;
use Guzzle\Common\Exception\MultiTransferException;

class Spider
{
    /**
     * @var Client Guzzle HTTP Client
     */
    protected $client;
    
    /**
     *
     * @var HotlineParser;
     */
    protected $parser;
    
    /**
     * @var string
     */
    protected $dataPath;

    public function __construct(HotlineParser $parser, $dataPath)
    {
        $this->dataPath = $dataPath;
        $this->parser = $parser;
        $this->client = new Client($this->parser->getParseUrl());
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
        $data = new \stdClass();
        if ($this->parser->parse($html)) {
            $data->page = $this->parser->getPage();
            $data->pages = $this->parser->getPages();
            $data->items = $this->parser->getItems();
        }
        return $data;
    }
    
    /**
     * Extract data from response
     * 
     * @return stdClass Data
     */
    protected function extractData(Response $response)
    {
        $html = $response->getBody(true);
        return $this->parsePage($html);
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
            $data = $this->extractData($response);
            if (isset($data->items)) {
                $hdd = array_merge($hdd, $data->items);
            }
        }
        return $hdd;
    }
    
    /**
     * Save data into file
     * 
     * @param mixed $data
     */
    protected function saveData($data)
    {
        $fileName = $this->dataPath . time() . '.json';
        $latestName = $this->dataPath . 'latest.json';
        file_put_contents(
            $fileName,
            json_encode(
                $data,
                JSON_UNESCAPED_UNICODE
            )
        );
        if (file_exists($latestName)) {
            unlink($latestName);
        }
        symlink($fileName, $latestName);
    }

    /**
     * Download all pages, parse and save data
     */
    public function run()
    {
        $pageOne = $this->loadPage();
        if (!$pageOne->isSuccessful()) {
            echo 'Can\'t load first page';
            return;
        }
        $data = $this->extractData($pageOne);
        $hdd = $data->items;

        $requests = $this->getPageRequest(range(1, $data->pages - 1));

        try {
            $responses = $this->client->send($requests);
            $hdd = array_merge($hdd, $this->parseResponses($responses));
            usort($hdd, function($a, $b) {
                return strcmp($a->title, $b->title);
            });
            $this->saveData($hdd);
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
