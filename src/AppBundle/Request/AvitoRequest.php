<?php

namespace AppBundle\Request;

use AppBundle\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Monolog\Logger;
use Symfony\Component\Yaml\Yaml;

class AvitoRequest
{
    /**
     * @var Client
     */
    private $client;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var string
     */
    private $url;

    /**
     * @var array
     */
    private $headers;

    /**
     * @var array
     */
    private $proxies;

    /**
     * @var int
     */
    private $proxy_index;

    /**
     * AvitoRequest constructor.
     * @param Client $client
     * @param string $proxy_list_path
     */
    public function __construct(Client $client, Logger $logger, string $proxy_list_path)
    {
        $this->client = $client;
        $this->logger = $logger;
        $this->url    = 'https://www.avito.ru/';

        $this->headers = [
            'accept'                    => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
            'accept-encoding'           => 'gzip, deflate, sdch, br',
            'accept-language'           => 'ru-RU,ru;q=0.8,en-US;q=0.6,en;q=0.4',
            'cache-control'             => 'max-age=0',
            'referer'                   => 'https://www.avito.ru/',
            'upgrade-insecure-requests' => '1',
            'user-agent'                => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_12_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/55.0.2883.95 Safari/537.36',
            'cookie'                    => 'u=26isga9z.1gac0ni.fjwbjucs2z;'
        ];

        $this->proxies = Yaml::parse(file_get_contents($proxy_list_path));
    }

    /**
     * @param string $url
     * @return Response
     */
    public function getList(string $url, int $page): Response
    {
        $query = [
            'user' => 1,
            'view' => 'list',
            's'    => 104, //order by date
            'page' => $page
        ];

        return $this->proxyRequest(new Request('GET', $this->url . $url, $this->headers), ['query' => $query]);
    }

    /**
     * @param string $url
     * @return Response
     */
    public function getRecord(string $url): Response
    {
        return $this->proxyRequest(new Request('GET', $this->url . $url, $this->headers));
    }

    /**
     * @param Request $request
     * @param array   $data
     * @param int     $try
     * @return mixed|\Psr\Http\Message\ResponseInterface
     * @throws RequestException
     * @throws \Exception
     */
    private function proxyRequest(Request $request, array $data = [], $try = 0)
    {
        $proxy = $this->currentProxy();

        if (!$proxy) {
            $this->resetProxy();
            throw new RequestException('Invalid proxy');
        }

        try {

            $data['proxy'] = $proxy;

            $this->logger->debug('proxy request', ['proxy' => $proxy, 'uri' => $request->getUri()]);

            $response = $this->client->send($request, $data);

        } catch (\Exception $e) {

            if ($try > 5) {
                throw $e;
            }

            $this->logger->info('proxy request exception', ['proxy' => $proxy, 'uri' => $request->getUri(), 'exception' => $e->getMessage()]);

            $this->nextProxy();

            $response = $this->proxyRequest($request, $data, ++$try);
        }

        $content = $response->getBody()->getContents();

        if (false !== mb_strrpos(mb_strtolower($content), 'доступ временно ограничен')) {
            $this->nextProxy();

            $this->logger->info('proxy request access denied', ['proxy' => $proxy, 'uri' => $request->getUri()]);

            $response = $this->proxyRequest($request, $data);
        }

        $response->getBody()->rewind();

        return $response;
    }

    /**
     * @return int
     */
    private function getProxyIndex()
    {
        if (file_exists('proxy_index')) {
            return (int)file_get_contents('proxy_index');
        }

        $this->setProxyIndex(0);

        return 0;
    }

    /**
     * @param int $index
     * @return bool
     */
    private function setProxyIndex(int $index)
    {
        file_put_contents('proxy_index', $index);

        return true;
    }

    /**
     * @return string
     */
    private function currentProxy()
    {
        if (null === $this->proxy_index) {
            $this->proxy_index = $this->getProxyIndex();
        }

        return $this->proxies[$this->proxy_index];
    }

    /**
     * @return bool
     */
    private function nextProxy()
    {
        $this->proxy_index++;
        $this->setProxyIndex($this->proxy_index);

        return true;
    }

    /**
     * @return bool
     */
    private function resetProxy()
    {
        $this->proxy_index = 0;
        $this->setProxyIndex($this->proxy_index);

        return true;
    }
}