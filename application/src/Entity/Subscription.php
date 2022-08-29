<?php

namespace iCoordinator\Entity;

/**
 * @Entity
 * @Table(name="subscriptions", options={"collate"="utf8_general_ci"})
 * @HasLifecycleCallbacks
 */
class Subscription extends AbstractEntity
{
    const ENTITY_NAME = 'entity:Subscription';

    const RESOURCE_ID = 'subscription';

    const STATE_ACTIVE = 'active';

    const STATE_TRIALING = 'trialing';

    const STATE_CANCELED = 'canceled';

    const STATE_TRIAL_ENDED = 'trial_ended';

    const STATE_ENDED = 'ended';

    /**
     * @var \iCoordinator\Entity\Subscription\ChargifyMapper
     * @OneToOne(
     *  targetEntity="\iCoordinator\Entity\Subscription\ChargifyMapper",
     *  mappedBy="subscription",
     *  cascade={"persist","remove"}
     * )
     */
    protected $chargify_mapper;

    /**
     * @var \iCoordinator\Entity\Portal
     * @OneToOne(targetEntity="\iCoordinator\Entity\Portal")
     * @JoinColumn(name="portal_id", referencedColumnName="id")
     */
    protected $portal;

    /**
     * @var \iCoordinator\Entity\License
     * @ManyToOne(targetEntity="\iCoordinator\Entity\License")
     * @JoinColumn(name="license_id", referencedColumnName="id")
     */
    protected $license;

    /**
     * @var int
     * @Column(type="integer", nullable=true)
     */
    protected $users_allocation;

    /**
     * @var int
     * @Column(type="integer", nullable=true)
     */
    protected $workspaces_allocation;

    /**
     * @var int
     * @Column(type="integer", nullable=true)
     */
    protected $storage_allocation;

    /**
     * @var string
     * @Column(type="string", nullable=false, length=20)
     */
    protected $state;

    /**
     * @return Subscription\ChargifyMapper
     */
    public function getChargifyMapper()
    {
        return $this->chargify_mapper;
    }

    /**
     * @param Subscription\ChargifyMapper $chargifyMapper
     * @return Subscription
     */
    public function setChargifyMapper($chargifyMapper)
    {
        $this->chargify_mapper = $chargifyMapper;
        $chargifyMapper->setSubscription($this);
        return $this;
    }

    /**
     * @return Portal
     */
    public function getPortal()
    {
        return $this->portal;
    }

    /**
     * @param Portal $portal
     * @return Subscription
     */
    public function setPortal($portal)
    {
        $this->portal = $portal;
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
     * @return Subscription
     */
    public function setLicense($license)
    {
        $this->license = $license;
        return $this;
    }

    /**
     * @return int
     */
    public function getUsersAllocation()
    {
        return $this->users_allocation;
    }

    /**
     * @param int $users_allocation
     * @return Subscription
     */
    public function setUsersAllocation($users_allocation)
    {
        $this->users_allocation = $users_allocation;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getWorkspacesAllocation()
    {
        if ($this->workspaces_allocation) {
            return $this->workspaces_allocation;
        } else {
            return $this->getLicense()->getWorkspacesLimit();
        }
    }

    /**
     * @param mixed $workspaces_allocation
     * @return Subscription
     */
    public function setWorkspacesAllocation($workspaces_allocation)
    {
        $this->workspaces_allocation = $workspaces_allocation;
        return $this;
    }

    /**
     * @return int
     */
    public function getStorageAllocation()
    {
        if ($this->storage_allocation) {
            return $this->storage_allocation;
        } else {
            return $this->getLicense()->getStorageLimit();
        }
    }

    /**
     * @param int $storage_allocation
     * @return Subscription
     */
    public function setStorageAllocation($storage_allocation)
    {
        $this->storage_allocation = $storage_allocation;
        return $this;
    }

    /**
     * @return string
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * @param string $state
     * @return Subscription
     */
    public function setState($state)
    {
        $this->state = $state;
        return $this;
    }

    public function getResourceId()
    {
        return self::RESOURCE_ID;
    }

    public function jsonSerialize($mini = false)
    {
        if ($mini) {
            return [
                'state' => $this->getState()
            ];
        } else {
            return [
                'entity_type' => self::RESOURCE_ID,
                'id' => $this->getId(),
                'license' => $this->getLicense()->jsonSerialize(true),
                'users_allocation' => $this->getUsersAllocation(),
                'workspaces_allocation' => $this->getWorkspacesAllocation(),
                'storage_allocation' => $this->getStorageAllocation(),
                'state' => $this->getState()
            ];
        }
    }
}
