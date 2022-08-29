<?php

namespace iCoordinator\Entity;

/**
 * OAuthAuthorizationCode
 *
 * @Table(name="oauth_authorization_codes", options={"collate"="utf8_general_ci"})
 * @Entity
 */
class OAuthAuthorizationCode
{
    /**
     * @var string
     *
     * @Column(name="authorization_code", type="string", length=40, nullable=false)
     * @Id
     */
    private $authorizationCode;

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
     * @var string
     *
     * @Column(name="redirect_uri", type="string", length=2000, nullable=true)
     */
    private $redirectUri;

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
    public function getAuthorizationCode()
    {
        return $this->authorizationCode;
    }

    /**
     * @param string $authorizationCode
     */
    public function setAuthorizationCode($authorizationCode)
    {
        $this->authorizationCode = $authorizationCode;
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
     * @return \Carbon\Carbon
     */
    public function getExpires()
    {
        return $this->expires;
    }

    /**
     * @param \Carbon\Carbon $expires
     */
    public function setExpires($expires)
    {
        $this->expires = $expires;
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
