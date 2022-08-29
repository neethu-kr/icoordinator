<?php

namespace iCoordinator\Chargify;

use Doctrine\ORM\EntityManager;
use iCoordinator\Entity\License;
use iCoordinator\Entity\Subscription;
use Laminas\Authentication\Adapter\Http\Exception\RuntimeException;
use PhpCollection\Map;

class WebhookMapper extends AbstractMapper
{
    const EVENT_SIGNUP_SUCCESS = 'signup_success';
    const EVENT_SIGNUP_FAILURE = 'signup_failure';
    const EVENT_RENEWAL_SUCCESS = 'renewal_success';
    const EVENT_RENEWAL_FAILURE = 'renewal_failure';
    const EVENT_PAYMENT_SUCCESS = 'payment_success';
    const EVENT_PAYMENT_FAILURE = 'payment_failure';
    const EVENT_BILLING_DATE_CHANGE = 'billing_date_change';
    const EVENT_SUBSCRIPTION_STATE_CHANGE = 'subscription_state_change';
    const EVENT_SUBSCRIPTION_PRODUCT_CHANGE = 'subscription_product_change';
    const EVENT_SUBSCRIPTION_CARD_UPDATE = 'subscription_card_update';
    const EVENT_EXPIRING_CARD = 'expiring_card';
    const EVENT_CUSTOMER_UPDATE = 'customer_update';
    const EVENT_COMPONENT_ALLOCATION_CHANGE = 'component_allocation_change';
    const EVENT_METERED_USAGE = 'metered_usage';
    const EVENT_UPGRADE_DOWNGRADE_SUCCESS = 'upgrade_downgrade_success';
    const EVENT_UPGRADE_DOWNGRADE_FAILURE = 'upgrade_downgrade_failure';
    const EVENT_REFUND_SUCCESS = 'refund_success';
    const EVENT_REFUND_FAILURE = 'refund_failure';
    const EVENT_UPCOMING_RENEWAL_NOTICE = 'upcoming_renewal_notice';
    const EVENT_END_OF_TRIAL_NOTICE = 'end_of_trial_notice';
    const EVENT_STATEMENT_CLOSED = 'statement_closed';
    const EVENT_STATEMENT_SETTLED = 'statement_settled';
    const EVENT_EXPIRATION_DATE_CHANGE = 'expiration_date_change';

    const DEFAULT_PORTAL_NAME = 'Portal 1';

    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $event;

    /**
     * @var Map
     */
    private $payload;

    /**
     * @var Map
     */
    private $component;

    /**
     * @var Map
     */
    private $previous_product;


    public function __construct(array $data, EntityManager $entityManager)
    {
        if (!isset($data['id']) || !isset($data['event']) || !isset($data['payload'])) {
            throw new \InvalidArgumentException();
        }

        $this->id           = $data['id'];
        $this->event        = $data['event'];
        $this->payload      = new Map($data['payload']);
        $this->websiteId    = str_replace('-', '_', $data['payload']['site']['subdomain']);
        $this->entityManager = $entityManager;

        $subcription        = $this->payload->get('subscription')->getOrElse(null);
        $customer           = $this->payload->get('customer')->getOrElse(null);
        $product            = $this->payload->get('product')->getOrElse(null);
        $previous_product   = $this->payload->get('previous_product')->getOrElse(null);
        $component          = $this->payload->get('component')->getOrElse(null);

        if ($subcription) {
            $this->subscription = new Map($subcription);
        }
        if (!$customer && $this->subscription) {
            $customer = $this->subscription->get('customer')->getOrElse(null);
        }
        if ($customer) {
            $this->customer = new Map($customer);
        }
        if (!$product && $this->subscription) {
            $product = $this->subscription->get('product')->getOrElse(null);
        }
        if ($product) {
            $this->product = new Map($product);
        }
        if ($previous_product) {
            $this->previous_product = new Map($previous_product);
        }
        if ($component) {
            $this->component = new Map($component);
        }
    }

    public function getWebsiteId()
    {
        return $this->websiteId;
    }

    public function getEvent()
    {
        return $this->event;
    }

    public function isSelfServeWebhook()
    {
        if ($this->event == self::EVENT_SIGNUP_SUCCESS) {
            return (strpos($this->getCustomer()->get('reference')->getOrElse(null), 'v2-') !== false);
        }

        return false;
    }

    public function getProductFamilyId()
    {
        $productFamily = $this->previous_product->get('product_family')->getOrThrow(
            new \RuntimeException('Chargify product family is not defined')
        );
        return $productFamily['id'];
    }

    public function getPreviousProductFamilyId()
    {
        if ($this->event == self::EVENT_SUBSCRIPTION_PRODUCT_CHANGE) {
            $productFamily = $this->previous_product->get('product_family')->getOrThrow(
                new \RuntimeException('Previous Chargify product family is not defined')
            );
            return $productFamily['id'];
        }

        return false;
    }

    public function getNewSubscriptionState()
    {
        if (in_array($this->event, [self::EVENT_SUBSCRIPTION_STATE_CHANGE, self::EVENT_SIGNUP_SUCCESS])) {
            return $this->subscription->get('state')->getOrElse(false);
        }

        return false;
    }

    public function getNewSubscriptionPortalName()
    {
        return $this->customer->get('organization')->getOrElse(self::DEFAULT_PORTAL_NAME);
    }

    public function getNewSubscriptionUserEmail()
    {
        return $this->customer->get('email')->getOrThrow(
            new RuntimeException('Chargify customer email is not defined')
        );
    }

    public function getSubscription()
    {
        if ($this->event == self::EVENT_COMPONENT_ALLOCATION_CHANGE) {
            $chargifySubscriptionMapper = $this->getChargifySubscriptionMapper();
            $subscription               = $chargifySubscriptionMapper->getSubscription();
            if ($subscription === null) {
                throw new \RuntimeException(
                    'Subscription with ID ' . $this->getChargifySubscriptionId() . ' not found'
                );
            }

            return $subscription;
        }

        return false;
    }

    public function getNewSubscription()
    {
        if ($this->event == self::EVENT_SIGNUP_SUCCESS) {
            $chargifySubscriptionMapper = $this->getChargifySubscriptionMapper();
            $subscription               = $chargifySubscriptionMapper->getSubscription();
            if ($subscription === null) {
                $subscription = new Subscription();
                $subscription
                    ->setLicense($this->getLicense())
                    ->setState($this->getNewSubscriptionState())
                    ->setChargifyMapper($chargifySubscriptionMapper);
            }

            return $subscription;
        }

        return false;
    }

    public function getNewUsersAllocation()
    {
        if ($this->event == self::EVENT_COMPONENT_ALLOCATION_CHANGE) {
            $chargifyMappers = $this->getSubscription()->getLicense()->getChargifyMappers();
            $componentId = $this->getComponentId();
            /** @var License\ChargifyMapper $chargifyMapper */
            foreach ($chargifyMappers as $chargifyMapper) {
                if (in_array($componentId, $chargifyMapper->getChargifyUsersComponentIds())) {
                    return $this->payload->get('new_allocation')->getOrElse(false);
                }
            }

            return false;
        }
    }

    public function getNewWorkspacesAllocation()
    {
        if ($this->event == self::EVENT_COMPONENT_ALLOCATION_CHANGE) {
            $chargifyMappers = $this->getSubscription()->getLicense()->getChargifyMappers();
            $componentId = $this->getComponentId();
            /** @var License\ChargifyMapper $chargifyMapper */
            foreach ($chargifyMappers as $chargifyMapper) {
                if (in_array($componentId, $chargifyMapper->getChargifyWorkspacesComponentIds())) {
                    return $this->payload->get('new_allocation')->getOrElse(false);
                }
            }

            return false;
        }
    }

    public function getNewStorageAllocation()
    {
        if ($this->event == self::EVENT_COMPONENT_ALLOCATION_CHANGE) {
            $chargifyMappers = $this->getSubscription()->getLicense()->getChargifyMappers();
            $componentId = $this->getComponentId();
            /** @var License\ChargifyMapper $chargifyMapper */
            foreach ($chargifyMappers as $chargifyMapper) {
                if (in_array($componentId, $chargifyMapper->getChargifyStorageComponentIds())) {
                    return $this->payload->get('new_allocation')->getOrElse(false);
                }
            }

            return false;
        }
    }

    private function getComponentId()
    {
        if ($this->component !== null) {
            return $this->component->get('id')->getOrThrow(new \RuntimeException('Component ID is not defined'));
        } else {
            throw new \RuntimeException('Component is not defined');
        }
    }
}
