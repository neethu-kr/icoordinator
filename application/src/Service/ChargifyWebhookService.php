<?php

namespace iCoordinator\Service;

use iCoordinator\Chargify\WebhookMapper;

class ChargifyWebhookService extends AbstractService
{
    /**
     * @var WebhookMapper
     */
    private $webhookMapper = null;

    public function validateSignature($signature, $body)
    {
        if ($this->webhookMapper === null) {
            throw new \RuntimeException('Use setData() function before processing webhook');
        }

        $websiteId = $this->webhookMapper->getWebsiteId();
        $sharedKey = $this->getSharedKey($websiteId);

        $signatureCheck = hash_hmac('sha256', $body, $sharedKey);

        return ($signature == $signatureCheck);
    }

    public function setData($data)
    {
        $this->data = $data;
        $this->webhookMapper = new WebhookMapper($data, $this->getEntityManager());
    }

    public function processWebhook()
    {
        if ($this->webhookMapper === null) {
            throw new \RuntimeException('Use setData() function before processing webhook');
        }

        $webhookMapper = $this->webhookMapper;

        switch ($webhookMapper->getEvent()) {
            case WebhookMapper::EVENT_SIGNUP_SUCCESS:
                return $this->signUpSuccessEventHandler($webhookMapper);
                break;
            case WebhookMapper::EVENT_SUBSCRIPTION_STATE_CHANGE:
                return $this->subscriptionStateChangeEventHandler($webhookMapper);
                break;
            case WebhookMapper::EVENT_COMPONENT_ALLOCATION_CHANGE:
                return $this->componentAllocationChangeEventHandler($webhookMapper);
                break;
            case WebhookMapper::EVENT_SUBSCRIPTION_PRODUCT_CHANGE:
                return $this->subscriptionProductChangeEventHandler($webhookMapper);
                break;
        }
    }

    private function signUpSuccessEventHandler(WebhookMapper $webhookMapper)
    {
        if (!$webhookMapper->isSelfServeWebhook()) {
            $userEmail = $webhookMapper->getNewSubscriptionUserEmail();
            $portalName = $webhookMapper->getNewSubscriptionPortalName();

            $data = [
                'user' => [
                    'email' => $userEmail
                ],
                'portal' => [
                    'name' => $portalName
                ]
            ];

            return $this->getSignUpService()->signUp($data, $webhookMapper);
        }

        return null;
    }

    private function subscriptionStateChangeEventHandler(WebhookMapper $webhookMapper)
    {
        $subscription = $webhookMapper->getChargifySubscriptionMapper()->getSubscription();
        if ($subscription) {
            $subscription->setState($webhookMapper->getNewSubscriptionState());
            $this->getEntityManager()->flush();
        }

        return $subscription;
    }

    private function componentAllocationChangeEventHandler(WebhookMapper $webhookMapper)
    {
        $subscription = $webhookMapper->getChargifySubscriptionMapper()->getSubscription();
        if ($subscription) {
            $newUsersAllocation         = $webhookMapper->getNewUsersAllocation($subscription);
            $newWorkspacesAllocation    = $webhookMapper->getNewWorkspacesAllocation($subscription);
            $newStorageAllocation       = $webhookMapper->getNewStorageAllocation($subscription);

            if ($newUsersAllocation) {
                $subscription->setUsersAllocation($newUsersAllocation);
            }
            if ($newWorkspacesAllocation) {
                $subscription->setWorkspacesAllocation($newWorkspacesAllocation);
            }
            if ($newStorageAllocation) {
                $subscription->setStorageAllocation($newStorageAllocation);
            }

            $this->getEntityManager()->flush();
        }

        return $subscription;
    }

    private function subscriptionProductChangeEventHandler(WebhookMapper $webhookMapper)
    {
        $subscription   = $webhookMapper->getChargifySubscriptionMapper()->getSubscription();
        $newLicense     = $webhookMapper->getLicense();

        $subscription->setLicense($newLicense);

        if ($webhookMapper->getPreviousProductFamilyId() != $webhookMapper->getProductFamilyId()) {
            $subscription->setUsersAllocation(null)
                ->setWorkspacesAllocation(null)
                ->setStorageAllocation(null);
        }

        $this->getEntityManager()->flush();

        return $subscription;
    }

    /**
     * @return SignUpService
     */
    private function getSignUpService()
    {
        return $this->getContainer()->get('SignUpService');
    }

    /**
     * @param $websiteId
     * @return mixed
     */
    private function getSharedKey($websiteId)
    {
        $chargifySettings = $this->getContainer()->get('settings')['chargify'];
        if (!isset($chargifySettings[$websiteId])) {
            throw new \InvalidArgumentException('Website ID "' . $websiteId . '" is not found in chargify settings');
        }

        if (!isset($chargifySettings[$websiteId]['shared_key'])) {
            throw new \RuntimeException('Chargify shared key is not set for website "' . $websiteId . '"');
        }

        return $chargifySettings[$websiteId]['shared_key'];
    }
}
