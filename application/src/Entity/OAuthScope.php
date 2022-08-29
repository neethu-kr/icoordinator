<?php

namespace iCoordinator\Entity;

/**
 * OAuthScope
 *
 * @Table(name="oauth_scopes", options={"collate"="utf8_general_ci"})
 * @Entity
 */
class OAuthScope
{
    /**
     * @var int
     * @Id
     * @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     *
     * @Column(name="scope", type="text", length=65535, nullable=true)
     */
    private $scope;

    /**
     * @var boolean
     *
     * @Column(name="is_default", type="boolean", nullable=true)
     */
    private $isDefault;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return boolean
     */
    public function getIsDefault()
    {
        return $this->isDefault;
    }

    /**
     * @param boolean $isDefault
     */
    public function setIsDefault($isDefault)
    {
        $this->isDefault = $isDefault;
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
}
