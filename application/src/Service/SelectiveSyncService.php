<?php

namespace iCoordinator\Service;

use iCoordinator\Entity\Event\FileEvent;
use iCoordinator\Entity\Event\FolderEvent;
use iCoordinator\Entity\File;
use iCoordinator\Entity\SelectiveSync;
use iCoordinator\Entity\User;
use iCoordinator\Service\Exception\NotFoundException;

class SelectiveSyncService extends AbstractService
{
    /**
     * @var EventService
     */
    protected $eventService;

    protected $selSyncData;

    public function getSelectiveSyncFileIds($user, $workspace = null)
    {
        if (is_numeric($user)) {
            $userId = $user;
            /** @var User $user */
            $user = $this->getEntityManager()->getReference(User::ENTITY_NAME, $userId);
        }
        $sql = 'SELECT DISTINCT f.id as file_id, s.id as sel_sync_id FROM iCoordinator\Entity\SelectiveSync s ' .
            'JOIN s.file f JOIN s.created_by u WHERE u.id = ' . $user->getId();
        if ($workspace) {
            $sql .= ' AND f.workspace = ' . $workspace->getId();
        }

        $query = $this->getEntityManager()->createQuery(
            $sql
        );
        $ids = $query->getResult();

        if ($ids) {
            foreach ($ids as $fileId) {
                $selSyncData[$fileId["file_id"]] = $fileId["sel_sync_id"];
            }
        } else {
            $selSyncData = null;
        }
        return $selSyncData;
    }

    public function getSelectiveSync($file, $user, $inheritanceTested = false, &$selSyncData = null)
    {
        if ($file == null) {
            return null;
        }
        if (is_numeric($user)) {
            $userId = $user;
            $user = $this->getEntityManager()->getReference(User::ENTITY_NAME, $userId);
        }
        if (is_numeric($file)) {
            $fileId = $file;
            $file = $this->getEntityManager()->find(File::ENTITY_NAME, $fileId);
            if (!$file) {
                throw new NotFoundException();
            }
        }

        if ($selSyncData == null) {
            $selSyncData = $this->getSelectiveSyncFileIds($user, $file->getWorkspace());
            if ($selSyncData == null) {
                return null;
            }
        }
        $selectiveSync = null;
        if (isset($selSyncData[$file->getId()])) {
            $selectiveSync = new SelectiveSync();
            if (str_contains($selSyncData[$file->getId()], "-inherited")) {
                $selectiveSync->setId(str_replace("-inherited", "", $selSyncData[$file->getId()]));
                $selectiveSync->setInherited(true);
            } else {
                $selectiveSync->setId($selSyncData[$file->getId()]);
            }
            $selectiveSync->setFile($file);
            $selectiveSync->setCreatedBy($user);
        } elseif ($file->getParent() && !$inheritanceTested) {
            /*$selectiveSync = $this->getSelectiveSync($file->getParent(), $user, false, $selSyncData);
            if ($selectiveSync) {
                $selectiveSync->setInherited(true);
            }*/
            if (isset($selSyncData[$file->getParent()->getId()])) {
                $selectiveSync = new SelectiveSync();
                $selectiveSync->setId(str_replace("-inherited", "", $selSyncData[$file->getParent()->getId()]));
                $selectiveSync->setFile($file->getParent());
                $selectiveSync->setCreatedBy($user);
                $selectiveSync->setInherited(true);
            } else {
                $selectiveSync = $this->getSelectiveSync($file->getParent(), $user, false, $selSyncData);
                if ($selectiveSync) {
                    $selectiveSync->setInherited(true);
                    $selSyncData[$file->getId()] = $selectiveSync->getId() . "-inherited";
                }
            }
        }

        /*if (!$selectiveSync) {
            if (isset($selSyncData[$file->getId()])) {
                $selectiveSync = $this->getEntityManager()
                    ->getRepository(SelectiveSync::ENTITY_NAME)
                    ->find($selSyncData[$file->getId()]);

                $selectiveSync = new SelectiveSync();
                $selectiveSync->setId($selSyncData[$file->getId()]);
                $selectiveSync->setFile($file);
                $selectiveSync->setCreatedBy($user);
            }
        }*/
        return $selectiveSync;
    }

    public function setSelectiveSync($file, $user)
    {
        if (is_numeric($user)) {
            $userId = $user;
            /** @var User $user */
            $user = $this->getEntityManager()->getReference(User::ENTITY_NAME, $userId);
        }

        if (is_numeric($file)) {
            $fileId = $file;
            /** @var File $file */
            $file = $this->getEntityManager()->find(File::ENTITY_NAME, $fileId);
            if (!$file) {
                throw new NotFoundException();
            }
        }

        if ($file instanceof SmartFolder) {
            throw new \Exception('Only files and folders can be added to selective sync');
        }
        //$selSyncData = $this->getSelectiveSyncFileIds($user);
        $selSyncData = null;
        $selectiveSync = $this->getSelectiveSync($file, $user, false, $selSyncData);
        if (!$selectiveSync) {
            $selectiveSync = $this->createSelectiveSync($file, $user);
        }

        if ($selectiveSync->getInherited()) {
            throw new \Exception('Not possible to update selective sync settings that are inherited');
        }


        if (!$selectiveSync->getId()) {
            $this->getEntityManager()->persist($selectiveSync);
        }

        if ($this->getAutoCommitChanges()) {
            $this->getEntityManager()->flush();
        }
        //creating event
        if ($file->getType() == 'folder') {
            $this->getEventService()->addEvent(FolderEvent::TYPE_SELECTIVESYNC_CREATE, $file, $user);
        } else {
            $this->getEventService()->addEvent(FileEvent::TYPE_SELECTIVESYNC_CREATE, $file, $user);
        }

        $this->getEntityManager()->flush();

        return $selectiveSync;
    }

    public function deleteSelectiveSync($file, $user)
    {
        if (is_numeric($user)) {
            $userId = $user;
            /** @var User $user */
            $user = $this->getEntityManager()->getReference(User::ENTITY_NAME, $userId);
        }

        if (is_numeric($file)) {
            $fileId = $file;
            /** @var File $file */
            $file = $this->getEntityManager()->find(File::ENTITY_NAME, $fileId);
            if (!$file) {
                throw new NotFoundException();
            }
        }

        $selectiveSync = $this->getEntityManager()->getRepository(SelectiveSync::ENTITY_NAME)->findOneBy(
            array(
            'file' => $file,
            'created_by' => $user
            )
        );

        if ($selectiveSync) {
            $this->getEntityManager()->remove($selectiveSync);
            if ($this->getAutoCommitChanges()) {
                $this->getEntityManager()->flush();
            }
            //creating event
            if ($file->getType() == 'folder') {
                $this->getEventService()->addEvent(FolderEvent::TYPE_SELECTIVESYNC_DELETE, $file, $user);
            } else {
                $this->getEventService()->addEvent(FileEvent::TYPE_SELECTIVESYNC_DELETE, $file, $user);
            }
            $this->getEntityManager()->flush();
        }
    }

    public function hasSelectiveSync($file)
    {

        if (is_numeric($file)) {
            $fileId = $file;
            /** @var File $file */
            $file = $this->getEntityManager()->find(File::ENTITY_NAME, $fileId);
            if (!$file) {
                throw new NotFoundException();
            }
        }

        $selectiveSync = null;
        if ($file->getParent()) {
            $selectiveSync = $this->hasSelectiveSync($file->getParent());
            if ($selectiveSync) {
                $selectiveSync->setInherited(true);
            }
        }

        if (!$selectiveSync) {
            $selectiveSync = $this->getEntityManager()->getRepository(SelectiveSync::ENTITY_NAME)->findOneBy(
                array(
                    'file' => $file
                )
            );
        }

        return $selectiveSync;
    }

    public function getAllSelectiveSync($file)
    {
        $selectiveSync = null;
        if (is_numeric($file)) {
            $fileId = $file;
            /** @var File $file */
            $file = $this->getEntityManager()->find(File::ENTITY_NAME, $fileId);
            if (!$file) {
                throw new NotFoundException();
            }
        }

        if ($file->getParent()) {
            $selectiveSync = $this->hasSelectiveSync($file->getParent());
        }

        $this->allSelectiveSync = $this->getEntityManager()->getRepository(SelectiveSync::ENTITY_NAME)->findBy(
            array(
                'file' => $file
            )
        );


        return $this->allSelectiveSync;
    }

    public function getFileUserIds($file, $type, $userIds = array())
    {

        if (is_numeric($file)) {
            $fileId = $file;
            /** @var File $file */
            $file = $this->getEntityManager()->find(File::ENTITY_NAME, $fileId);
            if (!$file) {
                throw new NotFoundException();
            }
        }
        $allSelectiveSync = $this->getEntityManager()->getRepository(SelectiveSync::ENTITY_NAME)->findBy(
            array(
                'file' => $file
            )
        );
        foreach ($allSelectiveSync as $selectiveSync) {
            $userId = $selectiveSync->getCreatedBy()->getId();
            $userIds[$userId] = $userId;
        }
        if ($file->getParent()) {
            $userIds = $this->getFileUserIds($file->getParent(), $type, $userIds);
        }
        return $userIds;
    }

    private function createSelectiveSync($file, $user)
    {
        $selectiveSync = new SelectiveSync();
        $selectiveSync->setFile($file)
            ->setCreatedBy($user);

        return $selectiveSync;
    }

    /**
     * @return EventService
     */
    public function getEventService()
    {
        if (!$this->eventService) {
            $this->eventService = $this->getContainer()->get('EventService');
        }

        return $this->eventService;
    }
}
