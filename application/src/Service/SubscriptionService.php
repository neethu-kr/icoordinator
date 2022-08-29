<?php

namespace iCoordinator\Service;

use iCoordinator\Entity\Portal;
use iCoordinator\Entity\Subscription;

class SubscriptionService extends AbstractService
{
    const GB =  1073741824;

    public function getPortalSubscription($portal)
    {
        if (is_numeric($portal)) {
            $portalId = $portal;
            /** @var Portal $portal */
            $portal = $this->getEntityManager()->getReference(Portal::ENTITY_NAME, $portalId);
        }

        $subscriptionRepository = $this->getEntityManager()->getRepository(Subscription::ENTITY_NAME);
        $subscription = $subscriptionRepository->findOneBy([
            'portal' => $portal
        ]);

        return $subscription;
    }

    public function checkCanAddWorkspace($portal)
    {
        $portalSubscription = $this->getPortalSubscription($portal);
        if ($portalSubscription != null) {
            $workspaceCount = count($this->getContainer()->get('WorkspaceService')->getWorkspaces($portal, null));
            $workspacesAllocation = $portalSubscription->getWorkspacesAllocation();
            return !($workspaceCount >= $workspacesAllocation);
        }
        return true;
    }

    public function checkCanInviteUser($portal)
    {

        $portalSubscription = $this->getPortalSubscription($portal);
        if ($portalSubscription != null) {
            $usersCount = count($this->getContainer()->get('PortalService')->getPortalUsers($portal));
            $usersCount += count($this->getContainer()->get('SignUpService')->getPortalInvitations($portal));
            $usersAllocation = $portalSubscription->getUsersAllocation();
            return $usersCount < $usersAllocation;
        }
        return true;
    }

    public function checkCanAddUser($portal)
    {

        $portalSubscription = $this->getPortalSubscription($portal);
        if ($portalSubscription != null) {
            $usersCount = count($this->getContainer()->get('PortalService')->getPortalUsers($portal));
            $usersCount += count($this->getContainer()->get('SignUpService')->getPortalInvitations($portal));
            $usersAllocation = $portalSubscription->getUsersAllocation();
            return $usersCount <= $usersAllocation;
        }
        return true;
    }

    public function checkCanAddFile($size, $portal)
    {
        $portalSubscription = $this->getPortalSubscription($portal);
        if ($portalSubscription != null) {
            // Disabling quota check for performance reasons
            /*$usedStorage = $this->getContainer()->get('FileService')->getUsedStorage($portal);
            $storageAllocation = $portalSubscription->getStorageAllocation();
            return ($usedStorage + $size) < ($storageAllocation * 1073741824);
            */
        }
        return true;
    }

    public function checkFileSizeOK($size, $portal)
    {
        $portalSubscription = $this->getPortalSubscription($portal);
        if ($portalSubscription != null) {
            return $size <= ($portalSubscription->getLicense()->getFileSizeLimit() * self::GB);
        }
        return true;
    }
}
