<?php

namespace iCoordinator\Entity;

/**
 * OAuthClient
 *
 * @Table(name="oauth_clients", options={"collate"="utf8_general_ci"})
 * @Entity
 */
class OAuthClient
{
    /**
     * @var string
     *
     * @Column(name="client_id", type="string", length=80, nullable=false)
     * @Id
     */
    private $clientId;

    /**
     * @var string
     *
     * @Column(name="client_secret", type="string", length=80, nullable=true)
     */
    private $clientSecret;

    /**
     * @var string
     *
     * @Column(name="redirect_uri", type="string", length=2000, nullable=true)
     */
    private $redirectUri;

    /**
     * @var string
     *
     * @Column(name="grant_types", type="string", length=80, nullable=true)
     */
    private $grantTypes;

    /**
     * @var string
     *
     * @Column(name="scope", type="string", length=100, nullable=true)
     */
    private $scope;

    /**
     * @var string
     *
     * @Column(name="user_id", type="string", length=80, nullable=true)
     */
    private $userId;

    /**
     * @return string
     */
    public function getClientId()
    {
        return $this->clientId;
    }

    /**
     * @param string $clientId
     */
    public function setClientId($clientId)
    {
        $this->clientId = $clientId;
    }

    /**
     * @return string
     */
    public function getClientSecret()
    {
        return $this->clientSecret;
    }

    /**
     * @param string $clientSecret
     */
    public function setClientSecret($clientSecret)
    {
        $this->clientSecret = $clientSecret;
    }

    /**
     * @return string
     */
    public function getGrantTypes()
    {
        return $this->grantTypes;
    }

    /**
     * @param string $grantTypes
     */
    public function setGrantTypes($grantTypes)
    {
        $this->grantTypes = $grantTypes;
    }

    /**
     * @return string
     */
    public function getRedirectUri()
    {
        return $this->redirectUri;
    }

    /**
     * @param string $redirectUri
     */
    public function setRedirectUri($redirectUri)
    {
        $this->redirectUri = $redirectUri;
    }

    /**
     * @return string
     */
    public function getScope()
    {
        return $this->scope;
    }

    /**
     * @param string $scope
     */
    public function setScope($scope)
    {
        $this->scope = $scope;
    }

    /**
     * @return string
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param string $userId
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;
    }
}
