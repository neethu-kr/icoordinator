<?php

namespace iCoordinator\Service;

use Doctrine\ORM\Tools\Pagination\Paginator;
use iCoordinator\Entity\EventNotification;
use iCoordinator\Entity\EventNotification\FileEventNotification;
use iCoordinator\Entity\File;
use iCoordinator\Entity\User;
use iCoordinator\Permissions\Privilege\FilePrivilege;
use iCoordinator\Permissions\Role\UserRole;
use iCoordinator\Service\Exception\ValidationFailedException;

class EventNotificationService extends AbstractService
{
    const CRLF = '<br>';

    /**
     * @var FileService
     */
    private $fileService;

    /**
     * @var FileEmailOptionsService
     */
    private $fileEmailOptionsService;

    /**
     * @var UserService
     */
    private $userService;

    /**
     * @var WorkspaceService
     */
    private $workspaceService;

    /**
     * @var PortalService
     */
    private $portalService;

    /**
     * @var GroupService
     */
    private $groupService;

    /**
     * @var PermissionService
     */
    private $permissionService;

    /**
     * @var OutboundEmailService
     */
    private $outboundEmailService;

    /**
     * @return Acl
     */
    public function getAcl()
    {
        return $this->getContainer()->get('acl');
    }

    public function sendEventNotifications($instant_notification = false)
    {
        global $langArray;
        $acl = $this->getAcl();
        $privilege = FilePrivilege::PRIVILEGE_READ;
        $serverBrand = getenv('BRAND');
        $eventNotificationUsers = $this->getAllEventNotificationsUsers($serverBrand, $instant_notification);
        foreach ($eventNotificationUsers as $ukey => $eventNotificationUser) {
            $brand = '';
            $userNotificationEmail = '';
            $previousPortal = null;
            $previousWorkspace = null;
            $previousFile = null;
            $user = $this->getUserService()->getUser($eventNotificationUser[1]);
            $role = new UserRole($user);
            $eventNotifications = $this->getUserEventNotifications(
                $eventNotificationUser,
                $serverBrand,
                $instant_notification
            );
            $settings = $this->getContainer()->get('settings');

            $config = $settings['email'];
            $locale_path = $settings['applicationPath'] . $config['locale_path'];

            foreach ($eventNotifications->getIterator() as $key => $eventNotification) {
                $brand = $eventNotification->getBrand();
                if ($brand == $serverBrand) {
                    $source = $eventNotification->getSource();
                    $portal = $eventNotification->getPortal();
                    $workspace = $eventNotification->getWorkspace();
                    if ($source != null && $acl->isAllowed($role, $source, $privilege)) {
                        if ($key == 0 || $portal != $previousPortal) {
                            if ($user) {
                                if ($user->getLocale()) {
                                    $userLang = $user->getLocale()->getLang();
                                } else {
                                    $userLang = 'en';
                                }
                            } else {
                                $userLang = 'en';
                            }
                            include $locale_path . DIRECTORY_SEPARATOR . 'lang.' . ($userLang != '' ? $userLang : 'en');
                            //Add portal information
                            $userNotificationEmail .= $this::CRLF . $this::CRLF .
                                '<font style="font-size:14px"><b>' . $langArray["portal"] . ': ' .
                                $this->getPortalInformation($eventNotification) .
                                '</b></font>' . $this::CRLF;
                            $previousPortal = $portal;
                        }
                        if ($key == 0 || $workspace != $previousWorkspace) {
                            //Add workspace information
                            $userNotificationEmail .= $this::CRLF . '<font style="font-size:12px"><b>' .
                                $langArray["workspace"] . ': ' . $this->getWorkspaceInformation($eventNotification) .
                                '</b></font>' . $this::CRLF;
                            $previousWorkspace = $workspace;
                        }
                        if ($key == 0 || $previousFile == null || $source->getId() != $previousFile->getId()) {
                            //Add file information
                            $userNotificationEmail .= $this::CRLF .
                                substr($this->getFilePath($source), 1) . $this::CRLF;
                            $previousFile = $source;
                        }
                        $userNotificationEmail .= $this->getFileInformation($eventNotification, $langArray)
                            . $this::CRLF;
                    }
                }
            }
            if ($userNotificationEmail != '') {
                $this->getOutboundEmailService()
                    ->setTo($user->getEmail())
                    ->setLang($userLang)
                    ->sendEventNotification($user, $userNotificationEmail, $brand);
            }
            $this->removeUserEventNotifications($eventNotificationUser, $serverBrand, $instant_notification);
        }
    }
    private function getPortalInformation($eventNotification)
    {
        return $eventNotification->getPortal()->getName();
    }
    private function getWorkspaceInformation($eventNotification)
    {
        return $eventNotification->getWorkspace()->getName();
    }
    private function getFilePath($file)
    {
        $pathInfo = '';
        if ($file->getParent() != null) {
            $pathInfo = $this->getFilePath($file->getParent());
        }
        return $pathInfo . '/' . $file->getName();
    }
    private function getFileInformation($eventNotification, $langArray)
    {
        $suffix = $eventNotification->getCreatedBy()->getName() .
            ', ' . $eventNotification->getCreatedAt() . ' (GMT)';
        switch ($eventNotification->getType()) {
            case FileEventNotification::TYPE_CREATE:
                return '<i>&nbsp;&nbsp;&nbsp;' . $langArray["file_uploaded_by"] . ' ' . $suffix . '</i>';
                break;
            case FileEventNotification::TYPE_UPDATE:
                return '<i>&nbsp;&nbsp;&nbsp;' . $langArray["file_new_version_uploaded_by"] . ' ' . $suffix . '</i>';
                break;
            case FileEventNotification::TYPE_DELETE:
                return '<i>&nbsp;&nbsp;&nbsp;' . $langArray["file_deleted_by"] . ' ' . $suffix . '</i>';
                break;
            default:
                break;
        }
    }
    public function getAllEventNotificationsUsers($brand, $instant_notification)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select(('IDENTITY(e.user)'))
            ->from(EventNotification::ENTITY_NAME, 'e')
            ->andWhere('e.brand = :brand')
            ->andWhere('e.instant_notification = :instant_notification')
            ->setParameter('brand', $brand)
            ->setParameter('instant_notification', $instant_notification)
            ->groupBy('e.user');

        $query = $qb->getQuery();
        $paginator = new Paginator($query, false);

        return $paginator;
    }

    public function getAllEventNotifications()
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select(array('e'))
            ->from(EventNotification::ENTITY_NAME, 'e')
            ->orderBy('e.user_id', 'ASC')
            ->orderBy('e.portal_id', 'ASC')
            ->orderBy('e.workspace_id', 'ASC')
            ->orderBy('e.source_id', 'ASC')
            ->orderBy('e.id', 'ASC');


        $query = $qb->getQuery();
        $paginator = new Paginator($query, false);

        return $paginator;
    }

    public function getUserEventNotifications($user, $brand, $instant_notification = false)
    {
        if (is_numeric($user)) {
            $userId = $user;
            /** @var User $user */
            $user = $this->getEntityManager()->getReference(User::ENTITY_NAME, $userId);
        }

        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select(array('e'))
            ->from(EventNotification::ENTITY_NAME, 'e')
            ->andWhere('e.user = :user')
            ->andWhere('e.brand = :brand')
            ->andWhere('e.instant_notification = :instant_notification')
            ->setParameter('user', $user)
            ->setParameter('brand', $brand)
            ->setParameter('instant_notification', $instant_notification)
            ->orderBy('e.portal_id', 'ASC')
            ->orderBy('e.workspace_id', 'ASC')
            ->orderBy('e.source_id', 'ASC')
            ->orderBy('e.id', 'ASC');


        $query = $qb->getQuery();
        $paginator = new Paginator($query, false);

        return $paginator;
    }

    public function removeUserEventNotifications($user, $brand, $instant_notification)
    {
        if (is_numeric($user)) {
            $userId = $user;
            /** @var User $user */
            $user = $this->getEntityManager()->getReference(User::ENTITY_NAME, $userId);
        }

        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->delete()
            ->from(EventNotification::ENTITY_NAME, 'e')
            ->where('e.user = :user')
            ->andWhere('e.brand = :brand')
            ->andWhere('e.instant_notification = :instant_notification')
            ->setParameter('user', $user)
            ->setParameter('brand', $brand)
            ->setParameter('instant_notification', $instant_notification);

        $query = $qb->getQuery();
        $numDeleted = $query->execute();
    }

    /**
     * @param $type
     * @param $source
     * @param $createdBy
     * @param null $userIds
     * @return EventNotification\FileEventNotification
     * @throws ValidationFailedException
     * @throws \Doctrine\ORM\ORMException
     */
    public function addEventNotification($type, $source, $createdBy, $userIds = null)
    {
        switch (true) {
            case (in_array($type, EventNotification\FileEventNotification::getEventNotificationTypes())):
                $eventNotification = new EventNotification\FileEventNotification();
                break;

            default:
                throw new ValidationFailedException('Event notification with type "' . $type . '" not found');
        }

        $eventNotification->setType($type)
            ->setWorkspace($source->getWorkspace())
            ->setPortal($source->getWorkspace()->getPortal())
            ->setSource($source)
            ->setCreatedBy($createdBy)
            ->setBrand($this->getBrand());


        //if user ids are not specified - define them automatically
        if (empty($userIds)) {
            $userIds = $this->getEventNotificationSourceUserIds($source, $type);
        } elseif (!is_array($userIds)) {
            $userIds = array($userIds);
        }

        if (!empty($userIds)) {
            foreach ($userIds as $userId) {
                $newEventNotification = clone $eventNotification;
                $user = $this->getEntityManager()->getReference(User::getEntityName(), $userId);
                $newEventNotification->setUser($user);
                $newEventNotification->setInstantNotification($user->getInstantNotification());

                $this->getEntityManager()->persist($newEventNotification);
            }
        }

        return $eventNotification;
    }

    private function getEventNotificationSourceUserIds($source, $type)
    {
        $userIds = array();
        switch (true) {
            case ($source instanceof File):
                $userIds = $this->getFileEmailOptionsService()->getFileUserIds($source, $type);
                break;
            default:
                throw new ValidationFailedException('Unknown event source type - "' . get_class($source) . '"');
        }

        return $userIds;
    }

    public function removeNotificationsForWorkspace($workspace)
    {
        $em = $this->getEntityManager();
        $pdo = $em->getConnection();
        $sql = 'DELETE FROM event_notifications where workspace_id='.$workspace->getId();

        $stmt = $pdo->prepare($sql);
        $stmt->execute();
    }
    private function getBrand()
    {
        return getenv('BRAND');
    }

    /**
     * @return UserService
     */
    public function getUserService()
    {
        if (!$this->userService) {
            $this->userService = $this->getContainer()->get('UserService');
        }
        return $this->userService;
    }
    /**
     * @return PortalService
     */
    public function getPortalService()
    {
        if (!$this->portalService) {
            $this->portalService = $this->getContainer()->get('PortalService');
        }
        return $this->portalService;
    }

    /**
     * @return WorkspaceService
     */
    public function getWorkspaceService()
    {
        if (!$this->workspaceService) {
            $this->workspaceService = $this->getContainer()->get('WorkspaceService');
        }
        return $this->workspaceService;
    }

    /**
     * @return FileService
     */
    public function getFileService()
    {
        if (!$this->fileService) {
            $this->fileService = $this->getContainer()->get('FileService');
        }
        return $this->fileService;
    }


    /**
     * @return PermissionService
     */
    protected function getPermissionService()
    {
        if (!$this->permissionService) {
            $this->permissionService = $this->getContainer()->get('PermissionService');
        }
        return $this->permissionService;
    }

    /**
     * @return FileEmailOptionsService
     */
    public function getFileEmailOptionsService()
    {
        if (!$this->fileEmailOptionsService) {
            $this->fileEmailOptionsService = $this->getContainer()->get('FileEmailOptionsService');
        }
        return $this->fileEmailOptionsService;
    }

    /**
     * @return OutboundEmailService
     */
    protected function getOutboundEmailService()
    {
        if (!$this->outboundEmailService) {
            $this->outboundEmailService = $this->getContainer()->get('OutboundEmailService');
        }
        return $this->outboundEmailService;
    }
}
