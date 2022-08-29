<?php

namespace iCoordinator\Service;

use Doctrine\ORM\EntityNotFoundException;
use iCoordinator\Entity\EmailNotification\SharedLinkEmailNotification;
use iCoordinator\Entity\File;
use iCoordinator\Entity\HistoryEvent\FileHistoryEvent;
use iCoordinator\Entity\SharedLink;
use iCoordinator\Entity\User;
use iCoordinator\Service\Exception\NotFoundException;
use iCoordinator\Service\Helper\TokenHelper;
use iCoordinator\Service\Helper\UrlHelper;

class SharedLinkService extends AbstractService
{
    const SECRET = 'NnXBYW';

    /**
     * @var OutboundEmailService
     */
    private $outboundEmailService;

    /**
     * @var HistoryEventService
     */
    private $historyEventService;

    /**
     * @param $sharedLinkId
     * @return null|SharedLink
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     */
    public function getSharedLink($sharedLinkId)
    {
        $sharedLinkId = $this->getEntityManager()->find(SharedLink::ENTITY_NAME, $sharedLinkId);
        return $sharedLinkId;
    }

    /**
     * @param $token
     * @return null|SharedLink
     */
    public function getSharedLinkByToken($token)
    {
        $sharedLink = $this->getEntityManager()->getRepository(SharedLink::ENTITY_NAME)->findOneBy(array(
            'token' => $token
        ));

        return $sharedLink;
    }

    public function updateSharedLink(SharedLink $sharedLink, array $data, User $udatedBy)
    {
        $this->getHistoryEventService()->addEvent(
            FileHistoryEvent::TYPE_FILE_SHARED_LINK_UPDATE,
            $sharedLink->getFile(),
            $udatedBy,
            $sharedLink->getFile()->getName().':'.$sharedLink->getAccessType().' -> '.$data['access_type']
        );
        $sharedLink->setAccessType($data['access_type'])
            ->setToken($this->generateToken($sharedLink->getFile()))
            ->setCreatedBy($udatedBy);
        return $sharedLink;
    }

    public function createSharedLink(File $file, array $data, User $createdBy)
    {
        $sharedLink = new SharedLink();
        $sharedLink->setAccessType($data['access_type'])
            ->setToken($this->generateToken($file))
            ->setFile($file)
            ->setCreatedBy($createdBy);
        $file->setSharedLink($sharedLink);
        $this->getHistoryEventService()->addEvent(
            FileHistoryEvent::TYPE_FILE_SHARED_LINK_CREATE,
            $file,
            $createdBy,
            $file->getName().':'.$data['access_type']
        );
    }

    private function generateToken(File $file)
    {
        $token = TokenHelper::getSecureToken();
        $sharedLink = $this->getEntityManager()->getRepository(SharedLink::ENTITY_NAME)->findOneBy(array(
            'token' => $token
        ));
        if ($sharedLink) {
            return $this->generateToken($file);
        }
        return $token;
    }

    /**
     * @param File $file
     * @param User $deletedBy
     * @return bool
     * @throws NotFoundException
     */
    public function deleteSharedLink(File $file, User $deletedBy)
    {
        $sharedLink = $file->getSharedLink();

        if (!$sharedLink) {
            return true;
        }
        $this->getHistoryEventService()->addEvent(
            FileHistoryEvent::TYPE_FILE_SHARED_LINK_DELETE,
            $file,
            $deletedBy,
            $file->getName().':'.$sharedLink->getAccessType()
        );
        $file->setSharedLink(null);

        try {
            $this->getEntityManager()->remove($sharedLink);
        } catch (EntityNotFoundException $e) {
            throw new NotFoundException();
        }
    }

    public function getSharedLinkUrl($sharedLink)
    {
        if (is_numeric($sharedLink)) {
            $sharedLinkId = $sharedLink;
            /** @var SharedLink $sharedLink */
            $sharedLink = $this->getEntityManager()->getReference(SharedLink::ENTITY_NAME, $sharedLinkId);
        }

        return UrlHelper::getSharedLinkUrl($this->getContainer(), $sharedLink);
    }
    public function sendSharedLinkNotification($sharedLink, $data, $sender)
    {
        if (is_numeric($sharedLink)) {
            $sharedLinkId = $sharedLink;
            /** @var SharedLink $sharedLink */
            $sharedLink = $this->getEntityManager()->getReference(SharedLink::ENTITY_NAME, $sharedLinkId);
        }

        if (empty($data['emails'])) {
            throw new \Exception('Emails param is not set');
        }

        $emailNotification = new SharedLinkEmailNotification();
        $emailNotification
            ->setEmails($data['emails'])
            ->setSuccessfulEmails($data['successful_emails'])
            ->setFailedEmails($data['failed_emails']);

        if (!empty($data['message'])) {
            $emailNotification->setMessage($data['message']);
        }
        $outboundEmailService = $this->getOutboundEmailService();
        $sharedLinkUrl = UrlHelper::getSharedLinkUrl($this->getContainer(), $sharedLink);
        $userService = $this->getContainer()->get('UserService');
        foreach ($emailNotification->getSuccessfulEmails() as $email) {
            $user = $userService->getUserByEmail($email);
            if ($user) {
                $outboundEmailService->setLang($user->getLocale()->getLang());
            } else {
                $outboundEmailService->setLang('en');
            }
            $outboundEmailService->setTo($email);
            $outboundEmailService->sendSharedLinkNotification(
                $sharedLink,
                $sharedLinkUrl,
                $sender,
                $emailNotification->getMessage()
            );
            $outboundEmailService->setSubject('');
        }

        return $emailNotification;
    }

    public function sendMultipleSharedLinkNotification($data, $sender)
    {

        if (empty($data['emails'])) {
            throw new \Exception('Emails param is not set');
        }

        $emailNotification = new SharedLinkEmailNotification();
        $emailNotification
            ->setEmails($data['emails'])
            ->setSuccessfulEmails($data['successful_emails'])
            ->setFailedEmails($data['failed_emails'])
            ->setFailedIds($data['failed_ids']);

        if (!empty($data['message'])) {
            $emailNotification->setMessage($data['message']);
        }
        $sharedLinkUrls = "";
        $root = false;
        $outboundEmailService = $this->getOutboundEmailService();
        foreach ($data['all_ids'] as $sharedLinkId) {
            $sharedLink = $this->getEntityManager()->getReference(SharedLink::ENTITY_NAME, $sharedLinkId);
            $parent = $sharedLink->getFile()->getParent();
            if ($parent == null) {
                $root = true;
                $parent = $sharedLink->getFile()->getWorkspace();
            }
            $sharedLinkUrls .= '<a href="'.UrlHelper::getSharedLinkUrl($this->getContainer(), $sharedLink).
                '">'.$sharedLink->getFile()->getName().'</a><br>';
        }
        $userService = $this->getContainer()->get('UserService');
        foreach ($emailNotification->getSuccessfulEmails() as $email) {
            $user = $userService->getUserByEmail($email);
            if ($user) {
                $outboundEmailService->setLang($user->getLocale()->getLang());
            } else {
                $outboundEmailService->setLang('en');
            }
            $outboundEmailService->setTo($email);
            $outboundEmailService->sendMultipleSharedLinkNotification(
                $root,
                $parent,
                $sharedLinkUrls,
                $sender,
                $emailNotification->getMessage()
            );
            $outboundEmailService->setSubject('');
        }

        return $emailNotification;
    }

    /**
     * @return OutboundEmailService
     */
    private function getOutboundEmailService()
    {
        if (!$this->outboundEmailService) {
            $this->outboundEmailService = $this->getContainer()->get('OutboundEmailService');
        }

        return $this->outboundEmailService;
    }

    /**
     * @return HistoryEventService
     */
    public function getHistoryEventService()
    {
        if (!$this->historyEventService) {
            $this->historyEventService = $this->getContainer()->get('HistoryEventService');
        }

        return $this->historyEventService;
    }
}
