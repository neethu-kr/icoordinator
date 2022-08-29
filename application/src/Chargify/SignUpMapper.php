<?php

namespace iCoordinator\Chargify;

use Doctrine\ORM\EntityManager;
use iCoordinator\Entity\License;
use iCoordinator\Entity\Subscription;
use PhpCollection\Map;

class SignUpMapper extends AbstractMapper
{
    /**
     * @var Map
     */
    private $components;

    /**
     * @param $chargifyResponse
     * @param $chargifyRequest
     * @param $websiteId
     * @param EntityManager $entityManager
     */
    public function __construct($chargifyResponse, $chargifyRequest, $websiteId, EntityManager $entityManager)
    {
        if (!isset($chargifyResponse->signup)) {
            throw new \InvalidArgumentException('Invalid chargify signup response');
        }
        if (isset($chargifyResponse->signup->customer)) {
            $this->customer = new Map((array)$chargifyResponse->signup->customer);
        }
        if (isset($chargifyResponse->signup->product)) {
            $this->product = new Map((array)$chargifyResponse->signup->product);
        }
        if (isset($chargifyResponse->signup->subscription)) {
            $this->subscription = new Map((array)$chargifyResponse->signup->subscription);
        }
        if (isset($chargifyRequest->signup->components)) {
            $this->components = new Map(get_object_vars($chargifyRequest->signup->components));
        }

        $this->entityManager = $entityManager;
        $this->websiteId = $websiteId;
    }

    /**
     * @return string
     */
    public function getPortalUuid()
    {
        return $this->customer->get('reference')->getOrElse(null);
    }

    /**
     * @return Subscription
     */
    public function getNewSubscription()
    {
        $chargifySubscriptionMapper = $this->getChargifySubscriptionMapper();
        $subscription               = $chargifySubscriptionMapper->getSubscription();
        if ($subscription === null) {
            $subscription = new Subscription();
            $subscription
                ->setLicense($this->getLicense())
                ->setUsersAllocation($this->getUsersAllocation())
                ->setWorkspacesAllocation($this->getWorkspacesAllocation())
                ->setStorageAllocation($this->getStorageAllocation())
                ->setState($this->getSubscriptionState())
                ->setChargifyMapper($chargifySubscriptionMapper);
        }

        return $subscription;
    }

    /**
     * @return mixed
     */
    private function getSubscriptionState()
    {
        return $this->subscription->get('state')->getOrThrow(new \RuntimeException(
            'Subscription state is not defined in Chargify response'
        ));
    }

    /**
     * @return int
     */
    private function getUsersAllocation()
    {
        $chargifyUsersComponentIds = $this->getChargifyLicenseMapper()->getChargifyUsersComponentIds();

        foreach ($chargifyUsersComponentIds as $chargifyUsersComponentId) {
            if (isset($this->components)) {
                return $this->components->get($chargifyUsersComponentId)->getOrElse([]);
            }
        }

        return 0;
    }

    /**
     * @return int
     */
    private function getWorkspacesAllocation()
    {
        $chargifyWorkspacesComponentIds = $this->getChargifyLicenseMapper()->getChargifyWorkspacesComponentIds();

        foreach ($chargifyWorkspacesComponentIds as $chargifyWorkspacesComponentId) {
            if (isset($this->components)) {
                return $this->components->get($chargifyWorkspacesComponentId)->getOrElse([]);
            }
        }

        return 0;
    }


    /**
     * @return int
     */
    private function getStorageAllocation()
    {
        $chargifyStorageComponentIds = $this->getChargifyLicenseMapper()->getChargifyStorageComponentIds();

        foreach ($chargifyStorageComponentIds as $chargifyStorageComponentId) {
            if (isset($this->components)) {
                return $this->components->get($chargifyStorageComponentId)->getOrElse([]);
            }
        }

        return 0;
    }
}
