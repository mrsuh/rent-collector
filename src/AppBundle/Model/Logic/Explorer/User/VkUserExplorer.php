<?php

namespace AppBundle\Model\Logic\Explorer\User;

use AppBundle\Exception\ExploreException;
use AppBundle\Request\VkPublicRequest;

class VkUserExplorer implements UserExplorerInterface
{
    private $request;

    /**
     * VkUserExplorer constructor.
     * @param VkPublicRequest $request
     */
    public function __construct(VkPublicRequest $request)
    {
        $this->request = $request;
    }

    /**
     * @param int $id
     * @return User
     * @throws ExploreException
     */
    public function explore(int $id): User
    {
        $response = $this->request->getUserInfo($id);

        $info = json_decode($response->getBody()->getContents(), true);

        if (!array_key_exists('response', $info)) {
            throw new ExploreException('Has not key "response" in response');
        }

        $user = null;
        foreach ($info['response'] as $i) {
            switch (true) {
                case array_key_exists('id', $i):
                    $user_id = (string)$i['id'];
                    break;
                case array_key_exists('uid', $i):
                    $user_id = (string)$i['uid'];
                    break;
                default:
                    $user_id = null;
            }

            if ((string)$user_id === (string)$id) {
                $user = $i;
                break;
            }
        }

        if (null === $user) {
            return null;
        }

        foreach (['first_name', 'last_name', 'photo_100'] as $key) {
            if (!array_key_exists($key, $user)) {
                throw new ExploreException(sprintf('Has not key "%s" in response', $key));
            }
        }

        return
            (new User())
                ->setFirstName($user['first_name'])
                ->setLastName($user['last_name'])
                ->setPhoto($user['photo_100']);
    }
}