<?php

namespace iCoordinator\Entity\Subscription;

use iCoordinator\Entity\AbstractEntity;

/**
 * @Entity
 * @Table(name="subscription_chargify_mappers", options={"collate"="utf8_general_ci"})
 * @HasLifecycleCallbacks
 */
class ChargifyMapper extends AbstractEntity
{
    const ENTITY_NAME = 'entity:Subscription\ChargifyMapper';

    const RESOURCE_ID = 'subscription_chargify_mapper';

    /**
     * @var \iCoordinator\Entity\Subscription
     * @OneToOne(targetEntity="\iCoordinator\Entity\Subscription")
     * @JoinColumn(name="subscription_id", referencedColumnName="id")
     */
    protected $subscription;

    /**
     * @var int
     * @Column(type="integer", nullable=false, unique=true)
     */
    protected $chargify_subscription_id;

    /**
     * @return \iCoordinator\Entity\Subscription
     */
    public function getSubscription()
    {
        return $this->subscription;
    }

    /**
     * @param \iCoordinator\Entity\Subscription $subscription
     * @return ChargifyMapper
     */
    public function setSubscription($subscription)
    {
        $this->subscription = $subscription;
        return $this;
    }

    /**
     * @return int
     */
    public function getChargifySubscriptionId()
    {
        return $this->chargify_subscription_id;
    }

    /**
     * @param int $chargify_subscription_id
     * @return ChargifyMapper
     */
    public function setChargifySubscriptionId($chargify_subscription_id)
    {
        $this->chargify_subscription_id = $chargify_subscription_id;
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
