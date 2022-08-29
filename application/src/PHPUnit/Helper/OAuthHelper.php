<?php

namespace iCoordinator\PHPUnit\Helper;

use iCoordinator\Entity\OAuthClient;

class OAuthHelper extends AbstractDataHelper
{
    protected $defaults = [
        'client_id' => 'test_client_id',
        'client_secret' => 'test_client_secret',
        'scope' => 'test_scope'
    ];

    public function createOAuthClient($data = array(), $useDefaults = true, $randomizeDefaults = true)
    {

        $oauthClient = new OAuthClient();

        $this->hydrate($oauthClient, $data, $useDefaults, $randomizeDefaults);

        $this->getEntityManager()->persist($oauthClient);
        $this->getEntityManager()->flush($oauthClient);

        return $oauthClient;
    }
}
