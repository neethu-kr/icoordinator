<?php

namespace iCoordinator\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use iCoordinator\Entity\License\ChargifyMapper;

/**
 * @Entity
 * @Table(name="licenses", options={"collate"="utf8_general_ci"})
 * @HasLifecycleCallbacks
 */
class License extends AbstractEntity
{
    const ENTITY_NAME = 'entity:License';

    const RESOURCE_ID = 'license';

    /**
     * @var \iCoordinator\Entity\License\ChargifyMapper
     * @OneToMany(
     *  targetEntity="\iCoordinator\Entity\License\ChargifyMapper",
     *  mappedBy="license",
     *  cascade={"persist","remove"}
     * )
     */
    protected $chargify_mappers;

    /**
     * @var int
     * @Column(type="integer", nullable=true)
     */
    protected $users_limit;

    /**
     * @var int
     * @Column(type="integer", nullable=true)
     */
    protected $workspaces_limit;

    /**
     * @var int
     * @Column(type="integer", nullable=true)
     */
    protected $storage_limit;

    /**
     * @var int
     * @Column(type="integer", nullable=true)
     */
    protected $file_size_limit;

    public function __construct()
    {
        $this->chargify_mappers = new ArrayCollection();
    }

    /**
     * @return ArrayCollection
     */
    public function getChargifyMappers()
    {
        return $this->chargify_mappers;
    }


    public function addChargifyMapper(ChargifyMapper $chargifyMapper)
    {
        $this->chargify_mappers[] = $chargifyMapper;
        $chargifyMapper->setLicense($this);

        return $this;
    }

    /**
     * @return int
     */
    public function getUsersLimit()
    {
        return $this->users_limit;
    }

    /**
     * @param int $users_limit
     * @return License
     */
    public function setUsersLimit($users_limit)
    {
        $this->users_limit = $users_limit;
        return $this;
    }

    /**
     * @return int
     */
    public function getWorkspacesLimit()
    {
        return $this->workspaces_limit;
    }

    /**
     * @param int $workspaces_limit
     * @return License
     */
    public function setWorkspacesLimit($workspaces_limit)
    {
        $this->workspaces_limit = $workspaces_limit;
        return $this;
    }

    /**
     * @return int
     */
    public function getStorageLimit()
    {
        return $this->storage_limit;
    }

    /**
     * @param int $storage_limit
     * @return License
     */
    public function setStorageLimit($storage_limit)
    {
        $this->storage_limit = $storage_limit;
        return $this;
    }

    /**
     * @return int
     */
    public function getFileSizeLimit()
    {
        return $this->file_size_limit;
    }

    /**
     * @param int $file_size_limit
     * @return License
     */
    public function setFileSizeLimit($file_size_limit)
    {
        $this->file_size_limit = $file_size_limit;
        return $this;
    }

    public function getResourceId()
    {
        return self::RESOURCE_ID;
    }

    public function jsonSerialize()
    {
        return [
            'entity_type' => self::RESOURCE_ID,
            'id' => $this->getId(),
            'users_limit' => $this->getUsersLimit(),
            'workspaces_limit' => $this->getWorkspacesLimit(),
            'storage_limit' => $this->getStorageLimit()
        ];
    }
}
