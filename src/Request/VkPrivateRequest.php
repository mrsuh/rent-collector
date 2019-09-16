<?php

namespace App\Request;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Mrsuh\Service\AuthService;
use Psr\Http\Message\ResponseInterface;

class VkPrivateRequest
{
    private $auth;
    private $client;
    private $url;
    private $version;

    public function __construct(Client $client, string $vkUsername, string $vkPassword, string $vkAppId)
    {
        $this->auth = new AuthService([
            'username' => $vkUsername,
            'password' => $vkPassword,
            'app_id'   => $vkAppId,
            'scope'    => ['wall', 'photos']
        ]);

        $this->client  = $client;
        $this->url     = 'https://api.vk.com/method';
        $this->version = 5.64;

        $this->auth->auth();
    }

    public function groupsSearch(string $query): ?ResponseInterface
    {
        $form_params = [
            'v'            => $this->version,
            'access_token' => $this->auth->getToken(),
            'q'            => $query,
            'sort'         => 0,
            'count'        => 500
        ];

        return $this->authRequest(new Request('POST', $this->url . '/groups.search'), ['form_params' => $form_params]);
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

            $this->auth->auth();

            $try++;

            return $this->authRequest($request, $data, $try);
        }
    }

    public function boardGetTopics(int $group_id): ?ResponseInterface
    {
        $form_params = [
            'v'            => $this->version,
            'access_token' => $this->auth->getToken(),
            'group_id'     => $group_id
        ];

        return $this->authRequest(new Request('POST', $this->url . '/board.getTopics'), ['form_params' => $form_params]);
    }

    public function databaseGetCities(string $query): Response
    {
        $form_params = [
            'v'            => $this->version,
            'access_token' => $this->auth->getToken(),
            'group_id'     => 5,
            'country_id'   => 1,
            'q'            => $query
        ];

        return $this->authRequest(new Request('POST', $this->url . '/database.getCities'), ['form_params' => $form_params]);
    }

    public function groupsGetById(array $group_ids): ?ResponseInterface
    {
        $form_params = [
            'v'            => $this->version,
            'access_token' => $this->auth->getToken(),
            'group_ids'    => implode(',', $group_ids)
        ];

        return $this->authRequest(new Request('POST', $this->url . '/groups.getById'), ['form_params' => $form_params]);
    }
}