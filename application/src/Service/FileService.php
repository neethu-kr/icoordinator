<?php

namespace iCoordinator\Service;

use Carbon\Carbon;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\Tools\Pagination\Paginator;
use iCoordinator\Entity\Acl\AclResource\AclFileResource;
use iCoordinator\Entity\Event\FileEvent;
use iCoordinator\Entity\EventNotification\FileEventNotification;
use iCoordinator\Entity\File;
use iCoordinator\Entity\FileUpload;
use iCoordinator\Entity\FileVersion;
use iCoordinator\Entity\Folder;
use iCoordinator\Entity\HistoryEvent\FileHistoryEvent;
use iCoordinator\Entity\User;
use iCoordinator\Entity\Workspace;
use iCoordinator\File\Downloader;
use iCoordinator\File\Encryptor;
use iCoordinator\File\Storage\StorageFactory;
use iCoordinator\File\Storage\StorageInterface;
use iCoordinator\File\Uploader;
use iCoordinator\Permissions\BitMask;
use iCoordinator\Permissions\PermissionType;
use iCoordinator\Service\Exception\ConflictException;
use iCoordinator\Service\Exception\NotFoundException;
use iCoordinator\Service\Exception\NotTrashedException;
use iCoordinator\Service\Exception\ValidationFailedException;
use iCoordinator\Service\Helper\FileServiceHelper;
use iCoordinator\Service\Helper\TokenHelper;
use Laminas\Hydrator\ClassMethodsHydrator;
use Slim\Http\UploadedFile;
use Symfony;
use Upload\FileInfo;

/**
 * Class FileService
 * @package iCoordinator\Service
 */
class FileService extends DriveItemService
{
    const FILES_LIMIT_DEFAULT = 100;

    const FILE_VERSIONS_LIMIT_DEFAULT = 100;

    /**
     * @var Encryptor
     */
    private $encryptor;

    /**
     * @var StorageInterface
     */
    private $fileStorage;

    /**
     * @var StorageInterface
     */
    private $uploadsStorage;

    private function hashFile($fileName, $length)
    {
        $hash = "";
        $ctx = hash_init('sha256');
        $fd = fopen($fileName, 'rb');
        $totalSize = 0;
        if ($fd) {
            while (!feof($fd)) {
                $buffer = fread($fd, 1048576);
                $bufferSize = strlen($buffer);
                $totalSize += $bufferSize;
                if ($totalSize > $length) {
                    $adjustedBufferSize = $bufferSize - ($totalSize - $length);
                    $buffer = substr($buffer, 0, $adjustedBufferSize);
                }
                hash_update($ctx, $buffer);
            }
            fclose($fd);
            $hash = hash_final($ctx, false);
        }
        return $hash;
    }
    /**
     * @param $file
     * @return array
     */
    public function getFileUsers($file)
    {
        return $this->getDriveItemUsers($file);
    }

    /**
     * @param $file
     * @return array
     */
    public function getFileUserIds($file)
    {
        return $this->getDriveItemUserIds($file);
    }

    /**
     * @param $user
     * @param $file
     * @return boolean
     */
    public function userIsAllowed($user, $file)
    {
        return $this->isAllowed($user, $file);
    }
    /**
     * @param $fileUploadId
     * @return FileUpload
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     */
    public function getFileUpload($fileUploadId)
    {
        return $this->getEntityManager()->find(FileUpload::ENTITY_NAME, $fileUploadId);
    }

    /**
     * @param array $data
     * @return FileUpload
     */
    public function fileUploadCreate(array $data, $createdBy)
    {
        if (is_numeric($createdBy)) {
            $userId = $createdBy;
            /** @var User $createdBy */
            $createdBy = $this->getEntityManager()->getReference(User::ENTITY_NAME, $userId);
        }

        $fileUpload = new FileUpload();
        $fileUpload
            ->setId(TokenHelper::getSecureToken())
            ->setOffset(0)
            ->setCreatedBy($createdBy);

        if (isset($data['hash'])) {
            $fileUpload->setHash($data['hash']);
        }
        if (isset($data['expires'])) {
            $fileUpload->setExpiresAt(Carbon::parse($data['expires']));
        }

        $this->getEntityManager()->persist($fileUpload);
        $this->getEntityManager()->flush();

        return $fileUpload;
    }

    /**
     * @param FileUpload $fileUpload
     * @param UploadedFile $file
     * @param int $offset
     * @return FileUpload
     * @throws ValidationFailedException
     */
    public function uploadFileChunk(FileUpload $fileUpload, UploadedFile $file, $offset = 0)
    {
        if ($offset > $fileUpload->getOffset()) {
            throw new ValidationFailedException('Incorrect offset: ' . $offset . "!=" . $fileUpload->getOffset());
        }

        $chunkSize      = $file->getSize();
        $uploadsStorage = $this->getUploadsStorage();

        $fp = $uploadsStorage->fopen($fileUpload->getId().DIRECTORY_SEPARATOR.$fileUpload->getId().'_'.$offset, 'wb');

        fwrite($fp, stream_get_contents($file->getStream()->detach()), $chunkSize);
        fclose($fp);

        $fileUpload->setOffset($offset + $chunkSize);
        $this->getEntityManager()->flush();
        // Remove chunk from temporary upload directory
        //unlink($file->file);

        return $fileUpload;
    }

    public function finishUpload(
        $fileId,
        $uploadId,
        $userId,
        $client_version,
        $etag = "",
        $oldHash = "",
        $oldContentModifiedAt = ""
    ) {
        $createdBy = $this->getEntityManager()->getReference(User::ENTITY_NAME, $userId);
        $file = $this->getEntityManager()->getReference(File::ENTITY_NAME, $fileId);
        $fileUpload = $this->getFileUpload($uploadId);
        $filePath = $this->getUploadsStorage()->getStreamUrl(
            $fileUpload->getId().DIRECTORY_SEPARATOR.$fileUpload->getId()
        );
        $uploader = $this->createMultiUploader(dirname($filePath), $fileUpload->getId());
        $clientHash = $fileUpload->getHash();
        //deleting file upload if exists in db

        $_SERVER["HTTP_CLIENT_VERSION"] = $client_version;
        //$hash = $this->hashFile($filePath, $uploader->getFileInfo()->getSize());

        //uploading to file storage
        $hash = $this->uploadFileVersionToStorage($file->getVersion(), $uploader);
        if ($fileUpload != null) {
            $this->getEntityManager()->remove($fileUpload);
        }

        if ($clientHash != null && $clientHash != $hash) {
            // Hash differs, invalidate file upload
            $uploader->delete();
            if ($etag == "" || $etag == 1) {
                // New file
                $fileVersion = $file->getVersion();
                $fileVersion->setFile(null);
                $this->getEntityManager()->flush();
                $this->getEntityManager()->remove($fileVersion);
                //$file = $this->getEntityManager()->getReference(File::ENTITY_NAME, $fileId);
                $file->setVersion(null);
                $this->getEntityManager()->flush();
                $this->getEntityManager()->remove($file);
            } else {
                // Existing file, new version
                $fileVersion = $file->getVersion();
                $fileVersion->setFile(null);
                $this->getEntityManager()->flush();
                $this->getEntityManager()->remove($fileVersion);
                $fvArray = $this->getLatestFileVersion($file->getId());
                $fileVersion = $this->getEntityManager()->getReference(FileVersion::ENTITY_NAME, $fvArray['id']);
                $file->setVersion($fileVersion);
                $file->setIsUploading(false);
                $file->setEtag($etag - 1);
                $file->setSize($fileVersion->getSize());
                $file->setHash($oldHash);
                $file->setContentModifiedAt(
                    $oldContentModifiedAt ?
                        Carbon::parse(gmdate('Y-m-d H:i:s', strtotime(base64_decode($oldContentModifiedAt)))) :
                        Carbon::parse(gmdate('Y-m-d H:i:s', '0000-00-00 00:00:00'))
                );
            }
        } else {
            $file->setHash($hash);

            $file->setIsUploading(false);
            if ($etag != "") {
                $file->setEtag($etag);
            }

            if ($etag == "" || $etag == 1) {
                // Copy parent folder meta fields
                if ($file->getParent()) {
                    $parentMetaFieldValues = $file->getParent()->getMetaFieldsValues();
                    foreach ($parentMetaFieldValues as $value) {
                        $metaFieldValue = clone $value;
                        $metaFieldValue->setResource($file);
                        if (!count($file->getMetaFieldsValuesFiltered($value->getMetaField(), $value->getValue()))) {
                            $file->getMetaFieldsValues()->add($metaFieldValue);
                            $this->getEntityManager()->persist($metaFieldValue);
                        }
                    }
                }
                if ($file->getParent() == null && !$file->getWorkspace()->getDesktopSync()) {
                    // Disable desktop sync if root folder
                    $users = $this->getWorkspaceService()->getWorkspaceUsers($file->getWorkspace());
                    foreach ($users as $user) {
                        $this->getSelectiveSyncService()->setSelectiveSync($file, $user);
                    }
                }

                //creating new file event
                $this->getEventService()->addEvent(FileEvent::TYPE_CREATE, $file, $createdBy, null, false);
                $this->getHistoryEventService()->addEvent(FileEvent::TYPE_CREATE, $file, $createdBy, $file->getName());
                //creating event notification
                $this->getEventNotificationService()->addEventNotification(
                    FileEventNotification::TYPE_CREATE,
                    $file,
                    $createdBy
                );
            } else {
                //creating update file event
                $this->getEventService()->addEvent(FileEvent::TYPE_CONTENT_UPDATE, $file, $createdBy, null, false);
                $this->getHistoryEventService()->addEvent(
                    FileEvent::TYPE_CONTENT_UPDATE,
                    $file,
                    $createdBy,
                    $file->getName()
                );
                //creating event notification
                $this->getEventNotificationService()->addEventNotification(
                    FileEventNotification::TYPE_UPDATE,
                    $file,
                    $createdBy
                );
            }
        }
        $this->getEntityManager()->flush();
    }
    /**
     * @param array $data
     * @param null $workspace
     * @param null $createdBy
     * @return File
     * @throws ConflictException
     * @throws ValidationFailedException
     * @throws \Doctrine\ORM\ORMException
     */
    public function createFile(array $data, $workspace = null, $createdBy = null)
    {
        if (is_numeric($createdBy)) {
            $userId = $createdBy;
            /** @var User $createdBy */
            $createdBy = $this->getEntityManager()->getReference(User::ENTITY_NAME, $userId);
        }

        if (is_numeric($workspace)) {
            $workspaceId = $workspace;
            /** @var Workspace $workspace */
            $workspace = $this->getEntityManager()->getReference(Workspace::ENTITY_NAME, $workspaceId);
        }

        if (empty($data['name'])) {
            throw new ValidationFailedException('File name is not set');
        }

        $fileUpload = null;
        $hash = "";
        $filePath = "";
        if (isset($data['upload_id'])) {
            $fileUpload = $this->getFileUpload($data['upload_id']);
        }
        if (isset($data['content'])) {
            $tmpFilePath = $this->createTemporaryFile($data['content']);
            $uploader = $this->createUploader($tmpFilePath);
            if (isset($data['hash'])) {
                $hash = $data['hash'];
            } else {
                $hash = $this->hashFile($tmpFilePath, $uploader->getFileInfo()->getSize());
            }
        } elseif ($fileUpload !== null) {
            $filePath = $this->getUploadsStorage()->getStreamUrl(
                $fileUpload->getId().DIRECTORY_SEPARATOR.$fileUpload->getId()
            );
            $uploader = $this->createMultiUploader(dirname($filePath), $fileUpload->getId());
            $hash = $fileUpload->getHash();
            //$hash = $this->hashFile($filePath, $uploader->getFileInfo()->getSize());
        } else {
            if (count($_FILES) != 1) { //TODO make multiple file upload
                throw new \InvalidArgumentException();
            }
            $uploader = $this->createUploader();
            $tmpFilePath = $uploader->offsetGet(0)->getPathname();
            if (isset($data['hash'])) {
                $hash = $data['hash'];
            } else {
                $hash = $this->hashFile($tmpFilePath, $uploader->getFileInfo()->getSize());
            }
        }

        //$fileInfo = $uploader->getFileInfo();

        $file = new File();

        if (isset($data['parent_id'])) {
            $parentFolder = FileServiceHelper::updateParentFolder(
                $file,
                $data['parent_id'],
                $this->getEntityManager()
            );
            unset($data['parent_id']);
        } else {
            $parentFolder = null;
        }

        if ($workspace === null) {
            if ($parentFolder !== null) {
                $workspace = $parentFolder->getWorkspace();
            } else {
                throw new ValidationFailedException();
            }
        }

        if (isset($data['content_created_at'])) {
            $file->setContentCreatedAt(Carbon::parse(gmdate('Y-m-d H:i:s', strtotime($data['content_created_at']))));
            unset($data['content_created_at']);
        }

        if (isset($data['content_modified_at'])) {
            $file->setContentModifiedAt(Carbon::parse(gmdate('Y-m-d H:i:s', strtotime($data['content_modified_at']))));
            unset($data['content_modified_at']);
        }

        $hydrator = new ClassMethodsHydrator();
        $hydrator->hydrate($data, $file);

        if ($this->checkNameExists($data['name'], $workspace, $parentFolder)) {
            throw new ConflictException();
        }

        $mimeType = $this->detectMimeType($data['name']);

        $file->setCreatedBy($createdBy)
             ->setWorkspace($workspace)
             ->setSize($uploader->getFileSize())
             ->setMimeType(isset($data['mime_type']) ? $data['mime_type'] : $mimeType)
             ->setHash($hash);

        //adding permissions
        if ($file->getParent()) {
            $file->setOwnedBy($file->getParent()->getOwnedBy());
        } else {
            $file->setOwnedBy($createdBy);
        }

        //create new file version
        $this->createFileVersion($file, $data, $createdBy);


        $this->getEntityManager()->persist($file);

        //commit changes always as we need file ID
        $this->getEntityManager()->flush();
        $azure = getenv('AZURE');
        if ($filePath != "") {
            if (strpos($filePath, "fredrik") !== false) {
                $prefix = '/app/';
                $xdebug = 'XDEBUG_CONFIG="idekey=uploadFinish remote_enable=1 '.
                    'remote_host=docker.for.mac.localhost remote_port=9000 remote_log=/tmp/xdebug.log '.
                    'remote_connect_back=0 " ';
            } else {
                if ($azure) {
                    $prefix = '/app/';
                } else {
                    $prefix = '../';
                }
                $xdebug = '';
            }
            $phpCommand = $prefix . 'bin/finishUpload finish-upload ' .
                $file->getId() . ' ' .
                $data['upload_id'] . ' ' .
                $createdBy->getId() . ' ' .
                escapeshellarg((isset($_SERVER["HTTP_CLIENT_VERSION"]) ? $_SERVER["HTTP_CLIENT_VERSION"] : 'Unknown'));
            $command = $xdebug . 'nohup ' . $phpCommand . ' > /dev/null 2>&1 & echo $!';
            exec($command, $output, $return_var);
            $file->setIsUploading(true);
            $this->getEntityManager()->flush();
        } else {
            //uploading to file storage
            $uploadHash = $this->uploadFileVersionToStorage($file->getVersion(), $uploader);
            if ($hash != $uploadHash) {
                $uploader->delete();
                $fileVersion = $file->getVersion();
                $fileVersion->setFile(null);
                $this->getEntityManager()->flush();
                $this->getEntityManager()->remove($fileVersion);
                //$file = $this->getEntityManager()->getReference(File::ENTITY_NAME, $fileId);
                $file->setVersion(null);
                $this->getEntityManager()->flush();
                $this->getEntityManager()->remove($file);
            } else {
                // Copy parent folder meta fields
                if ($file->getParent()) {
                    $parentMetaFieldValues = $file->getParent()->getMetaFieldsValues();
                    foreach ($parentMetaFieldValues as $value) {
                        $metaFieldValue = clone $value;
                        $metaFieldValue->setResource($file);
                        if (!count($file->getMetaFieldsValuesFiltered($value->getMetaField(), $value->getValue()))) {
                            $file->getMetaFieldsValues()->add($metaFieldValue);
                            $this->getEntityManager()->persist($metaFieldValue);
                        }
                    }
                }
                if ($file->getParent() == null && !$workspace->getDesktopSync()) {
                    // Disable desktop sync if root folder
                    $users = $this->getWorkspaceService()->getWorkspaceUsers($workspace);
                    foreach ($users as $user) {
                        $this->getSelectiveSyncService()->setSelectiveSync($file, $user);
                    }
                }
                //deleting file upload if exists in db
                if ($fileUpload !== null) {
                    $this->getEntityManager()->remove($fileUpload);
                }

                //creating event
                $this->getEventService()->addEvent(FileEvent::TYPE_CREATE, $file, $createdBy, null, false);
                $this->getHistoryEventService()->addEvent(FileEvent::TYPE_CREATE, $file, $createdBy, $file->getName());
                //creating event notification
                $this->getEventNotificationService()->addEventNotification(
                    FileEventNotification::TYPE_CREATE,
                    $file,
                    $createdBy
                );
            }
            $this->getEntityManager()->flush();
        }
        return $file;
    }

    /**
     * Used if file is uploaded by sending content directly in POST webhook
     *
     * @param $content
     * @return string
     */
    private function createTemporaryFile($content)
    {
        $tmpFilePath = tempnam(sys_get_temp_dir(), 'file_content_');
        $handle = fopen($tmpFilePath, "w");
        fwrite($handle, $content);
        fclose($handle);

        return $tmpFilePath;
    }

    /**
     * @param null $tmpFilePath
     * @return Uploader
     */
    private function createUploader($tmpFilePath = null)
    {
        $storage = $this->getFileStorage();
        $encryptor = $this->getEncryptor();
        $uploader = new Uploader($storage, ['encryptor' => $encryptor]);
        if (!$uploader->count()) {
            if ($tmpFilePath !== null) {
                $fileInfo = FileInfo::createFromFactory($tmpFilePath);
                $uploader->offsetSet(0, $fileInfo);
            }
        }

        if (count($uploader->getErrors())) {
            throw new \RuntimeException('File was not properly uploaded: ' . implode('; ', $uploader->getErrors()));
        }

        return $uploader;
    }

    /**
     * @param null $tmpFilePath
     * @return Uploader
     */
    private function createMultiUploader($uploadFilesDir, $prefix)
    {
        $storage = $this->getFileStorage();
        $encryptor = $this->getEncryptor();
        $uploader = new Uploader($storage, ['encryptor' => $encryptor]);
        $offset = 0;
        $uploadFiles = array();
        if (!$uploader->count()) {
            if ($uploadFilesDir !== null) {
                if ($handle = opendir($uploadFilesDir)) {
                    while (false !== ($entry = readdir($handle))) {
                        if (substr($entry, 0, strlen($prefix)) === $prefix) {
                            $uploadFiles[] = $entry;
                        }
                    }
                    closedir($handle);
                    natsort($uploadFiles);
                    foreach ($uploadFiles as $uploadFile) {
                        $fileInfo = FileInfo::createFromFactory($uploadFilesDir . "/" . $uploadFile);
                        $uploader->offsetSet($offset++, $fileInfo);
                    }
                }
            }
        }

        if (count($uploader->getErrors())) {
            throw new \RuntimeException('File was not properly uploaded: ' . implode('; ', $uploader->getErrors()));
        }

        return $uploader;
    }

    /**
     * @return \iCoordinator\File\Storage\StorageInterface
     */
    public function getFileStorage()
    {
        if ($this->fileStorage === null) {
            $this->fileStorage = StorageFactory::createStorage($this->getFileStorageSettings());
        }
        return $this->fileStorage;
    }

    /**
     * @return \iCoordinator\File\Storage\StorageInterface
     */
    public function getUploadsStorage()
    {
        if ($this->uploadsStorage === null) {
            $this->uploadsStorage = StorageFactory::createStorage($this->getUploadsStorageSettings());
        }
        return $this->uploadsStorage;
    }

    private function getFileStorageSettings()
    {
        $settings = $this->getContainer()->get('settings');
        if (isset($settings['fileStorage'])) {
            return $settings['fileStorage'];
        } else {
            throw new \RuntimeException('File storage config not defined in settings');
        }
    }

    private function getUploadsStorageSettings()
    {
        $settings = $this->getContainer()->get('settings');
        if (isset($settings['uploadsStorage'])) {
            return $settings['uploadsStorage'];
        } else {
            throw new \RuntimeException('Uploads storage config not defined in settings');
        }
    }


    private function createFileVersion(File $file, $data, User $modifiedBy)
    {
        $fileVersion = new FileVersion();
        $fileVersion->setFile($file)
            ->setName($file->getName())
            ->setSize($file->getSize())
            ->setIv(Encryptor::generateIv())
            ->setModifiedBy($modifiedBy);

        if (isset($data['comment'])) {
            $fileVersion->setComment($data['comment']);
        }

        $file->setVersion($fileVersion);

        return $fileVersion;
    }

    /**
     * @param FileVersion $fileVersion
     * @param StorageInterface|Uploader $uploader
     */
    private function uploadFileVersionToStorage(FileVersion $fileVersion, Uploader $uploader)
    {
        $storagePath = $this->getFileVersionStoragePath($fileVersion);
        $fileVersion->setStoragePath($storagePath);

        $this->getEntityManager()->flush($fileVersion);

        $uploader->setFileVersion($fileVersion);
        if ($uploader) {
            return $uploader->upload();
        }
    }

    private function getFileVersionStoragePath(FileVersion $fileVersion)
    {
        return FileServiceHelper::getFileVersionStoragePath($fileVersion);
    }

    /**
     * @return Encryptor
     */
    private function getEncryptor()
    {
        if ($this->encryptor === null) {
            $this->encryptor = new Encryptor();
            $settings = $this->getContainer()->get('settings');
            if (isset($settings['fileEncryptionKey'])) {
                $this->encryptor->setKey($settings['fileEncryptionKey']);
            } else {
                throw new \RuntimeException('File storage encryption key is not defined in settings');
            }
        }

        return $this->encryptor;
    }

    /**
     * @param $fileId
     * @return null|File
     */
    public function getFile($fileId)
    {
        $file = $this->getEntityManager()->find(File::ENTITY_NAME, $fileId);
        return $file;
    }

    /**
     * @param $fileVersionId
     * @return null|FileVersion
     */
    public function getFileVersion($fileVersionId)
    {
        $fileVersion = $this->getEntityManager()->find(FileVersion::ENTITY_NAME, $fileVersionId);
        return $fileVersion;
    }
    /**
     * @param $fileId
     * @return null|FileVersion
     */
    public function getLatestFileVersion($fileId)
    {
        $em = $this->getEntityManager();
        $sql = "SELECT fv.* FROM file_versions fv where file_id = " . $fileId . " ORDER by id DESC LIMIT 1";

        $stmt = $em->getConnection()->prepare($sql);
        $stmt->execute();
        return $stmt->fetch();
    }
    /**
     * @param File $file
     * @return null|array
     */
    public function getAllFileVersions($file)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select(array('fv'))
            ->from(FileVersion::ENTITY_NAME, 'fv')
            ->where('fv.file = :file')
            ->setParameter('file', $file);
        $query = $qb->getQuery();
        $paginator = new Paginator($query, false);

        return $paginator->getIterator()->getArrayCopy();
    }
    /**
     * @param int|File $file
     * @param $user
     * @param bool $permanently
     * @throws NotFoundException
     * @throws \Doctrine\ORM\ORMException
     */
    public function deleteFile($file, $user, $permanently = false, $commitChanges = true, $recursive = false)
    {
        if (is_numeric($user)) {
            $userId = $user;
            /** @var User $user */
            $user = $this->getEntityManager()->getReference(User::ENTITY_NAME, $userId);
        }

        if (is_numeric($file)) {
            $fileId = $file;
            $file = $this->getEntityManager()->getReference(File::ENTITY_NAME, $fileId);
        }

        FileServiceHelper::checkIfLocked($file, $user, $this->getLockService(), $this->getUserService());

        if ((!$file->getIsTrashed() || $permanently) && !$file->getIsDeleted()) {
            if ($permanently) {
                $file->setIsDeleted(true);
            } else {
                $file->setIsTrashed(true);
            }

            try {
                $this->getEntityManager()->merge($file);
            } catch (EntityNotFoundException $e) {
                throw new NotFoundException();
            }

            if (!$recursive) {
                //creating event
                $this->getEventService()->addEvent(FileEvent::TYPE_DELETE, $file, $user, null, false);
                $this->getHistoryEventService()->addEvent(FileEvent::TYPE_DELETE, $file, $user, $file->getName());
            }
            //creating event notification
            $this->getEventNotificationService()->addEventNotification(
                FileEventNotification::TYPE_DELETE,
                $file,
                $user
            );

            if ($commitChanges == true) {
                $this->getEntityManager()->flush();
            }
        }
    }

    /**
     * @param int|File $file
     * @throws NotFoundException
     * @throws \Doctrine\ORM\ORMException
     */
    public function permanentRemoveFile($file)
    {
        if (is_numeric($file)) {
            $fileId = $file;
            $file = $this->getEntityManager()->getReference(File::ENTITY_NAME, $fileId);
        }
    }

    /**
     * @param $file
     * @param array $data
     * @param $user
     * @return bool|\Doctrine\Common\Proxy\Proxy|null|object
     * @throws ConflictException
     * @throws NotFoundException
     * @throws \Doctrine\ORM\ORMException
     */
    public function updateFile($file, array $data, $user)
    {
        $rename = true;
        $historyEvents = array();
        $events = array();

        if (is_numeric($user)) {
            $userId = $user;
            /** @var User $user */
            $user = $this->getEntityManager()->getReference(User::ENTITY_NAME, $userId);
        }

        if (is_numeric($file)) {
            $fileId = $file;
            $file = $this->getEntityManager()->find(File::ENTITY_NAME, $fileId);
            if (!$file) {
                throw new NotFoundException();
            }
        }



        if (array_key_exists('lock', $data)) {
            $fileServiceHelper = new FileServiceHelper();
            $fileServiceHelper->checkIfLockedAdmin(
                $file,
                $user,
                $this->getLockService(),
                $this->getUserService(),
                $this->getAcl()
            );
            FileServiceHelper::updateFileLock($file, $data['lock'], $user, $this->getLockService());
            if ($data['lock']) {
                $historyEvents[] = array(FileHistoryEvent::TYPE_FILE_LOCK, $file, $user, $file->getName());
            } else {
                $historyEvents[] = array(FileHistoryEvent::TYPE_FILE_UNLOCK, $file, $user, $file->getName());
            }
            unset($data['lock']);
        } else {
            FileServiceHelper::checkIfLocked($file, $user, $this->getLockService(), $this->getUserService());
        }

        if (isset($data['parent'])) {
            $newParentId = $data['parent']['id'];
            if ($newParentId != 0) {
                $newParentFolder = $this->getEntityManager()->find(Folder::ENTITY_NAME, $newParentId);
                if (!$newParentFolder) {
                    throw new NotFoundException();
                }
            } else {
                $newParentFolder = null;
            }
            $parentId = ($file->getParent() != null ? $file->getParent()->getId() : null);

            if ($newParentId != $parentId) {
                if (!empty($data['name'])) {
                    if ($this->checkNameExists(
                        $data['name'],
                        $file->getWorkspace(),
                        $newParentFolder,
                        $file->getId()
                    )) {
                        throw new ConflictException(
                            "Tried changing name and moving but file ".$data['name'].
                            " exists in ".($newParentFolder != null ? $newParentFolder->getName() : null).
                            " new parent id is ".$newParentId
                        );
                    } else {
                        // Only create event if name has actually changed
                        if ($data['name'] != $file->getName()) {
                            // Using name temporary to pass name change information to history events
                            // will be hydrated to new name down below
                            $events[] = array(FileEvent::TYPE_RENAME, $file, $user, null, true, $file->getName());
                        }
                    }
                } elseif ($this->checkNameExists(
                    $file->getName(),
                    $file->getWorkspace(),
                    $newParentFolder,
                    $file->getId()
                )) {
                    throw new ConflictException(
                        "Tried moving file but ".$file->getName().
                        " exists in ".($newParentFolder != null ? $newParentFolder->getName() : null).
                        " new parent id is ".$newParentId
                    );
                }
                $parentFolder = FileServiceHelper::updateParentFolder(
                    $file,
                    $data['parent'],
                    $this->getEntityManager()
                );
                // Copy new parent folder meta fields
                if ($newParentFolder) {
                    $parentMetaFieldValues = $newParentFolder->getMetaFieldsValues();
                    foreach ($parentMetaFieldValues as $value) {
                        $metaFieldValue = clone $value;
                        $metaFieldValue->setResource($file);
                        if (!count($file->getMetaFieldsValuesFiltered($value->getMetaField(), $value->getValue()))) {
                            $file->getMetaFieldsValues()->add($metaFieldValue);
                            $this->getEntityManager()->persist($metaFieldValue);
                        }
                    }
                }
                // Only create event if parent has actually changed for file
                $events[] = array(FileEvent::TYPE_MOVE, $file, $user, null, false, null);
                $description = $parentId . ':' . $newParentId;
                $historyEvents[] = array(FileEvent::TYPE_MOVE, $file, $user, $description);
            } elseif (!empty($data['name'])) {
                $parentFolder = $file->getParent();
                if ($this->checkNameExists($data['name'], $file->getWorkspace(), $parentFolder, $file->getId())) {
                    throw new ConflictException(
                        "Tried changing name but file ".$data['name'].
                        " exists in ". ($parentFolder != null ? $parentFolder->getName() : null).
                        " parent id is ".$newParentId
                    );
                }
                // Only create event if name has actually changed
                if ($data['name'] != $file->getName()) {
                    // Using name temporary to pass name change information to history events
                    // will be hydrated to new name down below
                    $events[] = array(FileEvent::TYPE_RENAME, $file, $user, null, true, $file->getName());
                }
            }
            if ($parentFolder) {
                unset($data['parent']);
            } else {
                $data['parent'] = null;
            }
            $rename = false;
        } else {
            $parentFolder = $file->getParent();
        }

        if ($parentFolder) {
            if ($file->getWorkspace()->getId() != $parentFolder->getWorkspace()->getId()) {
                $file->setWorkspace($parentFolder->getWorkspace());
            }
        }
        if (array_key_exists('shared_link', $data)) {
            FileServiceHelper::updateSharedLink($file, $data['shared_link'], $user, $this->getSharedLinkService());
            unset($data['shared_link']);
        }

        if ($rename && !empty($data['name'])) {
            if ($this->checkNameExists($data['name'], $file->getWorkspace(), $parentFolder, $file->getId())) {
                throw new ConflictException(
                    "Tried changing name but file ".$data['name'].
                    " exists in ". ($parentFolder != null ? $parentFolder->getName() : null)
                );
            }
            // Only create event if name has actually changed
            if ($data['name'] != $file->getName()) {
                // Using name temporary to pass name change information to history events
                // will be hydrated to new name down below
                $events[] = array(FileEvent::TYPE_RENAME, $file, $user, null, true, $file->getName());
            }
        }

        if (!empty($data)) {
            $hydrator = new ClassMethodsHydrator();
            $hydrator->hydrate($data, $file);

            // Removed since version should not be bumped for name change or file moved
            //$file->setEtag($file->getEtag() + 1);
        }
        $this->getEntityManager()->flush();
        $this->addHistoryEvents($historyEvents);
        $this->addEvents($events);
        $this->getEntityManager()->flush();
        return $file;
    }

    /**
     * @param $file
     * @param $user
     * @param array|null $data
     * @param bool|true $commitChanges
     * @return File
     * @throws ConflictException
     * @throws NotFoundException
     * @throws NotTrashedException
     * @throws ValidationFailedException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     */
    public function restoreFile($file, $user, array $data = null, $commitChanges = true)
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

        if (!$file->getIsTrashed()) {
            throw new NotTrashedException();
        }

        if (isset($data['parent'])) {
            $parentFolder = FileServiceHelper::updateParentFolder(
                $file,
                $data['parent'],
                $this->getEntityManager()
            );
            unset($data['parent']);
        } else {
            $parentFolder = $file->getParent();
            if ($parentFolder->getIsTrashed() || $parentFolder->getIsDeleted()) {
                throw new ConflictException();
            }
        }

        if (!empty($data['name'])) {
            $file->setName($data['name']);
        }

        if ($this->checkNameExists($file->getName(), $file->getWorkspace(), $parentFolder)) {
            throw new ConflictException();
        }

        $file->setIsTrashed(false);

        $this->getEventService()->addEvent(FileEvent::TYPE_CREATE, $file, $user);

        if ($commitChanges) {
            $this->getEntityManager()->flush();
        }

        return $file;
    }

    /**
     * @param $file
     * @param $user
     * @param array $data
     * @return File
     * @throws Exception\LockedException
     * @throws NotFoundException
     * @throws ValidationFailedException
     */
    public function updateFileContent($file, $user, array $data = null)
    {
        if (is_numeric($user)) {
            $userId = $user;
            /** @var User $user */
            $user = $this->getEntityManager()->getReference(User::ENTITY_NAME, $userId);
        }

        if (is_numeric($file)) {
            $fileId = $file;
            /** @var File $file */
            $file = $this->getEntityManager()->getReference(File::ENTITY_NAME, $fileId);
        }

        FileServiceHelper::checkIfLocked($file, $user, $this->getLockService(), $this->getUserService());

        $fileUpload = null;
        $filePath = "";
        $hash = "";
        if (isset($data['upload_id'])) {
            $fileUpload = $this->getFileUpload($data['upload_id']);
        }
        if (isset($data['content'])) {
            $tmpFilePath = $this->createTemporaryFile($data['content']);
            $uploader = $this->createUploader($tmpFilePath);
            if (isset($data['hash'])) {
                $hash = $data['hash'];
            } else {
                $hash = $this->hashFile($tmpFilePath, $uploader->getFileInfo()->getSize());
            }
        } elseif ($fileUpload !== null) {
            $filePath = $this->getUploadsStorage()->getStreamUrl(
                $fileUpload->getId().DIRECTORY_SEPARATOR.$fileUpload->getId()
            );
            $uploader = $this->createMultiUploader(dirname($filePath), $fileUpload->getId());
            //$hash = $this->hashFile($filePath, $uploader->getFileInfo()->getSize());
        } else {
            if (count($_FILES) != 1) { //TODO make multiple file upload
                throw new \InvalidArgumentException();
            }
            $uploader = $this->createUploader();
            $tmpFilePath = $uploader->offsetGet(0)->getPathname();
            if (isset($data['hash'])) {
                $hash = $data['hash'];
            } else {
                $hash = $this->hashFile($tmpFilePath, $uploader->getFileInfo()->getSize());
            }
        }

        /*try {
            $this->getEntityManager()->merge($file);
        } catch (EntityNotFoundException $e) {
            throw new NotFoundException();
        }*/
        $fileSize = $uploader->getFileSize();
        //create new file version
        $fileVersion = $this->createFileVersion($file, $data, $user);
        $fileVersion->setSize($fileSize);
        //needs to be flushed before storage upload
        $this->getEntityManager()->flush();
        $azure = getenv('AZURE');
        if ($filePath != "") {
            if (strpos($filePath, "fredrik") !== false) {
                $prefix = '/app/';
                $xdebug = 'XDEBUG_CONFIG="idekey=uploadFinish remote_enable=1 '.
                    'remote_host=docker.for.mac.localhost remote_port=9000 remote_log=/tmp/xdebug.log '.
                    'remote_connect_back=0 " ';
            } else {
                if ($azure) {
                    $prefix = '/app/';
                } else {
                    $prefix = '../';
                }
                $xdebug = '';
            }
            $phpCommand = $prefix . 'bin/finishUpload finish-upload ' .
                $file->getId() . ' ' .
                $data['upload_id'] . ' ' .
                $user->getId() . ' ' .
                escapeshellarg(
                    (isset($_SERVER["HTTP_CLIENT_VERSION"]) ? $_SERVER["HTTP_CLIENT_VERSION"] : 'Unknown')
                ) . ' ' .
                ($file->getEtag()+1) . ' ' .
                $file->getHash() . ' ' .
                ($file->getContentModifiedAt() ?
                    base64_encode($file->getContentModifiedAt()->format('Y-m-d H:i:s')) : null
                );
            $command = $xdebug . 'nohup ' . $phpCommand . ' > /dev/null 2>&1 & echo $!';
            exec($command, $output, $return_var);
            if (isset($data['content_modified_at'])) {
                $file->setContentModifiedAt(
                    Carbon::parse(gmdate('Y-m-d H:i:s', strtotime($data['content_modified_at'])))
                );
            }
            $file->setEtag($file->getEtag()+1)
                ->setHash($fileUpload->getHash())
                ->setSize($fileSize)
                ->setIsUploading(true);
            $this->getEntityManager()->flush();
        } else {
            //upload file to storage
            $uploadHash = $this->uploadFileVersionToStorage($file->getVersion(), $uploader);
            if ($hash != $uploadHash) {
                $uploader->delete();
                $fileVersion = $file->getVersion();
                $fileVersion->setFile(null);
                $this->getEntityManager()->flush();
                $this->getEntityManager()->remove($fileVersion);
                $fvArray = $this->getLatestFileVersion($file->getId());
                $fileVersion = $this->getEntityManager()->getReference(FileVersion::ENTITY_NAME, $fvArray['id']);
                $file->setVersion($fileVersion);
                $file->setIsUploading(false);
            } else {
                //deleting file upload if exists in db
                if ($fileUpload !== null) {
                    $this->getEntityManager()->remove($fileUpload);
                }
                if (isset($data['content_modified_at'])) {
                    $file->setContentModifiedAt(
                        Carbon::parse(gmdate('Y-m-d H:i:s', strtotime($data['content_modified_at'])))
                    );
                }
                $file->setHash($hash);
                $file->setEtag($file->getEtag() + 1)
                    ->setSize($fileSize);
                //creating event
                $this->getEventService()->addEvent(FileEvent::TYPE_CONTENT_UPDATE, $file, $user, null, false);
                $this->getHistoryEventService()->addEvent(
                    FileEvent::TYPE_CONTENT_UPDATE,
                    $file,
                    $user,
                    $file->getName()
                );
                //creating event notification
                $this->getEventNotificationService()->addEventNotification(
                    FileEventNotification::TYPE_UPDATE,
                    $file,
                    $user
                );
            }
            $this->getEntityManager()->flush();
        }
        return $file;
    }

    public function copyFile($file, $user, array $data = null)
    {
        if (is_numeric($user)) {
            $userId = $user;
            /** @var User $user */
            $user = $this->getEntityManager()->getReference(User::ENTITY_NAME, $userId);
        }

        if (is_numeric($file)) {
            $fileId = $file;
            /** @var File $file */
            $file = $this->getEntityManager()->getReference(File::ENTITY_NAME, $fileId);
        }

        $copyFile = clone $file;
        $copyFile->setLock(null);
        $fileVersionCopy = clone $file->getVersion();
        $copyFile->setVersion($fileVersionCopy);
        $fileVersionCopy->setFile($copyFile);
        $copyFile->setEtag(1);

        if (isset($data['parent'])) {
            $parentFolder = FileServiceHelper::updateParentFolder(
                $copyFile,
                $data['parent'],
                $this->getEntityManager()
            );
            unset($data['parent']);
        } else {
            $parentFolder = $copyFile->getParent();
        }

        if (isset($data['name'])) {
            $newName = $data['name'];
            unset($data['name']);
        } else {
            $newName = $copyFile->getName();
        }
        if ($this->checkNameExists($newName, $copyFile->getWorkspace(), $parentFolder)) {
            throw new ConflictException();
        }
        $copyFile->setName($newName);


        try {
            $this->getEntityManager()->persist($copyFile);
        } catch (EntityNotFoundException $e) {
            throw new NotFoundException();
        }

        //adding permissions
        if ($copyFile->getParent()) {
            $copyFile->setOwnedBy($copyFile->getParent()->getOwnedBy());
        } else {
            $copyFile->setOwnedBy($user);
        }

        // copy meta fields
        $copyFileMetaFieldsValues = array();
        $fileMetaFieldsValues =  $file->getMetaFieldsValues();
        foreach ($fileMetaFieldsValues as $value) {
            $metaFieldValue = clone $value;
            $metaFieldValue->setResource($copyFile);
            $this->getEntityManager()->persist($metaFieldValue);
            $copyFileMetaFieldsValues[] = $metaFieldValue;
        }
        $copyFile->setMetaFieldsValues($copyFileMetaFieldsValues);

        // Copy parent folder meta fields
        if ($copyFile->getParent()) {
            $parentMetaFieldValues = $copyFile->getParent()->getMetaFieldsValues();
            foreach ($parentMetaFieldValues as $value) {
                $metaFieldValue = clone $value;
                $metaFieldValue->setResource($copyFile);
                if (!count($copyFile->getMetaFieldsValuesFiltered($value->getMetaField(), $value->getValue()))) {
                    $copyFile->getMetaFieldsValues()->add($metaFieldValue);
                    $this->getEntityManager()->persist($metaFieldValue);
                }
            }
        }

        if ($copyFile->getParent() == null) {
            $workspace = $copyFile->getWorkspace();
            if (!$workspace->getDesktopSync()) {
                // Disable desktop sync if root folder
                $users = $this->getWorkspaceService()->getWorkspaceUsers($workspace);
                foreach ($users as $user) {
                    $this->getSelectiveSyncService()->setSelectiveSync($copyFile, $user);
                }
            }
        }

        //commit changes always, as we need ID
        $this->getEntityManager()->flush();

        //copy content only when we know IDs of each file object
        $this->copyFileContent($file, $copyFile, $this->getFileStorage());

        //creating event
        $this->getEventService()->addEvent(FileEvent::TYPE_CREATE, $copyFile, $user);
        $this->getEntityManager()->flush();

        return $copyFile;
    }

    public function getFilePath($file)
    {
        if (is_numeric($file)) {
            $fileId = $file;
            /** @var File $file */
            $file = $this->getEntityManager()->getReference(File::ENTITY_NAME, $fileId);
        }
        $path = $file->getName();
        $parent = $file->getParent();
        do {
            if ($parent) {
                $path = $parent->getName()."/".$path;
                $parent = $parent->getParent();
            }
        } while ($parent != null);
        $pathInfo = array(
            'path' => $path = $file->getWorkspace()->getName() . "/" . $path
        );
        return $pathInfo;
    }
    public function copyWorkspaceFile($file, $data, $user, $copySettings, $groupMap, $permissionsArray, $users)
    {
        if (is_numeric($user)) {
            $userId = $user;
            /** @var User $user */
            $user = $this->getEntityManager()->getReference(User::ENTITY_NAME, $userId);
        }

        if (is_numeric($file)) {
            $fileId = $file;
            /** @var File $file */
            $file = $this->getEntityManager()->getReference(File::ENTITY_NAME, $fileId);
        }
        if (!$file instanceof File) {
            return null;
        }
        if ($file->getIsTrashed() || $file->getIsDeleted()) {
            return null;
        }
        $copyFile = clone $file;
        $fileVersionCopy = clone $file->getVersion();
        $copyFile->setVersion($fileVersionCopy);
        $fileVersionCopy->setFile($copyFile);
        $copyFile->setEtag(1);
        $copyFile->setParent($data['parent']);
        $copyFile->setWorkspace($data['workspace']);
        //create new file version
        //$this->createFileVersion($copyFile, $data, $user);
        try {
            $this->getEntityManager()->persist($copyFile);
        } catch (EntityNotFoundException $e) {
            throw new NotFoundException();
        }

        //commit changes always, as we need ID
        $this->getEntityManager()->flush();

        //adding permissions
        if ($copyFile->getParent()) {
            $copyFile->setOwnedBy($copyFile->getParent()->getOwnedBy());
        } else {
            $copyFile->setOwnedBy($user);
        }
        if ($copySettings["labels"]) {
            // copy meta fields
            $copyFileMetaFieldsValues = array();
            $fileMetaFieldsValues = $file->getMetaFieldsValues();
            foreach ($fileMetaFieldsValues as $value) {
                $metaFieldValue = clone $value;
                $metaFieldValue->setResource($copyFile);
                $this->getEntityManager()->persist($metaFieldValue);
                $copyFileMetaFieldsValues[] = $metaFieldValue;
            }
            $copyFile->setMetaFieldsValues($copyFileMetaFieldsValues);
        }
        if ($copySettings['desktop_sync'] == 0 && $users != null) {
            foreach ($users as $user) {
                $this->getSelectiveSyncService()->setSelectiveSync($copyFile, $user->getId());
            }
        }

        //creating event
        $this->getEventService()->addEvent(FileEvent::TYPE_CREATE, $copyFile, $user);


        //copy content only when we know IDs of each file object
        //$this->copyFileContent($file, $copyFile, $this->getFileStorage());


        if ($copySettings["permissions"]) {
            if (isset($permissionsArray[$file->getId()])) {
                $permissions = $permissionsArray[$file->getId()];
                $newResource = new AclFileResource();
                $newResource->setFile($copyFile);
                $copyFile->setAclResource($newResource);
                try {
                    $this->getEntityManager()->persist($newResource);
                    $this->getEntityManager()->persist($copyFile);
                } catch (EntityNotFoundException $e) {
                    throw new NotFoundException();
                }
                $this->getEntityManager()->flush();
                foreach ($permissions as $permission) {
                    $bitMask = new BitMask('file');
                    $actions = $bitMask->getPermissions($permission['bit_mask']);
                    if ($permission['role_entity_type'] == "group") {
                        if (array_key_exists($permission['role_entity_id'], $groupMap)) {
                            $newGroup = $groupMap[$permission['role_entity_id']];
                            $this->getPermissionService()->addPermission(
                                $copyFile,
                                $newGroup,
                                $actions,
                                $user,
                                $copyFile->getWorkspace()->getPortal()
                            );
                        }
                    } else {
                        $this->getPermissionService()->addPermission(
                            $copyFile,
                            $this->getUserService()->getUser($permission['role_entity_id']),
                            $actions,
                            $user,
                            $copyFile->getWorkspace()->getPortal()
                        );
                    }
                }
            }
        }

        return $copyFile;
    }

    /**
     * @param File $originalFile
     * @param File $copyFile
     * @param StorageInterface $storage
     */
    private function copyFileContent(File $originalFile, File $copyFile, StorageInterface $storage)
    {
        $storage->copy($originalFile, $copyFile);
    }

    public function getFileVersions($file, $limit = self::FILE_VERSIONS_LIMIT_DEFAULT, $offset = 0)
    {
        if (is_numeric($file)) {
            $fileId = $file;
            /** @var File $file */
            $file = $this->getEntityManager()->getReference(File::ENTITY_NAME, $fileId);
            if (!$file) {
                throw new NotFoundException();
            }
        }

        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select(array('fv'))
            ->from(FileVersion::ENTITY_NAME, 'fv')
            ->where('fv.file = :file')
            ->setParameter('file', $file);


        if ($limit !== null) {
            $qb->setFirstResult($offset)
                ->setMaxResults($limit);
        }

        $query = $qb->getQuery();
        $paginator = new Paginator($query);

        return $paginator;
    }

    /**
     * @param $fileVersion
     * @param array $data
     * @param $user
     * @return bool|\Doctrine\Common\Proxy\Proxy|null|object
     * @throws ConflictException
     * @throws NotFoundException
     * @throws \Doctrine\ORM\ORMException
     */
    public function updateFileVersion($fileVersion, array $data, $user)
    {
        if (is_numeric($user)) {
            $userId = $user;
            /** @var User $user */
            $user = $this->getEntityManager()->getReference(User::ENTITY_NAME, $userId);
        }

        if (is_numeric($fileVersion)) {
            $fileVersionId = $fileVersion;
            $fileVersion = $this->getEntityManager()->find(FileVersion::ENTITY_NAME, $fileVersionId);
            if (!$fileVersion) {
                throw new NotFoundException();
            }
        }

        if (!empty($data)) {
            $hydrator = new ClassMethodsHydrator();
            $hydrator->hydrate($data, $fileVersion);
        }

        $this->getEntityManager()->flush();

        return $fileVersion;
    }

    /**
     * @param $file
     * @param null $version
     * @param array $options
     * @return Downloader
     * @throws NotFoundException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     */
    public function getFileDownloader($file, $version = null, $options = array(), $openStyle = 'inline')
    {
        if (is_numeric($file)) {
            $fileId = $file;
            /** @var File $file */
            $file = $this->getEntityManager()->find(File::ENTITY_NAME, $fileId);
            if (!$file) {
                throw new NotFoundException();
            }
        }

        if ($version !== null) {
            $fileVersion = $this->getEntityManager()->find(FileVersion::ENTITY_NAME, $version);
            if (!$fileVersion) {
                throw new NotFoundException();
            }
        } else {
            $fileVersion = $file->getVersion();
        }

        $storage = $this->getFileStorage();
        $options['encryptor'] = $this->getEncryptor();

        $downloader = new Downloader($storage, $this, $openStyle, $options);
        $downloader->setFileVersion($fileVersion);

        return $downloader;
    }
    private function getStoragePathCount($fileVersion)
    {
        /*$qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('COUNT(fv.storage_path)')
            ->from(FileVersion::ENTITY_NAME, 'fv')
            ->where('fv.storage_path = :storage_path')
            ->setParameter('storage_path', $fileVersion->getStoragePath());

        $query = $qb->getQuery();
        $result = $query->getResult();

        return $result[0][1];*/
        $em = $this->getEntityManager();
        $sql = "SELECT count(*) as cnt from file_versions where storage_path = ?";

        $stmt = $em->getConnection()->prepare($sql);
        $stmt->execute([$fileVersion->getStoragePath()]);
        $result = $stmt->fetchAll();
        return $result[0]["cnt"];
    }
    public function removeFileVersionPermanent($fileVersion)
    {
        /*$storagePathCount = $this->getStoragePathCount($fileVersion);
        if ($storagePathCount > 1) {
            echo($fileVersion->getStoragePath() . " : Count = ".$storagePathCount."\n");
        } else {
        */
        $storage = $this->getFileStorage();
        if (strlen($fileVersion->getStoragePath())) {
            echo("Removing " . $fileVersion->getStoragePath() . "\n");
            $result = $storage->delete($fileVersion->getStoragePath());
        }
        //}
    }
    /**
     * @param int $limit
     * @param int $offset
     * @param string $search
     * @return Paginator
     */
    public function getMatchingFiles($portalId, $limit = self::FILES_LIMIT_DEFAULT, $offset = 0, $search = '')
    {

        /*$qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select(array('f'))
            ->from(File::ENTITY_NAME, 'f')
            ->join('f.workspace', 'w')
            ->where('f.is_trashed != 1')
            ->andWhere('f.is_deleted != 1')
            ->andWhere("f INSTANCE OF :file_type")
            ->andWhere("w.portal = :portal")
            ->andWhere('LOWER(f.name) like :search')
            ->setParameter('search', '%'.strtolower($search).'%')
            ->setParameter('file_type', array('file'))
            ->setParameter('portal', $portalId);


        if ($limit !== null) {
            $qb->setFirstResult($offset)
                ->setMaxResults($limit);
        }

        $query = $qb->getQuery();
        $paginator = new Paginator($query);

        return $paginator;*/
        $search = '%' . $search . '%';
        $em = $this->getEntityManager();
        $sql = "SELECT DISTINCT f.id, f.name as hit FROM files f ".
            "JOIN workspaces w ".
            "where w.is_deleted != 1 and w.portal_id = ? and LOWER(f.name) like ? ".
            "and f.is_trashed != 1 and f.type = 'file' and f.is_deleted != 1 and f.workspace_id = w.id ".
            "UNION ".
            "SELECT DISTINCT f.id, mfv.value as hit FROM files f ".
            "JOIN workspaces w, meta_fields_values mfv ".
            "where w.is_deleted != 1 and w.portal_id = ? ".
            "and f.is_trashed != 1 and f.type = 'file' and f.is_deleted != 1 and f.workspace_id = w.id ".
            "and f.id = mfv.file_id and LOWER(mfv.value) like ? ".
            "UNION ".
            "SELECT DISTINCT f.id, mf.name as hit FROM files f ".
            "JOIN workspaces w, meta_fields mf ".
            "where w.is_deleted != 1 and w.portal_id = ? and LOWER(mf.name) like ? ".
            "and f.is_trashed != 1 and f.type = 'file' and f.is_deleted != 1 and f.workspace_id = w.id ".
            "and mf.id in (SELECT meta_field_id from meta_fields_values mfv where mfv.file_id = f.id)";
        if ($limit !== null) {
            $sql .= " LIMIT " . $limit;
        }
        $stmt = $em->getConnection()->prepare($sql);
        $stmt->execute([$portalId, $search, $portalId, $search, $portalId, $search]);
        return $stmt->fetchAll();
    }

    /**
     * @param int $limit
     * @param int $offset
     * @param string $search
     * @return Paginator
     */
    public function getMatchingWorkspaceFiles(
        $workspaceId,
        $limit = self::FILES_LIMIT_DEFAULT,
        $offset = 0,
        $search = ''
    ) {
        $search = '%' . $search . '%';
        $em = $this->getEntityManager();
        $sql = "SELECT DISTINCT f.id, f.name as hit FROM files f ".
            "where f.workspace_id = ? and (LOWER(f.name) like ? OR f.content_modified_at like ?) ".
            "and f.is_trashed != 1 and f.is_deleted != 1 and f.type != 'smart_folder'"
            ;
        if ($limit !== null) {
            $sql .= " LIMIT " . $limit;
        }
        $stmt = $em->getConnection()->prepare($sql);
        $stmt->execute([$workspaceId, $search, $search]);
        return $stmt->fetchAll();
    }

    /**
     * @param int $portalId
     * @return int
     */
    public function getUsedStorage($portalId)
    {

        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('SUM(fv.size)')
            ->from(FileVersion::ENTITY_NAME, 'fv')
            ->join('fv.file', 'f')
            ->join('f.workspace', 'w')
            ->join('w.portal', 'p')
            ->where('p.id = :portal_id')
            ->andWhere('f.is_trashed != 1')
            ->andWhere('f.is_deleted != 1')
            ->setParameter('portal_id', $portalId);

        $query = $qb->getQuery();
        $result = $query->getResult();

        return $result[0][1];
    }

    public function getFileFolderHighestPermission(File $file, $userId = null)
    {
        if ($userId == null) {
            $userId = $this->getAuth()->getIdentity();
        }
        $user = $this->getUserService()->getUser($userId);
        $allPrivileges = $this->getPermissionService()->getFirstFoundPermissions(
            $file,
            $user,
            $file->getWorkspace()->getPortal()
        );
        $highest = 0;
        $noPermission = PermissionType::getPermissionTypeBitMask(File::RESOURCE_ID, PermissionType::FILE_NONE);

        foreach ($allPrivileges as $priv) {
            if ($priv->getBitMask() > $highest && $priv->getBitMask() != $noPermission) {
                $highest = $priv->getBitMask();
            }
        }
        return $highest;
    }
    /**
     * @param $fileName
     * @return null|string
     */
    private function detectMimeType($fileName)
    {
        $mimes = new \Mimey\MimeTypes;
        if (false !== $poos = strrpos($fileName, '.')) {
            $extension = substr($fileName, $poos + 1);
            return $mimes->getMimeType($extension);
        }

        return null;
    }

    public function getAllFiles($withoutHash = false)
    {
        $em = $this->getEntityManager();
        $sql = "SELECT DISTINCT id FROM files WHERE type = 'file'";
        if ($withoutHash) {
            $sql .= " AND hash IS NULL";
        }
        $stmt = $em->getConnection()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    public function createFileHash()
    {
        $entries = $this->getAllFiles(true);
        $count = 0;
        foreach ($entries as $entry) {
            $file = $this->getFile($entry['id']);
            $downloader = $this->getFileDownloader($file);
            $ctx = hash_init('sha256');
            $stream = $downloader->getStream();
            $totalSize = 0;
            if ($stream) {
                while (!$stream->eof()) {
                    $buffer = $stream->read($downloader->getChunkSize());
                    $bufferSize = strlen($buffer);
                    $totalSize += $bufferSize;
                    if ($totalSize > $file->getSize()) {
                        $adjustedBufferSize = $bufferSize - ($totalSize - $file->getSize());
                        echo "Adjusting buffer - Total: ".$totalSize." File: ".$file->getSize()."\n";
                        echo "Buffer size: ".$bufferSize." Adjusted size: ".$adjustedBufferSize."\n";
                        $buffer = substr($buffer, 0, $adjustedBufferSize);
                    }
                    hash_update($ctx, $buffer);
                }
                $stream->close();
                $hash = hash_final($ctx, false);
                $sql = "UPDATE files set modified_at = modified_at, hash = :hash where id = :id";
                $params['id'] = $file->getId();
                $params['hash'] = $hash;
                $stmt = $this->entityManager->getConnection()->prepare($sql);
                $stmt->execute($params);
                echo "File: ".$file->getId()." Name: ".$file->getName(). " Hash: ".$hash. " Count = ".++$count."\n";
            }
        }
        return count($entries);
    }
}
