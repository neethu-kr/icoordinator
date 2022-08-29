<?php

namespace iCoordinator\Service;

use iCoordinator\Entity\EmailConfirmation;
use iCoordinator\Entity\User;
use iCoordinator\Service\Exception\NotFoundException;
use iCoordinator\Service\Helper\UrlHelper;
use phpseclib\Crypt\AES;

class EmailConfirmationService extends AbstractService
{
    /**
     * @var OutboundEmailService
     */
    private $outboundEmailService;


    /**
     * INFO: DB changes not flushed!
     *
     * @param User $user
     * @param $scope
     * @return bool
     * @throws \Exception
     */
    public function sendEmailConfirmation(User $user, $scope)
    {
        if ($user->isEmailConfirmed()) {
            throw new \Exception('User email is already confirmed');
        }

        $emailConfirmation = $this->getEmailConfirmationByUser($user);

        $token = $this->getEmailConfirmationToken($user, $scope);

        if ($emailConfirmation) {
            $emailConfirmation->setToken($token);
        } else {
            $emailConfirmation = new EmailConfirmation();
            $emailConfirmation->setUser($user);
            $emailConfirmation->setToken($token);
            $this->getEntityManager()->persist($emailConfirmation);
        }

        //send confirmation email
        $this->getOutboundEmailService()
            ->setTo($user->getEmail())
            ->setLang($user->getLocale()->getLang());

        switch ($scope) {
            case EmailConfirmation::SCOPE_SIGN_UP:
                $confirmationUrl =  $this->getSignUpEmailConfirmationUrl($emailConfirmation);
                $this->getOutboundEmailService()->sendSignUpConfirmEmailNotification(
                    $confirmationUrl
                );
                break;
            case EmailConfirmation::SCOPE_CHANGE_EMAIL:
                $confirmationUrl =  $this->getEmailChangeConfirmationUrl($emailConfirmation);
                $this->getOutboundEmailService()->sendEmailChangeConfirmEmailNotification(
                    $confirmationUrl
                );
                break;
        }

        return true;
    }

    /**
     * @param $token
     * @return User
     * @throws NotFoundException
     */
    public function signUpConfirmEmail($token)
    {
        $emailConfirmation = $this->getEmailConfirmationByToken($token);

        $password = $this->tokenToPassword($token);

        if (!$emailConfirmation) {
            throw new NotFoundException();
        }

        $user = $emailConfirmation->getUser();
        $user->setEmailConfirmed(true);

        $this->getEntityManager()->remove($emailConfirmation);
        $this->getEntityManager()->flush();

        //send welcome email
        if ($user->getLocale()) {
            $userLang = $user->getLocale()->getLang();
        } else {
            $userLang = 'en';
        }
        $this->getOutboundEmailService()
            ->setTo($user->getEmail())
            ->setLang($userLang)
            ->sendSignUpWelcomeNotification($user, $password);

        return $user;
    }

    /**
     * @param $token
     * @return null|EmailConfirmation
     */
    public function getEmailConfirmationByToken($token)
    {
        $repository = $this->getEntityManager()->getRepository(EmailConfirmation::ENTITY_NAME);
        $emailConfirmation = $repository->findOneBy(array(
            'token' => $token
        ));

        return $emailConfirmation;
    }

    /**
     * @param User $user
     * @return null|EmailConfirmation
     */
    public function getEmailConfirmationByUser(User $user)
    {
        $repository = $this->getEntityManager()->getRepository(EmailConfirmation::ENTITY_NAME);
        $emailConfirmation = $repository->findOneBy(array(
            'user' => $user
        ));

        return $emailConfirmation;
    }

    /**
     * @param EmailConfirmation $emailConfirmation
     * @return string
     */
    private function getSignUpEmailConfirmationUrl(EmailConfirmation $emailConfirmation)
    {
        return UrlHelper::getWebApplicationBaseUrl(
            $this->getContainer(),
            '#/sign-up/confirm-email/' . urlencode($emailConfirmation->getToken())
        );
    }

    private function getEmailChangeConfirmationUrl(EmailConfirmation $emailConfirmation)
    {
        //TODO
    }

    /**
     * @param User $user
     * @param $scope
     * @return string
     */
    private function getEmailConfirmationToken(User $user, $scope)
    {
        switch ($scope) {
            case EmailConfirmation::SCOPE_SIGN_UP:
                $token = $this->passwordToToken($user->getEmail(), $user->getRawPassword());
                break;
            case EmailConfirmation::SCOPE_CHANGE_EMAIL:
                $token = bin2hex(openssl_random_pseudo_bytes(16));
                ;
                //TODO
                break;
            default:
                throw new \InvalidArgumentException('Unrecognized email confirmation scope: "' . $scope . '"');
        }

        return $token;
    }

    /**
     * @param $password
     * @return string
     */
    public function passwordToToken($email, $password)
    {
        $encryptor = $this->getTokenEncryptor();
        return base64_encode($encryptor->encrypt(implode('|', [$email, $password])));
    }

    private function tokenToPassword($token)
    {
        $encryptor = $this->getTokenEncryptor();
        list($email, $password) = explode('|', $encryptor->decrypt(base64_decode($token)));
        return $password;
    }

    /**
     * @return AES
     */
    private function getTokenEncryptor()
    {
        return $this->getContainer()->get('TokenEncryptor');
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
}
