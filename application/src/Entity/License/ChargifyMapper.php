<?php

namespace iCoordinator\Entity\License;

use iCoordinator\Entity\AbstractEntity;
use iCoordinator\Entity\License;

/**
 * @Entity
 * @Table(
 *  name="license_chargify_mappers",
 *  uniqueConstraints={@UniqueConstraint(name="website_product_unique_idx", columns={
 *      "chargify_website_id", "chargify_product_handle"
 *  })},
 *  options={"collate"="utf8_general_ci"}
 * )
 * @HasLifecycleCallbacks
 */
class ChargifyMapper extends AbstractEntity
{
    const ENTITY_NAME = 'entity:License\ChargifyMapper';

    const RESOURCE_ID = 'license_chargify_mapper';

    /**
     * @var \iCoordinator\Entity\License
     * @ManyToOne(targetEntity="\iCoordinator\Entity\License")
     * @JoinColumn(name="license_id", referencedColumnName="id")
     */
    protected $license;

    /**
     * @var string
     * @Column(type="string", length=255, nullable=false)
     */
    protected $chargify_website_id;

    /**
     * @var string
     * @Column(type="string", length=255, nullable=false)
     */
    protected $chargify_product_handle;

    /**
     * @var string
     * @Column(type="string", length=255, nullable=false)
     */
    protected $chargify_users_component_ids;

    /**
     * @var string
     * @Column(type="string", length=255, nullable=true)
     */
    protected $chargify_workspaces_component_ids = null;

    /**
     * @var string
     * @Column(type="string", length=255, nullable=true)
     */
    protected $chargify_storage_component_ids = null;

    /**
     * @return string
     */
    public function getChargifyWebsiteId()
    {
        return $this->chargify_website_id;
    }

    /**
     * @param string $chargify_website_id
     * @return ChargifyMapper
     */
    public function setChargifyWebsiteId($chargify_website_id)
    {
        $this->chargify_website_id = $chargify_website_id;
        return $this;
    }

    /**
     * @return License
     */
    public function getLicense()
    {
        return $this->license;
    }

    /**
     * @param License $license
     * @return ChargifyMapper
     */
    public function setLicense($license)
    {
        $this->license = $license;
        return $this;
    }

    /**
     * @return int
     */
    public function getChargifyProductHandle()
    {
        return $this->chargify_product_handle;
    }

    /**
     * @param $chargify_product_handle
     * @return $this
     */
    public function setChargifyProductHandle($chargify_product_handle)
    {
        $this->chargify_product_handle = $chargify_product_handle;
        return $this;
    }

    /**
     * @return array
     */
    public function getChargifyUsersComponentIds()
    {
        return explode('|', $this->chargify_users_component_ids);
    }

    /**
     * @param array $chargify_users_component_ids
     * @return $this
     */
    public function setChargifyUsersComponentIds(array $chargify_users_component_ids)
    {
        $this->chargify_users_component_ids = implode('|', $chargify_users_component_ids);
        return $this;
    }

    /**
     * @return array
     */
    public function getChargifyWorkspacesComponentIds()
    {
        if (!empty($this->chargify_workspaces_component_ids)) {
            return explode('|', $this->chargify_workspaces_component_ids);
        }
        return [];
    }

    /**
     * @param array $chargify_workspaces_component_ids
     * @return $this
     */
    public function setChargifyWorkspacesComponentIds(array $chargify_workspaces_component_ids = null)
    {
        if ($chargify_workspaces_component_ids !== null) {
            $this->chargify_workspaces_component_ids = implode('|', $chargify_workspaces_component_ids);
        }
        return $this;
    }

    /**
     * @return array
     */
    public function getChargifyStorageComponentIds()
    {
        if (!empty($this->chargify_storage_component_ids)) {
            return explode('|', $this->chargify_storage_component_ids);
        }
        return [];
    }

    /**
     * @param array $chargify_storage_component_ids
     * @return $this
     */
    public function setChargifyStorageComponentIds(array $chargify_storage_component_ids = null)
    {
        if ($chargify_storage_component_ids !== null) {
            $this->chargify_storage_component_ids = implode('|', $chargify_storage_component_ids);
        }
        return $this;
    }

    public function getResourceId()
    {
        return self::RESOURCE_ID;
    }

    public function jsonSerialize()
    {
        return [];
    }
}
