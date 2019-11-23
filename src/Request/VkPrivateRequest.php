<?php

namespace App\Request;

use GuzzleHttp\Psr7\Request;
use Mrsuh\Service\AuthService;
use Psr\Http\Message\ResponseInterface;

class VkPrivateRequest
{
    private $auth;
    private $client;
    private $url;
    private $version;
    private $token;
    private $tokenStorageFilePath;

    public function __construct(
        Client $client,
        AuthService $auth,
        string $vkAppTokenStorageFilePath
    ) {
        $this->auth                 = $auth;
        $this->client               = $client;
        $this->url                  = 'https://api.vk.com/method';
        $this->version              = 5.64;
        $this->tokenStorageFilePath = $vkAppTokenStorageFilePath;
        $this->initToken();
    }

    private function initToken(bool $forceReloadToken = false): void
    {
        $this->token = file_exists($this->tokenStorageFilePath) ? file_get_contents($this->tokenStorageFilePath) : '';

        if ($this->token === '' || $forceReloadToken) {
            $this->auth->auth();
            $this->token = $this->auth->getToken();
            file_put_contents($this->tokenStorageFilePath, $this->token);
        }
    }

    public function groupsSearch(string $query, int $maxResults): ?ResponseInterface
    {
        $form_params = [
            'v'            => $this->version,
            'access_token' => $this->auth->getToken(),
            'q'            => $query,
            'sort'         => 0,
            'count'        => $maxResults,
        ];

        return $this->authRequest(new Request('POST', $this->url.'/groups.search'), ['form_params' => $form_params]);
    }

    private function authRequest(Request $request, array $data, $try = 0): ?ResponseInterface
    {
        try {

            $response = $this->client->send($request, $data);

            $content = (string)$response->getBody();

            $data = json_decode($content, true);

            if (!is_array($data)) {
                return $response;
            }

            if (array_key_exists('error', $data) && array_key_exists('error_code', $data['error'])) {
                if (5 === (int)$data['error']['error_code']) {
                    throw new \Exception('Auth exception');
                }
            }

            return $response;

        } catch (\Exception $e) {

            if ($try > 1) {
                throw $e;
            }

            $this->initToken(true);

            $try++;

            return $this->authRequest($request, $data, $try);
        }
    }

    public function boardGetTopics(int $group_id): ?ResponseInterface
    {
        $form_params = [
            'v'            => $this->version,
            'access_token' => $this->auth->getToken(),
            'group_id'     => $group_id,
        ];

        return $this->authRequest(new Request('POST', $this->url.'/board.getTopics'), ['form_params' => $form_params]);
    }

    public function groupsGetById(array $group_ids): ?ResponseInterface
    {
        $form_params = [
            'v'            => $this->version,
            'access_token' => $this->auth->getToken(),
            'group_ids'    => implode(',', $group_ids),
        ];

        return $this->authRequest(new Request('POST', $this->url.'/groups.getById'), ['form_params' => $form_params]);
    }
}