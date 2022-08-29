<?php

namespace iCoordinator\Entity;

/**
 * OAuthAccessToken
 *
 * @Table(name="oauth_access_tokens", options={"collate"="utf8_general_ci"})
 * @Entity
 */
class OAuthAccessToken
{
    /**
     * @var string
     *
     * @Column(name="access_token", type="string", length=40, nullable=false)
     * @Id
     */
    private $accessToken;

    /**
     * @var string
     *
     * @Column(name="client_id", type="string", length=80, nullable=false)
     */
    private $clientId;

    /**
     * @var string
     *
     * @Column(name="user_id", type="string", length=255, nullable=true)
     */
    private $userId;

    /**
     * @var \Carbon\Carbon
     *
     * @Column(name="expires", type="datetime", nullable=false)
     */
    private $expires = 'CURRENT_TIMESTAMP';

    /**
     * @var string
     *
     * @Column(name="scope", type="string", length=2000, nullable=true)
     */
    private $scope;

    /**
     * @return string
     */
    public function getAccessToken()
    {
        return $this->accessToken;
    }

    /**
     * @param string $accessToken
     */
    public function setAccessToken($accessToken)
    {
        $this->accessToken = $accessToken;
    }

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
     * @return \DateTime
     */
    public function getExpires()
    {
        return $this->expires;
    }

    /**
     * @param \DateTime $expires
     */
    public function setExpires($expires)
    {
        $this->expires = $expires;
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
