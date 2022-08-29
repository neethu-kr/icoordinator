<?php

namespace iCoordinator\Service;

use Carbon\Carbon;
use Doctrine\ORM\EntityNotFoundException;
use iCoordinator\Entity\File;
use iCoordinator\Entity\Lock;
use iCoordinator\Entity\SmartFolder;
use iCoordinator\Entity\User;
use iCoordinator\Service\Exception\LockedException;
use iCoordinator\Service\Exception\NotFoundException;

class LockService extends AbstractService
{
    public function createLock($file, array $data, $createdBy)
    {
        if (is_numeric($createdBy)) {
            $userId = $createdBy;
            /** @var User $createdBy */
            $createdBy = $this->getEntityManager()->getReference(User::ENTITY_NAME, $userId);
        }

        if (is_numeric($file)) {
            $fileId = $file;
            $file = $this->getEntityManager()->find(File::ENTITY_NAME, $fileId);
            if (!$file) {
                throw new NotFoundException();
            }
        }

        if ($file instanceof SmartFolder) {
            throw new \Exception('Only files and folder can be locked');
        }

        if ($this->isLocked($file)) {
            if ($file->getLock()->getCreatedBy() != $createdBy) {
                $lockUser = $this->getContainer()->get('UserService')->getUser($file->getLock()->getCreatedBy());
                throw new LockedException($lockUser->getName());
            } else {
                throw new LockedException();
            }
        }

        $lock = new Lock();
        $lock->setFile($file)
            ->setCreatedBy($createdBy);
        $file->setLock($lock);

        if (isset($data['expires_at'])) {
            $lock->setExpiresAt(Carbon::parse($data['expires_at']));
        }
    }

    public function isLocked(File $file)
    {
        $lock = $file->getLock();

        if ($lock !== null) {
            if (Carbon::now()->lte($lock->getExpiresAt())) {
                return true;
            } else {
                $this->deleteLockForced($file);
            }
        }

        return false;
    }
    private function getFilesForLock($lock)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('f')
            ->from(File::ENTITY_NAME, 'f')
            ->where('f.lock = :lock')
            ->setParameter('lock', $lock);

        $query = $qb->getQuery();
        return $query->getResult();
    }
    private function getLocksForFile($file)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('l')
            ->from(Lock::ENTITY_NAME, 'l')
            ->where('l.file = :file')
            ->setParameter('file', $file);

        $query = $qb->getQuery();
        return $query->getResult();
    }
    public function deleteLockForced(File $file)
    {
        $lock = $file->getLock();

        if (!$lock) {
            $locks = $this->getLocksForFile($file);
            if (empty($locks)) {
                return true;
            } else {
                foreach ($locks as $lock) {
                    $files = $this->getFilesForLock($lock);
                    foreach ($files as $lockFile) {
                        $lockFile->setLock(null);
                    }
                    $this->getEntityManager()->flush();
                    $lock->setFile(null);
                    try {
                        $this->getEntityManager()->remove($lock);
                    } catch (EntityNotFoundException $e) {
                        throw new NotFoundException();
                    }
                }
            }
            return true;
        }
        // Set lock to null for any files referencing this lock
        $files = $this->getFilesForLock($lock);
        foreach ($files as $lockFile) {
            $lockFile->setLock(null);
        }
        $this->getEntityManager()->flush();
        $lock->setFile(null);
        try {
            $this->getEntityManager()->remove($lock);
        } catch (EntityNotFoundException $e) {
            throw new NotFoundException();
        }
    }

    public function deleteLock(File $file, $deletedBy)
    {
        if (is_numeric($deletedBy)) {
            $userId = $deletedBy;
            /** @var User $deletedBy */
            $deletedBy = $this->getEntityManager()->getReference(User::ENTITY_NAME, $userId);
        }

        if (is_numeric($file)) {
            $fileId = $file;
            $file = $this->getEntityManager()->find(File::ENTITY_NAME, $fileId);
            if (!$file) {
                throw new NotFoundException();
            }
        }

        $this->deleteLockForced($file);
    }
}
