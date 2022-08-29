<?php

namespace iCoordinator\Service;

use iCoordinator\Entity\AbstractEntity;
use iCoordinator\Entity\Folder;
use iCoordinator\Entity\InboundEmail;
use iCoordinator\Entity\User;
use iCoordinator\Entity\Workspace;
use iCoordinator\Service\Exception\ValidationFailedException;
use Laminas\Permissions\Acl\Resource\ResourceInterface;

class InboundEmailService extends AbstractService
{
    const ERROR_WRONG_WEBHOOK_FORMAT = 'error_wrong_hook_format';
    const ERROR_WRONG_ADDRESS = 'error_wrong_address';

    const ACTION_ADD_FILE_TO_WORKSPACE = 'add_file_to_workspace';
    const ACTION_ADD_FILE_TO_FOLDER = 'add_file_to_folder';
    const SAVE_EMAIL = 'saveemail';
    /**
     * @var ResourceInterface
     */
    private $resource;

    /**
     * @var string
     */
    private $action;

    /**
     * @var User;
     */
    private $user;


    /**
     * @var string
     */
    private $fromEmail;

    /**
     * @var string
     */
    private $toEmail;

    /**
     * @var string
     */
    private $fromName;

    /**
     * @var string
     */
    private $message;

    /**
     * @var boolean
     */
    private $saveEmail = false;

    /**
     * @var array
     */
    private $attachments;

    /**
     * @var OutboundEmailService
     */
    private $outboundEmailService;

    /**
     * @var FileService
     */
    private $fileService;

    /**
     * @var FolderService
     */
    private $folderService;

    /**
     * @param $data
     * @throws ValidationFailedException
     * @throws \Exception
     */
    public function setData($data)
    {
        if (empty($data['msg']) || !is_array($data['msg'])) {
            throw new \Exception('Wrong webhook format');
        }

        if (!empty($data['msg']['from_email'])) {
            $this->setFromEmail($data['msg']['from_email']);
        }

        if (!empty($data['msg']['from_name'])) {
            $this->setFromName($data['msg']['from_name']);
        }

        if ((empty($data['msg']['to']) || !is_array($data['msg']['to'])) &&
            (empty($data['msg']['cc']) || !is_array($data['msg']['cc'])) &&
            (empty($data['msg']['bcc']) || !is_array($data['msg']['bcc']))) {
            $this->error(self::ERROR_WRONG_WEBHOOK_FORMAT);
        }

        $recipientList = array();
        $to = array_key_exists('to', $data['msg']) ? $data['msg']['to'] : null;
        $cc = array_key_exists('cc', $data['msg']) ? $data['msg']['cc'] : null;
        $bcc = array_key_exists('bcc', $data['msg']) ? $data['msg']['bcc'] : null;
        $email = array_key_exists('email', $data['msg']) ? $data['msg']['email'] : null;

        if (is_array($to)) {
            $recipientList = array_merge($recipientList, $to);
        }
        if (is_array($cc)) {
            $recipientList = array_merge($recipientList, $cc);
        }
        if (is_array($bcc)) {
            $recipientList = array_merge($recipientList, $bcc);
        }
        if ($email!="") {
            $recipientList = array_merge($recipientList, [[$email,""]]);
        }

        foreach ($recipientList as $recipient) {
            $toParts = explode('@', $recipient[0]); // Name usually in [0] but sometimes email
            if (count($toParts)) {
                $toParts = explode('-', $toParts[0]);
                if (($toParts[0] == 'file' || $toParts[0] == 'saveemail' || $toParts[0] == 'workspace')
                    && count($toParts) >= 3
                ) {
                    $this->setToEmail($recipient[0]);
                    break; // Stops at first occurrence of inbound mail address
                }
            }
            $toParts = explode('@', $recipient[1]); // If name in [0] then email in [1]
            if (count($toParts)) {
                $toParts = explode('-', $toParts[0]);
                if (($toParts[0] == 'file' || $toParts[0] == 'saveemail' || $toParts[0] == 'workspace')
                    && count($toParts) >= 3
                ) {
                    $this->setToEmail($recipient[1]);
                    break; // Stops at first occurrence of inbound mail address
                }
            }
        }
        if (count($toParts) < 3) {
            $this->error(self::ERROR_WRONG_ADDRESS);
        }
        if ($toParts[0] == self::SAVE_EMAIL) {
            $this->saveEmail = true;
            array_shift($toParts);
        }
        $this->setResourceAndAction($toParts);

        $this->setUser($toParts);

        $this->setAttachments($data);

        $this->setEmailMessage($data);
    }

    private function setFromEmail($fromEmail)
    {
        $this->fromEmail = $fromEmail;
    }

    private function setFromName($fromName)
    {
        $this->fromName = $fromName;
    }

    private function error($errorType)
    {
        if ($this->getUser()) {
            if ($this->getUser()->getLocale()) {
                $userLang = $this->getUser()->getLocale()->getLang();
            } else {
                $userLang = 'en';
            }
        } else {
            $userLang = 'en';
        }

        $this->getOutboundEmailService()
            ->setTo($this->getFromEmail(), $this->getFromName())
            ->setLang($userLang);

        switch ($errorType) {
            case self::ERROR_WRONG_WEBHOOK_FORMAT:
                $this->getOutboundEmailService()->sendInboundFileUploadInternalServerErrorNotification();
                break;

            case self::ERROR_WRONG_ADDRESS:
                $this->getOutboundEmailService()->sendInboundFileUploadWrongEmailErrorNotification(
                    $this->getToEmail()
                );
                break;
        }

        throw new ValidationFailedException();
    }

    /**
     * @return string
     */
    public function getFromEmail()
    {
        return $this->fromEmail;
    }

    /**
     * @return string
     */
    public function getFromName()
    {
        return $this->fromName;
    }

    /**
     * @return string
     */
    public function getToEmail()
    {
        return $this->toEmail;
    }

    private function setToEmail($toEmail)
    {
        $this->toEmail = $toEmail;
    }

    private function setResourceAndAction(array $toParts)
    {
        $resource = null;
        switch ($toParts[0]) {
            case Workspace::RESOURCE_ID:
                $workspaceId = $toParts[1];

                $workspaceService = $this->getContainer()->get('WorkspaceService');
                $resource = $workspaceService->getWorkspace($workspaceId);

                $this->action = self::ACTION_ADD_FILE_TO_WORKSPACE;
                break;

            case Folder::RESOURCE_ID:
                $folderId = $toParts[1];

                $folderService = $this->getContainer()->get('FolderService');
                $resource = $folderService->getFolder($folderId);

                $this->action = self::ACTION_ADD_FILE_TO_FOLDER;
                break;

            default:
                $this->error(self::ERROR_WRONG_ADDRESS);
                break;
        }

        if (!$resource) {
            $this->error(self::ERROR_WRONG_ADDRESS);
        }
        $this->resource = $resource;
    }

    private function setUser(array $toParts)
    {
        array_shift($toParts);
        array_shift($toParts);
        $uuid = implode('-', $toParts);
        $userService = $this->getContainer()->get('UserService');
        $user = $userService->getUserByUuid($uuid);
        if (!$user) {
            $this->error(self::ERROR_WRONG_ADDRESS);
        }
        $this->user = $user;
    }

    private function setAttachments($data)
    {
        if (!empty($data['msg'])) {
            $attachments = '';
            if (!empty($data['msg']['attachments'])) {
                $attachments = $data['msg']['attachments'];
            }
            if (!is_array($attachments)) {
                if (!$this->saveEmail) {
                    $this->error(self::ERROR_WRONG_WEBHOOK_FORMAT);
                }
            } else {
                $this->attachments = $attachments;
            }
        } else {
            $this->error(self::ERROR_WRONG_WEBHOOK_FORMAT);
        }
    }

    private function setEmailMessage($data)
    {
        if (!empty($data['msg'])) {
            if (!empty($data['msg']['from_email'])) {
                $from = 'From: ';
                if (!empty($data['msg']['from_name'])) {
                    $from .= $data['msg']['from_name'] . ' ';
                }
                $this->message .= $from . ' &lt;' . $data['msg']['from_email'] . '&gt;<br>';
            }
            $this->message .= 'To: ' . $this->toEmail . '<br>';

            if (!empty($data['msg']['subject'])) {
                $this->message .= 'Subject: ' . $data['msg']['subject'] . '<br>';
            }
            if (!empty($data['msg']['html'])) {
                $this->message .= '<br>' . $data['msg']['html'];
            } elseif (!empty($data['msg']['text'])) {
                $this->message .= '<br>' . $data['msg']['text'];
            }
            $this->message .= '<br>';
        }
        $this->message = '<html>'.
        '<head><meta charset="'.mb_detect_encoding($this->message, "auto").'"></head>'.
        '<body>'.$this->message.'</body>'.
        '</html>';
    }

    public function process()
    {
        switch ($this->getAction()) {
            case self::ACTION_ADD_FILE_TO_WORKSPACE:
            case self::ACTION_ADD_FILE_TO_FOLDER:
                $files = [];
                $resource = $this->getResource();
                if ($resource instanceof Folder) {
                    $workspace = $resource->getWorkspace();
                    $parent = $resource;
                    $parentId = $parent->getId();
                } else {
                    $workspace = $resource;
                    $parent = null;
                    $parentId = null;
                }
                if ($this->saveEmail) {
                    $folderName = 'mail-' . date("ymdHis");
                    $folder = $this->getFolderService()->getByName($folderName, $workspace, $parent);
                    if (!$folder) {
                        $folder = $this->getFolderService()->createFolder([
                            'parent' => array("id" => $parentId),
                            'name' => $folderName
                        ], $workspace, $this->getUser());
                    }
                    $parent = $folder;
                    $parentId = $folder->getId();
                }
                if (is_array($this->getAttachments())) {
                    foreach ($this->getAttachments() as $attachment) {
                        $fileName = mb_decode_mimeheader($attachment['name']);
                        $this->message .= '<br>' . $fileName;

                        $file = $this->getFileService()->getByName($fileName, $workspace, $parent);

                        $content = $attachment['content'];
                        if ($attachment['base64']) {
                            $content = base64_decode($content);
                        }

                        if (!$file) {
                            $file = $this->getFileService()->createFile([
                                'parent_id' => $parentId,
                                'content' => $content,
                                'mime_type' => $attachment['type'],
                                'name' => $fileName
                            ], $workspace, $this->getUser());
                        } else {
                            $file = $this->getFileService()->updateFileContent($file, $this->getUser(), [
                                'content' => $content,
                                'mime_type' => $attachment['type']
                            ]);
                        }

                        $files[] = $file;
                    }
                }
                if ($this->saveEmail) {
                    $fileName = 'mail-' . date("ymdHis") . '.html';

                    $file = $this->getFileService()->getByName($fileName, $workspace, $parent);

                    if (!$file) {
                        $file = $this->getFileService()->createFile([
                            'parent_id' => $parentId,
                            'content' => $this->message,
                            'mime_type' => 'text/plain',
                            'name' => $fileName
                        ], $workspace, $this->getUser());
                    } else {
                        $file = $this->getFileService()->updateFileContent($file, $this->getUser(), [
                            'content' => $this->message,
                            'mime_type' => 'text/plain'
                        ]);
                    }
                    $files[] = $file;
                }
                return $this->success($files);
                break;
        }
    }

    /**
     * @return string
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * @return array
     */
    public function getAttachments()
    {
        return $this->attachments;
    }

    /**
     * @return ResourceInterface
     */
    public function getResource()
    {
        return $this->resource;
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    private function success(array $files)
    {
        $this->getOutboundEmailService()
            ->setTo($this->getFromEmail(), $this->getFromName())
            ->setLang($this->getUser()->getLocale()->getLang());

        $this->getOutboundEmailService()->sendInboundFileUploadedNotification($files);

        return $files;
    }

    public function getInboundEmail($user, AbstractEntity $resource)
    {
        if (is_numeric($user)) {
            $userId = $user;
            /** @var User $user */
            $user = $this->getEntityManager()->getReference(User::ENTITY_NAME, $userId);
        }

        $email = $resource->getResourceId() . '-' . $resource->getId() . '-' . $user->getUuid() .
            '@' . $this->getContainer()->get('settings')['email']['inbound_email_host'];

        $inboundEmail = new InboundEmail();
        $inboundEmail->setUser($user)
            ->setResource($resource)
            ->setEmail($email);

        return $inboundEmail;
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
     * @return FileService
     */
    private function getFileService()
    {
        if ($this->fileService === null) {
            $this->fileService = $this->getContainer()->get('FileService');
        }

        return $this->fileService;
    }

    /**
     * @return FolderService
     */
    private function getFolderService()
    {
        if ($this->folderService === null) {
            $this->folderService = $this->getContainer()->get('FolderService');
        }

        return $this->folderService;
    }
}
