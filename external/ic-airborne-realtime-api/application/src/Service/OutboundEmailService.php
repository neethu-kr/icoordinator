<?php

namespace iCoordinator\Service;

use iCoordinator\Entity\File;
use iCoordinator\Entity\Folder;
use iCoordinator\Entity\Invitation;
use iCoordinator\Entity\Portal;
use iCoordinator\Entity\SharedLink;
use iCoordinator\Entity\User;
use iCoordinator\Factory\Service\OutboundEmail\AdapterFactory;

class OutboundEmailService extends AbstractService
{
    const TEMPLATE_LOCALE_EMAIL = 'locale-email-template';
    const TEMPLATE_SIGN_UP_CONFIRM_EMAIL = 'sign-up-confirm-email';
    const TEMPLATE_SIGN_UP_WELCOME = 'sign-up-welcome';
    const TEMPLATE_SIGN_UP_INVITATION = 'sign-up-invitation';
    const TEMPLATE_PORTAL_INVITATION = 'portal-invitation';
    const TEMPLATE_SIGN_UP_INVITATION_WELCOME = 'sign-up-invitation-welcome';
    const TEMPLATE_INBOUND_EMAIL_FILE_UPLOAD_SUCCESS = 'inbound-email-file-upload-success';
    const TEMPLATE_INBOUND_EMAIL_FILE_UPLOAD_INTERNAL_SERVER_ERROR = 'inbound-email-file-upload-internal-server-error';
    const TEMPLATE_INBOUND_EMAIL_FILE_UPLOAD_WRONG_EMAIL_ERROR = 'inbound-email-file-upload-wrong-email-error';
    const TEMPLATE_SHARED_FILE_NOTIFICATION = 'shared-file-notification';
    const TEMPLATE_SHARED_FILES_NOTIFICATION = 'shared-files-notification';
    const TEMPLATE_SHARED_FOLDER_NOTIFICATION = 'shared-folder-notification';
    const TEMPLATE_PASSWORD_RESET = 'password-reset';
    const TEMPLATE_SEND_EVENT_NOTIFICATION = 'send-event-notification';
    const TEMPLATE_COPY_WORKSPACE = 'copy-workspace';

    /**
     * @var OutboundEmail\Adapter\AdapterInterface
     */
    private $adapter;

    private function getBrandImageUrl($brand = null)
    {
        return getenv('BRAND_IMAGE_URL');
    }

    private function getBrandName($brand)
    {
        return getenv('BRAND_NAME');
    }

    private function getBrandCopyrightUrl($brand)
    {
        return getenv('COPYRIGHT_URL');
    }

    private function getBrandCopyrightName($brand)
    {
        return getenv('COPYRIGHT_NAME');
    }

    public function sendInboundFileUploadedNotification(array $files)
    {
        $fileNames = [];
        /** @var File $file */
        foreach ($files as $file) {
            $fileNames[] = $file->getName();
        }

        $this->getAdapter()->send(self::TEMPLATE_INBOUND_EMAIL_FILE_UPLOAD_SUCCESS, array(
            'file_names' => implode(', ', $fileNames),
            'parent_folder_name' => $file->getParent() ? $file->getParent()->getName() : null,
            'workspace_name' => $file->getWorkspace()->getName(),
            'brand_name' => $this->getBrandName(null),
            'brand_image_url' => $this->getBrandImageUrl(null),
            'brand_copyright_name' => $this->getBrandCopyrightName(null),
            'brand_copyright_url' => $this->getBrandCopyrightUrl(null),
            'web_base_url' => getenv('WEB_BASE_URL')
        ));
    }

    /**
     * @return OutboundEmail\Adapter\AdapterInterface
     * @throws \Exception
     */
    private function getAdapter()
    {
        if (!$this->adapter) {
            $this->adapter = AdapterFactory::createAdapter($this->getContainer());
        }
        return $this->adapter;
    }

    public function sendInboundFileUploadInternalServerErrorNotification()
    {
        //TODO: send error to administrator as well or log

        $this->getAdapter()->send(self::TEMPLATE_INBOUND_EMAIL_FILE_UPLOAD_INTERNAL_SERVER_ERROR);
    }

    public function sendInboundFileUploadWrongEmailErrorNotification($wrongEmail)
    {
        $this->getAdapter()->send(self::TEMPLATE_INBOUND_EMAIL_FILE_UPLOAD_WRONG_EMAIL_ERROR, array(
            'wrong_email' => $wrongEmail,
            'brand_name' => $this->getBrandName(null),
            'brand_image_url' => $this->getBrandImageUrl(null),
            'brand_copyright_name' => $this->getBrandCopyrightName(null),
            'brand_copyright_url' => $this->getBrandCopyrightUrl(null)

        ));
    }

    public function sendSignUpConfirmEmailNotification($confirmationUrl)
    {
        $this->getAdapter()->send(self::TEMPLATE_SIGN_UP_CONFIRM_EMAIL, array(
            'confirmation_url' => $confirmationUrl,
            'brand_name' => $this->getBrandName(null),
            'brand_image_url' => $this->getBrandImageUrl(null),
            'brand_copyright_name' => $this->getBrandCopyrightName(null),
            'brand_copyright_url' => $this->getBrandCopyrightUrl(null)
        ));
    }

    public function sendEmailChangeConfirmEmailNotification($confirmationUrl)
    {
        //TODO
    }

    public function sendPasswordResetNotification(User $user, $password)
    {
        $this->getAdapter()->send(self::TEMPLATE_PASSWORD_RESET, array(
            'email' => $user->getEmail(),
            'password' => $password,
            'brand_name' => $this->getBrandName(null),
            'brand_image_url' => $this->getBrandImageUrl(null),
            'brand_copyright_name' => $this->getBrandCopyrightName(null),
            'brand_copyright_url' => $this->getBrandCopyrightUrl(null)
        ));
    }

    public function sendSignUpWelcomeNotification(User $user, $password)
    {
        $this->getAdapter()->send(self::TEMPLATE_SIGN_UP_WELCOME, array(
            'email' => $user->getEmail(),
            'password' => $password,
            'brand_name' => $this->getBrandName(null),
            'brand_image_url' => $this->getBrandImageUrl(null),
            'brand_copyright_name' => $this->getBrandCopyrightName(null),
            'brand_copyright_url' => $this->getBrandCopyrightUrl(null)
        ));
    }

    public function sendPortalInvitationNotification(Invitation $invitation, $invitationUrl)
    {
        $this->getAdapter()->send(self::TEMPLATE_PORTAL_INVITATION, array(
            'invitation_url' => $invitationUrl,
            'invited_by_name' => $invitation->getCreatedBy()->getName() != '' ?
                $invitation->getCreatedBy()->getName() : $invitation->getCreatedBy()->getEmail(),
            'invited_by_email' => $invitation->getCreatedBy()->getEmail(),
            'portal_name' => $invitation->getPortal()->getName(),
            'brand_name' => $this->getBrandName(null),
            'brand_image_url' => $this->getBrandImageUrl(null),
            'brand_copyright_name' => $this->getBrandCopyrightName(null),
            'brand_copyright_url' => $this->getBrandCopyrightUrl(null)
        ));
    }

    public function sendSignUpInvitationNotification(Invitation $invitation, $invitationUrl)
    {
        $this->getAdapter()->send(self::TEMPLATE_SIGN_UP_INVITATION, array(
            'invitation_url' => $invitationUrl,
            'invited_by_name' => $invitation->getCreatedBy()->getName() != '' ?
                $invitation->getCreatedBy()->getName() : $invitation->getCreatedBy()->getEmail(),
            'invited_by_email' => $invitation->getCreatedBy()->getEmail(),
            'portal_name' => $invitation->getPortal()->getName(),
            'brand_name' => $this->getBrandName(null),
            'brand_image_url' => $this->getBrandImageUrl(null),
            'brand_copyright_name' => $this->getBrandCopyrightName(null),
            'brand_copyright_url' => $this->getBrandCopyrightUrl(null)
        ));
    }

    public function sendSignUpInvitationWelcomeNotification(User $user, $password, Portal $portal)
    {
        $this->getAdapter()->send(self::TEMPLATE_SIGN_UP_INVITATION_WELCOME, array(
            'email' => $user->getEmail(),
            'password' => $password,
            'portal_name' => $portal->getName(),
            'brand_name' => $this->getBrandName(null),
            'brand_image_url' => $this->getBrandImageUrl(null),
            'brand_copyright_name' => $this->getBrandCopyrightName(null),
            'brand_copyright_url' => $this->getBrandCopyrightUrl(null),
            'web_base_url' => getenv('WEB_BASE_URL')
        ));
    }

    public function sendSharedLinkNotification(SharedLink $sharedLink, $sharedLinkUrl, $sender, $message = null)
    {
        if ($sharedLink->getFile() instanceof Folder) {
            $template = self::TEMPLATE_SHARED_FOLDER_NOTIFICATION;
            $params = array(
                'folder_name' => $sharedLink->getFile()->getName()
            );
        } else {
            $template = self::TEMPLATE_SHARED_FILE_NOTIFICATION;
            $params = array(
                'file_name' => $sharedLink->getFile()->getName()
            );
        }

        $params = array_merge($params, array(
            /*'user_name' => $sharedLink->getCreatedBy()->getName() != '' ?
                $sharedLink->getCreatedBy()->getName() : $sharedLink->getCreatedBy()->getEmail(),
            */
            'user_name' => $sender->getName() != '' ?
                $sender->getName() : $sender->getEmail(),
            'message' => $message,
            'shared_link_url' => $sharedLinkUrl,
            'token' => $sharedLink->getToken(),
            'brand_name' => $this->getBrandName(null),
            'brand_image_url' => $this->getBrandImageUrl(null),
            'brand_copyright_name' => $this->getBrandCopyrightName(null),
            'brand_copyright_url' => $this->getBrandCopyrightUrl(null)
        ));

        $this->getAdapter()->send($template, $params);
    }

    public function sendMultipleSharedLinkNotification($root, $parent, $sharedLinkUrls, $sender, $message = null)
    {
        $template = self::TEMPLATE_SHARED_FILES_NOTIFICATION;
        if ($root) {
            $params = array(
                'folder_name' => "/",
                'workspace_name' => $parent->getName()
            );
        } else {
            $params = array(
                'folder_name' => $parent->getName(),
                'workspace_name' => $parent->getWorkspace()->getName()
            );
        }

        $params = array_merge($params, array(
            /*'user_name' => $sharedLink->getCreatedBy()->getName() != '' ?
                $sharedLink->getCreatedBy()->getName() : $sharedLink->getCreatedBy()->getEmail(),
            */
            'user_name' => $sender->getName() != '' ?
                $sender->getName() : $sender->getEmail(),
            'message' => $message,
            'shared_link_urls' => $sharedLinkUrls,
            //'token' => $sharedLink->getToken(),
            'brand_name' => $this->getBrandName(null),
            'brand_image_url' => $this->getBrandImageUrl(null),
            'brand_copyright_name' => $this->getBrandCopyrightName(null),
            'brand_copyright_url' => $this->getBrandCopyrightUrl(null)
        ));

        $this->getAdapter()->send($template, $params);
    }

    public function sendEventNotification(User $user, $notificationList, $brand)
    {
        $this->getAdapter()->send(self::TEMPLATE_SEND_EVENT_NOTIFICATION, array(
            'email' => $user->getEmail(),
            'notification_list' => $notificationList,
            'brand_name' => $this->getBrandName($brand),
            'brand_image_url' => $this->getBrandImageUrl($brand),
            'brand_copyright_name' => $this->getBrandCopyrightName($brand),
            'brand_copyright_url' => $this->getBrandCopyrightUrl($brand)
        ));
    }

    public function sendCopyWorkspaceCompleted(User $user, $newWorkspace, $brand)
    {
        $this->getAdapter()->send(self::TEMPLATE_COPY_WORKSPACE, array(
            'email' => $user->getEmail(),
            'new_workspace_name' => $newWorkspace->getName(),
            'brand_name' => $this->getBrandName($brand),
            'brand_image_url' => $this->getBrandImageUrl($brand),
            'brand_copyright_name' => $this->getBrandCopyrightName($brand),
            'brand_copyright_url' => $this->getBrandCopyrightUrl($brand)
        ));
    }

    /**
     * @param $toEmail
     * @param null $toName
     * @return $this
     */
    public function addTo($toEmail, $toName = null)
    {
        $this->getAdapter()->addTo($toEmail, $toName);
        return $this;
    }

    /**
     * @param $toEmail
     * @param null $toName
     * @return $this
     */
    public function setTo($toEmail, $toName = null)
    {
        $this->getAdapter()->setTo($toEmail, $toName);
        return $this;
    }

    /**
     * @param $toLang
     * @param null $toLang
     * @return $this
     */
    public function setLang($toLang)
    {
        $this->getAdapter()->setLang($toLang);
        return $this;
    }

    /**
     * @param $subject
     * @param null $subject
     * @return $this
     */
    public function setSubject($subject)
    {
        $this->getAdapter()->setSubject($subject);
        return $this;
    }
}
