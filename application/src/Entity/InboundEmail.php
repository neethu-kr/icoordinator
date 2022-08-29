<?php

namespace iCoordinator\Entity;

use Laminas\Stdlib\JsonSerializable;

class InboundEmail implements JsonSerializable
{
    /**
     * @var User
     */
    private $user;

    /**
     * @var AbstractEntity
     */
    private $resource;

    /**
     * @var string
     */
    private $email;

    public function jsonSerialize()
    {
        return array(
            'entity_type' => 'inbound_email',
            'user' => $this->getUser()->jsonSerialize(true),
            'resource' => $this->getResource()->jsonSerialize(true),
            'email' => $this->getEmail()
        );
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param $user
     * @return $this
     */
    public function setUser($user)
    {
        $this->user = $user;
        return $this;
    }

    /**
     * @return AbstractEntity
     */
    public function getResource()
    {
        return $this->resource;
    }

    /**
     * @param $resource
     * @return $this
     */
    public function setResource($resource)
    {
        $this->resource = $resource;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param $email
     * @return $this
     */
    public function setEmail($email)
    {
        $this->email = $email;
        return $this;
    }
}
