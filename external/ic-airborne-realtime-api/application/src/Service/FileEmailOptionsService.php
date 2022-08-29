<?php

namespace iCoordinator\Service;

use iCoordinator\Entity\EventNotification\FileEventNotification;
use iCoordinator\Entity\File;
use iCoordinator\Entity\FileEmailOptions;
use iCoordinator\Entity\SmartFolder;
use iCoordinator\Entity\User;
use iCoordinator\Service\Exception\NotFoundException;

class FileEmailOptionsService extends AbstractService
{
    /**
     * @var $userIds
     */
    private $userIds = array();

    /**
     * @var $allEmailOptions
     */
    private $allEmailOptions = array();

    public function setFileEmailOptions($file, array $data, $user)
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
            throw new \Exception('Only files and folders can have email options');
        }

        $emailOptions = $this->getFileEmailOptions($file, $user);
        if (!$emailOptions) {
            $emailOptions = $this->createFileEmailOptions($file, $user);
        }

        if ($emailOptions->isInherited()) {
            throw new \Exception('Not possible to update inherited email options');
        }

        if (array_key_exists('download_notification', $data)) {
            $emailOptions->setDownloadNotification($data['download_notification']);
        }
        if (array_key_exists('upload_notification', $data)) {
            $emailOptions->setUploadNotification($data['upload_notification']);
        }
        if (array_key_exists('delete_notification', $data)) {
            $emailOptions->setDeleteNotification($data['delete_notification']);
        }

        if (!$emailOptions->getId()) {
            $this->getEntityManager()->persist($emailOptions);
        } elseif (!$emailOptions->isDownloadNotification() &&
            !$emailOptions->isUploadNotification() &&
            !$emailOptions->isDeleteNotification()) {
            $this->getEntityManager()->remove($emailOptions);
        }

        if ($this->getAutoCommitChanges()) {
            $this->getEntityManager()->flush();
        }

        return $emailOptions;
    }

    public function getFileEmailOptionsFileIds($user)
    {
        if (is_numeric($user)) {
            $userId = $user;
            /** @var User $user */
            $user = $this->getEntityManager()->getReference(User::ENTITY_NAME, $userId);
        }

        $query = $this->getEntityManager()->createQuery(
            'SELECT DISTINCT f.id FROM iCoordinator\Entity\FileEmailOptions feo ' .
            'JOIN feo.file f JOIN feo.user u WHERE u.id = ' . $user->getId()
        );
        $ids = $query->getResult();

        if ($ids) {
            foreach ($ids as $fileId) {
                $fileEmailOptionsData[$fileId["id"]] = $fileId["id"];
            }
        } else {
            $fileEmailOptionsData = null;
        }
        return $fileEmailOptionsData;
    }

    public function getFileEmailOptions($file, $user, $inheritanceTested = false, $fileEmailOptionsData = null)
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

        if ($fileEmailOptionsData == null) {
            $fileEmailOptionsData = $this->getFileEmailOptionsFileIds($user);
            if ($fileEmailOptionsData == null) {
                return null;
            }
        }

        $emailOptions = null;
        if (!$inheritanceTested && $file->getParent()) {
            $emailOptions = $this->getFileEmailOptions($file->getParent(), $user, false, $fileEmailOptionsData);
            if ($emailOptions) {
                $emailOptions->setInherited(true);
            }
        }

        if (!$emailOptions) {
            if (isset($fileEmailOptionsData[$file->getId()])) {
                $emailOptions = $this->getEntityManager()->getRepository(FileEmailOptions::ENTITY_NAME)->findOneBy(
                    array(
                        'file' => $file,
                        'user' => $user
                    )
                );
            }
        }

        return $emailOptions;
    }

    public function hasFileEmailOptions($file)
    {

        if (is_numeric($file)) {
            $fileId = $file;
            /** @var File $file */
            $file = $this->getEntityManager()->find(File::ENTITY_NAME, $fileId);
            if (!$file) {
                throw new NotFoundException();
            }
        }

        $emailOptions = null;
        if ($file->getParent()) {
            $emailOptions = $this->hasFileEmailOptions($file->getParent());
            if ($emailOptions) {
                $emailOptions->setInherited(true);
            }
        }

        if (!$emailOptions) {
            $emailOptions = $this->getEntityManager()->getRepository(FileEmailOptions::ENTITY_NAME)->findOneBy(
                array(
                    'file' => $file
                )
            );
        }

        return $emailOptions;
    }

    public function getAllFileEmailOptions($file)
    {
        $emailOptions = null;
        if (is_numeric($file)) {
            $fileId = $file;
            /** @var File $file */
            $file = $this->getEntityManager()->find(File::ENTITY_NAME, $fileId);
            if (!$file) {
                throw new NotFoundException();
            }
        }

        if ($file->getParent()) {
            $emailOptions = $this->hasFileEmailOptions($file->getParent());
        }

        $this->allEmailOptions = $this->getEntityManager()->getRepository(FileEmailOptions::ENTITY_NAME)->findBy(
            array(
                'file' => $file
            )
        );


        return $this->allEmailOptions;
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
        switch ($type) {
            case FileEventNotification::TYPE_CREATE:
                $conditions = array(
                    'file' => $file,
                    'upload_notification' => 1
                );
                break;
            case FileEventNotification::TYPE_UPDATE:
                $conditions = array(
                    'file' => $file,
                    'upload_notification' => 1
                );
                break;
            case FileEventNotification::TYPE_DELETE:
                $conditions = array(
                    'file' => $file,
                    'delete_notification' => 1
                );
                break;
            default:
                $conditions = array(
                    'file' => $file
                );
                break;
        }
        $emailOptions = $this->getEntityManager()->getRepository(FileEmailOptions::ENTITY_NAME)->findBy(
            $conditions
        );
        foreach ($emailOptions as $emailOption) {
            $userId = $emailOption->getUser()->getId();
            $userIds[$userId] = $userId;
        }
        if ($file->getParent()) {
            $userIds = $this->getFileUserIds($file->getParent(), $type, $userIds);
        }
        return $userIds;
    }

    private function createFileEmailOptions($file, $user)
    {
        $emailOptions = new FileEmailOptions();
        $emailOptions->setFile($file)
            ->setUser($user);

        return $emailOptions;
    }
}
