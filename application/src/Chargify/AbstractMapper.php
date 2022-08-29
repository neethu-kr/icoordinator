<?php

namespace iCoordinator\Chargify;

use Doctrine\ORM\EntityManager;
use iCoordinator\Entity\License;
use iCoordinator\Entity\Subscription;
use PhpCollection\Map;

abstract class AbstractMapper
{
    /**
     * @var string
     */
    protected $websiteId;

    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var License\ChargifyMapper
     */
    protected $chargifyLicenseMapper = null;

    /**
     * @var Subscription\ChargifyMapper
     */
    protected $chargifySubscriptionMapper = null;

    /**
     * @var Map
     */
    protected $customer;

    /**
     * @var Map
     */
    protected $product;

    /**
     * @var Map
     */
    protected $subscription;

    /**
     * @return Map
     */
    public function getCustomer()
    {
        return $this->customer;
    }

    abstract public function getNewSubscription();

    /**
     * @return License
     */
    public function getLicense()
    {
        return $this->getChargifyLicenseMapper()->getLicense();
    }

    /**
     * @return int
     */
    protected function getChargifyProductHandle()
    {
        return $this->product->get('handle')->getOrThrow(new \RuntimeException(
            'Product Handle is not defined in Chargify response'
        ));
    }

    /**
     * @return int
     */
    protected function getChargifySubscriptionId()
    {
        return $this->subscription->get('id')->getOrThrow(new \RuntimeException(
            'Subscription ID is not defined in Chargify response'
        ));
    }

    /**
     * @return License\ChargifyMapper
     */
    public function getChargifyLicenseMapper()
    {
        if ($this->chargifyLicenseMapper === null) {
            $chargifyMapperRepository = $this->getEntityManager()->getRepository(License\ChargifyMapper::ENTITY_NAME);
            $chargifyMappers = $chargifyMapperRepository->findBy([
                'chargify_website_id' => $this->websiteId
            ]);

            /** @var License\ChargifyMapper $chargifyMapper */
            foreach ($chargifyMappers as $chargifyMapper) {
                if (fnmatch($chargifyMapper->getChargifyProductHandle(), $this->getChargifyProductHandle())) {
                    $this->chargifyLicenseMapper = $chargifyMapper;
                    break;
                }
            }

            if (!$this->chargifyLicenseMapper) {
                throw new \RuntimeException(
                    'Chargify License mapper is not defined for product with id "'
                    . $this->getChargifyProductHandle() . '"'
                );
            }
        }

        return $this->chargifyLicenseMapper;
    }

    /**
     * @return Subscription\ChargifyMapper
     */
    public function getChargifySubscriptionMapper()
    {
        if ($this->chargifySubscriptionMapper === null) {
            $chargifyMapperRepository = $this->getEntityManager()->getRepository(
                Subscription\ChargifyMapper::ENTITY_NAME
            );
            $this->chargifySubscriptionMapper = $chargifyMapperRepository->findOneBy([
                'chargify_subscription_id' => $this->getChargifySubscriptionId()
            ]);

            if ($this->chargifySubscriptionMapper === null) {
                $this->chargifySubscriptionMapper = new Subscription\ChargifyMapper();
                $this->chargifySubscriptionMapper->setChargifySubscriptionId($this->getChargifySubscriptionId());
            }
        }

        return $this->chargifySubscriptionMapper;
    }

    /**
     * @return EntityManager
     */
    protected function getEntityManager()
    {
        return $this->entityManager;
    }
}
