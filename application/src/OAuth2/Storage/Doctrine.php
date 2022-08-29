<?php

namespace iCoordinator\OAuth2\Storage;

use Carbon\Carbon;
use Doctrine\ORM\EntityManager;
use iCoordinator\Entity\OAuthAccessToken;
use iCoordinator\Entity\OAuthAuthorizationCode;
use iCoordinator\Entity\OAuthClient;
use iCoordinator\Entity\OAuthRefreshToken;
use iCoordinator\Entity\OAuthScope;
use iCoordinator\Entity\User;
use OAuth2\Storage\AccessTokenInterface;
use OAuth2\Storage\AuthorizationCodeInterface;
use OAuth2\Storage\ClientCredentialsInterface;
use OAuth2\Storage\RefreshTokenInterface;
use OAuth2\Storage\ScopeInterface;
use OAuth2\Storage\UserCredentialsInterface;

class Doctrine implements
    AuthorizationCodeInterface,
    AccessTokenInterface,
    ClientCredentialsInterface,
    UserCredentialsInterface,
    RefreshTokenInterface,
    ScopeInterface
{
    protected $em;
    protected $config;

    public function __construct(EntityManager $em, $config = array())
    {
        if (!$em instanceof EntityManager) {
            throw new \InvalidArgumentException(
                'First argument to iCoordinator\OAuth2\Storage\Doctrine must be an instance of EntityManager'
            );
        }
        $this->em = $em;

        $this->config = $config;
    }

    /* OAuth2\Storage\ClientCredentialsInterface */
    public function checkClientCredentials($client_id, $client_secret = null)
    {
        $oauthClient = $this->em->getRepository('iCoordinator\Entity\OAuthClient')->findOneBy(array(
            'clientId' => $client_id
        ));
        return $oauthClient && $oauthClient->getClientSecret() == $client_secret;
    }

    public function isPublicClient($client_id)
    {
        $oauthClient = $this->em->getRepository('iCoordinator\Entity\OAuthClient')->findOneBy(array(
            'clientId' => $client_id
        ));

        if (!$oauthClient) {
            return false;
        }

        $oauthSecret = $oauthClient->getClientSecret();
        return empty($oauthSecret);
    }

    /* OAuth2\Storage\ClientInterface */

    public function checkRestrictedGrantType($client_id, $grant_type)
    {
        $details = $this->getClientDetails($client_id);
        if (isset($details['grant_types'])) {
            $grant_types = explode(' ', $details['grant_types']);

            return in_array($grant_type, (array)$grant_types);
        }

        // if grant_types are not defined, then none are restricted
        return true;
    }

    public function getClientDetails($client_id)
    {
        /** @var OAuthClient $oauthClient */
        $oauthClient = $this->em->getRepository('iCoordinator\Entity\OAuthClient')->findOneBy(array(
            'clientId' => $client_id
        ));

        return array(
            'client_id' => $oauthClient->getClientId(),
            'client_secret' => $oauthClient->getClientSecret(),
            'grant_types' => $oauthClient->getGrantTypes(),
            'redirect_uri' => $oauthClient->getRedirectUri(),
            'scope' => $oauthClient->getScope(),
            'user_id' => $oauthClient->getUserId()
        );
    }

    /* OAuth2\Storage\AccessTokenInterface */

    public function getAccessToken($access_token)
    {
        /** @var OAuthAccessToken $accessToken */
        $accessToken = $this->em->getRepository('\iCoordinator\Entity\OAuthAccessToken')->findOneBy(array(
           'accessToken' => $access_token
        ));

        if ($accessToken) {
            return array(
                'access_token' => $accessToken->getAccessToken(),
                'expires' => $accessToken->getExpires()->getTimestamp(),
                'client_id' => $accessToken->getClientId(),
                'user_id' => $accessToken->getUserId(),
                'scope' => $accessToken->getScope()
            );
        }

        return null;
    }

    public function setAccessToken($access_token, $client_id, $user_id, $expires, $scope = null)
    {
        // convert expires from timestamp
        $expires = Carbon::createFromTimestamp($expires);

        /** @var OAuthAccessToken $accessToken */
        $accessToken = $this->em->getRepository('\iCoordinator\Entity\OAuthAccessToken')->findOneBy(array(
            'accessToken' => $access_token
        ));

        if (!$accessToken) {
            $accessToken = new OAuthAccessToken();
            $accessToken->setAccessToken($access_token);
        }

        $accessToken->setClientId($client_id);
        $accessToken->setUserId($user_id);
        $accessToken->setExpires($expires);
        $accessToken->setScope($scope);
        $this->em->persist($accessToken);

        return $this->em->flush();
    }

    /* OAuth2\Storage\AuthorizationCodeInterface */
    public function getAuthorizationCode($code)
    {
        /** @var OAuthAuthorizationCode $authorizationCode */
        $authorizationCode = $this->em->getRepository('\iCoordinator\Entity\OAuthAuthorizationCode')
            ->findOneBy(array('authorizationCode' => $code));

        if ($authorizationCode) {
            return array(
                'authorization_code' => $authorizationCode->getAuthorizationCode(),
                'client_id' => $authorizationCode->getClientId(),
                'user_id' => $authorizationCode->getUserId(),
                'redirect_uri' => $authorizationCode->getRedirectUri(),
                'expires' => $authorizationCode->getExpires()->getTimestamp(),
                'scope' => $authorizationCode->getScope()
            );
        }

        return null;
    }

    public function setAuthorizationCode($code, $client_id, $user_id, $redirect_uri, $expires, $scope = null)
    {
        // convert expires from timestamp
        $expires = Carbon::createFromTimestamp($expires);

        /** @var OAuthAuthorizationCode $authorizationCode */
        $authorizationCode = $this->em->getRepository('\iCoordinator\Entity\OAuthAuthorizationCode')
            ->findOneBy(array('authorizationCode' => $code));

        if (!$authorizationCode) {
            $authorizationCode = new OAuthAuthorizationCode();
            $authorizationCode->setAuthorizationCode($code);
        }

        $authorizationCode->setClientId($client_id);
        $authorizationCode->setUserId($user_id);
        $authorizationCode->setRedirectUri($redirect_uri);
        $authorizationCode->setExpires($expires);
        $authorizationCode->setScope($scope);
        $this->em->persist($authorizationCode);

        return $this->em->flush();
    }

    public function expireAuthorizationCode($code)
    {
        /** @var OAuthAuthorizationCode $authorizationCode */
        $authorizationCode = $this->em->getRepository('\iCoordinator\Entity\OAuthAuthorizationCode')
            ->findOneBy(array('authorizationCode' => $code));

        $this->em->remove($authorizationCode);
        return $this->em->flush();
    }

    /* OAuth2\Storage\UserCredentialsInterface */
    public function checkUserCredentials($username, $password)
    {
        if ($user = $this->getUser($username)) {
            return $this->checkPassword($user, $password) && $user->isEmailConfirmed();
        }

        return false;
    }

    /**
     * @param $username
     * @return bool|User
     */
    public function getUser($username)
    {
        $user = $this->em->getRepository('\iCoordinator\Entity\User')->findOneBy(array(
            'email' => $username
        ));

        if (!$user) {
            return false;
        }

        return $user;
    }

    /* OAuth2\Storage\RefreshTokenInterface */

    protected function checkPassword(User $user, $password)
    {
        return password_verify($password, $user->getPassword());
    }

    public function getUserDetails($username)
    {
        /* @var User $user*/
        $user = $this->getUser($username);

        return array(
            'user_id' => $user->getId(),
            'username' => $user->getEmail()
        );
    }

    public function getRefreshToken($refresh_token)
    {
        /** @var OAuthRefreshToken $refreshToken */
        $refreshToken = $this->em->getRepository('\iCoordinator\Entity\OAuthRefreshToken')->findOneBy(array(
            'refreshToken' => $refresh_token
        ));

        if ($refreshToken) {
            return array(
                'refresh_token' => $refreshToken->getRefreshToken(),
                'expires' => $refreshToken->getExpires()->getTimestamp(),
                'client_id' => $refreshToken->getClientId(),
                'user_id' => $refreshToken->getUserId(),
                'scope' => $refreshToken->getScope()
            );
        }

        return null;
    }

    public function setRefreshToken($refresh_token, $client_id, $user_id, $expires, $scope = null)
    {
        $expires = Carbon::createFromTimestamp($expires);

        $refreshToken = new OAuthRefreshToken();
        $refreshToken->setRefreshToken($refresh_token);
        $refreshToken->setClientId($client_id);
        $refreshToken->setUserId($user_id);
        $refreshToken->setExpires($expires);
        $refreshToken->setScope($scope);

        $this->em->persist($refreshToken);

        return $this->em->flush();
    }

    public function unsetRefreshToken($refresh_token)
    {
        /** @var OAuthRefreshToken $refreshToken */
        $refreshToken = $this->em->getRepository('\iCoordinator\Entity\OAuthRefreshToken')->findOneBy(array(
            'refreshToken' => $refresh_token
        ));

        if ($refreshToken) {
            $this->em->remove($refreshToken);
        }
        return $this->em->flush();
    }

    /* ScopeInterface */

    public function scopeExists($scope)
    {
        $scopes = explode(' ', $scope);

        $dql = 'SELECT COUNT(s.id) FROM \iCoordinator\Entity\OAuthScope s WHERE s.scope IN (?1)';
        $count = $this->em->createQuery($dql)
            ->setParameter(1, $scopes)
            ->getSingleScalarResult();

        return $count == count((array)$scope);
    }

    public function getDefaultScope($client_id = null)
    {
        /** @var OAuthScope $scope */
        $scopes = $this->em->getRepository('\iCoordinator\Entity\OAuthScope')->findBy(array(
            'isDefault' => true
        ));

        if ($scopes) {
            $defaultScope = array_map(function ($scope) {
                return $scope->getScope();
            }, $scopes);

            return implode(' ', $defaultScope);
        }

        return null;
    }

    public function getClientScope($client_id)
    {
        if (!$clientDetails = $this->getClientDetails($client_id)) {
            return false;
        }

        if (isset($clientDetails['scope'])) {
            return $clientDetails['scope'];
        }

        return null;
    }
}
