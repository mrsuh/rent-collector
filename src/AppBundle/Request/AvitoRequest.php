<?php

namespace AppBundle\Request;

use AppBundle\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Monolog\Logger;

class AvitoRequest
{
    const AGENT = 'user_agent';

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
    private $agents;

    /**
     * @var int
     */
    private $agent_index;

    private $count;

    /**
     * AvitoRequest constructor.
     * @param Client $client
     * @param Logger $logger
     */
    public function __construct(Client $client, Logger $logger, string $path_user_agents)
    {
        $this->client = $client;
        $this->logger = $logger;
        $this->url    = 'https://www.avito.ru/';

        $this->agents      = explode(PHP_EOL, file_get_contents($path_user_agents));
        $this->agent_index = $this->getAgentIndex();

        $this->count = 0;
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
            'i'    => 1,//photo only
            'page' => $page
        ];

        return $this->queryClean('GET', $this->url . $url, $query);
    }

    /**
     * @param string $url
     * @return Response
     */
    public function getRecord(string $url): Response
    {
        return $this->queryClean('GET', $this->url . $url);
    }

    /**
     * @param Request $request
     * @param array   $data
     * @return mixed|\Psr\Http\Message\ResponseInterface
     * @throws RequestException
     */
    private function accessRequest(Request $request, array $data = [])
    {
        $agent = $this->nextAgent();

        $data['headers'] = [
            'User-Agent' => $agent
        ];

        $this->logger->debug('request', ['count' => $this->count, 'agent' => $agent, 'uri' => $request->getUri()->getPath()]);
        $this->count++;

        $response = $this->client->send($request, $data);

        $content = $response->getBody()->getContents();

        if (false !== mb_strrpos(mb_strtolower($content), 'доступ временно ограничен')) {

            throw new RequestException('Access denied');
        }

        $response->getBody()->rewind();

        return $response;
    }

    /**
     * @param string $method
     * @param string $path
     * @param array  $params
     * @return Response
     * @throws RequestException
     */
    public function queryClean(string $method, string $path, array $params = [])
    {
        echo 'MEMORY ' . (memory_get_usage() / 1024 / 1024) . 'MB' . PHP_EOL;

        $url = $path;
        $ch  = curl_init();
        switch ($method) {
            case 'POST':
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
                break;
            case 'DELETE':
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
                break;
            case 'PUT':
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
                break;
            case 'GET':
                curl_setopt($ch, CURLOPT_URL, $url . '?' . http_build_query($params));
                break;
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);

        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_FAILONERROR, true);

        $agent = $this->nextAgent();

        $headers = [
            'User-Agent: ' . $agent,
            'Cookie: ' . 'f=5.cc913c231fb04ced4b5abdd419952845a68643d4d8df96e9a68643d4d8df96e9a68643d4d8df96e9ba029cd346349f36c1e8912fd5a48d02c1e8912fd5a48d02c1e8912fd5a48d02c1e8912fd5a48d02c1e8912fd5a48d02c1e8912fd5a48d0246b8ae4e81acb9fa143114829cf33ca746b8ae4e81acb9fae2415097439d4047d50b96489ab264edaf305aadb1df8ceba09db4af14a5e9adbc8794f0f6ce82fe3de19da9ed218fe2e2415097439d4047143114829cf33ca746b8ae4e81acb9fa3de19da9ed218fe2fb0fb526bb39450a87829363e2d856a2b5b87f59517a23f23de19da9ed218fe23de19da9ed218fe2c772035eab81f5e187829363e2d856a2143114829cf33ca7aacc085410276950b362cba29273a079b629ecbcdeab3d430389ebfa631afd29d7d1f37e9cbae09d1f522017d20e30eb662cd990eb56f6055335ad04dacb1d9b38f0f5e6e0d2832ed5b80124e38d26c7f65dca5b7a8f20315dcded8022a3c9fccbf1a5019b899285164b09365f5308e7618389eb0521524862d16ec2199c9f402da10fb74cac1eab2da10fb74cac1eab73f45b5206a1053e0e8ba0fc9b38f7fb5be628aeda7a6ae8;'
        ];

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $this->logger->debug('request', ['count' => $this->count, 'agent' => $agent, 'path' => $path]);
        $this->count++;

        $server_output = curl_exec($ch);

        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (302 === $status) {
            throw new RequestException('Access denied');
        }

        if (200 !== $status) {
            throw new RequestException('Invalid response');
        }

        curl_close($ch);

        return new Response(200, [], $server_output);
    }

    /**
     * @return int
     */
    private function getAgentIndex()
    {
        if (file_exists(self::AGENT)) {
            return (int)file_get_contents(self::AGENT);
        }

        $this->setAgentIndex(0);

        return 0;
    }

    /**
     * @param int $index
     * @return bool
     */
    private function setAgentIndex(int $index)
    {
        file_put_contents(self::AGENT, $index);

        return true;
    }

    /**
     * @return mixed
     */
    private function nextAgent()
    {
        $this->agent_index++;

        if (!array_key_exists($this->agent_index, $this->agents)) {
            $this->agent_index = 0;
        }

        $this->setAgentIndex($this->agent_index);

        return $this->agents[$this->agent_index];
    }
}