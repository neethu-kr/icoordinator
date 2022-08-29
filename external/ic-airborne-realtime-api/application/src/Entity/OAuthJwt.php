<?php

namespace iCoordinator\Entity;

/**
 * OAuthJwt
 *
 * @Table(name="oauth_jwt", options={"collate"="utf8_general_ci"})
 * @Entity
 */
class OAuthJwt
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
     * @Column(name="subject", type="string", length=80, nullable=true)
     */
    private $subject;

    /**
     * @var string
     *
     * @Column(name="public_key", type="string", length=2000, nullable=true)
     */
    private $publicKey;

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
    public function getPublicKey()
    {
        return $this->publicKey;
    }

    /**
     * @param string $publicKey
     */
    public function setPublicKey($publicKey)
    {
        $this->publicKey = $publicKey;
    }

    /**
     * @return string
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * @param string $subject
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;
    }
}
