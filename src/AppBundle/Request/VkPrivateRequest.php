<?php

namespace AppBundle\Request;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Mrsuh\Service\AuthService;
use Schema\Publish\User\User;

class VkPrivateRequest
{
    private $client;
    private $url;
    private $version;

    /**
     * VkPrivateRequest constructor.
     * @param Client $client
     * @param User   $user
     */
    public function __construct(Client $client, User $user)
    {
        $this->auth = new AuthService([
            'username' => $user->getUsername(),
            'password' => $user->getPassword(),
            'app_id'   => $user->getAppId(),
            'scope'    => ['wall', 'photos']
        ]);

        $this->user    = $user;
        $this->client  = $client;
        $this->url     = 'https://api.vk.com/method';
        $this->version = 5.64;

        $this->auth->auth();
    }

    /**
     * @param array $data
     * @return Response
     */
    public function photosGetWallUploadServer(array $data): Response
    {
        $data['v']            = $this->version;
        $data['access_token'] = $this->auth->getToken();

        return $this->authRequest(new Request('GET', $this->url . '/photos.getWallUploadServer'), ['query' => $data]);
    }

    /**
     * @param string $url
     * @param array  $data
     * @return Response
     */
    public function uploadPhoto(string $url, array $data): Response
    {
        return $this->client->send(new Request('POST', $url), ['multipart' => [$data]]);
    }

    /**
     * @param array $data
     * @return Response
     */
    public function photosSaveWallPhoto(array $data): Response
    {
        $data['v']            = $this->version;
        $data['access_token'] = $this->auth->getToken();

        return $this->authRequest(new Request('GET', $this->url . '/photos.saveWallPhoto'), ['query' => $data]);
    }

    /**
     * @param array $data
     * @return Response
     */
    public function wallPost(array $data): Response
    {
        $data['v']            = $this->version;
        $data['access_token'] = $this->auth->getToken();

        return $this->authRequest(new Request('POST', $this->url . '/wall.post'), ['form_params' => $data]);
    }

    /**
     * @param Request $request
     * @param array   $data
     * @param int     $try
     * @return mixed|\Psr\Http\Message\ResponseInterface
     * @throws \Exception
     */
    private function authRequest(Request $request, array $data, $try = 0)
    {
        try {

            return $this->client->send($request, $data);

        } catch (\Exception $e) {

            if ($try > 1) {
                throw $e;
            }

            $this->auth->auth();

            $try++;

            return $this->authRequest($request, $data, $try);
        }
    }
}