<?php

namespace iCoordinator\Service;

use ChargifyV2\Client as ChargifyClient;
use ChargifyV2\DirectHelper as ChargifyDirectHelper;
use iCoordinator\Chargify\AbstractMapper;
use iCoordinator\Chargify\SignUpMapper;
use iCoordinator\Entity\EmailConfirmation;
use iCoordinator\Entity\Group;
use iCoordinator\Entity\HistoryEvent\InvitationHistoryEvent;
use iCoordinator\Entity\Invitation;
use iCoordinator\Entity\Invitation\InvitationWorkspace;
use iCoordinator\Entity\Invitation\InvitationWorkspaceGroup;
use iCoordinator\Entity\Portal;
use iCoordinator\Entity\Subscription;
use iCoordinator\Entity\User;
use iCoordinator\Entity\Workspace;
use iCoordinator\Permissions\PermissionType;
use iCoordinator\Service\Exception\ChargifyException;
use iCoordinator\Service\Exception\ConflictException;
use iCoordinator\Service\Exception\NotFoundException;
use iCoordinator\Service\Exception\ValidationFailedException;
use iCoordinator\Service\Helper\TokenHelper;
use iCoordinator\Service\Helper\UrlHelper;
use Laminas\Hydrator\ClassMethodsHydrator;
use PhpCollection\Map;
use Rhumsaa\Uuid\Uuid;

/**
 * Class UserService
 * @package iCoordinator\Service
 */
class SignUpService extends AbstractService
{
    /**
     * @var PermissionService
     */
    private $permissionService;

    /**
     * @var PortalService
     */
    private $portalService;

    /**
     * @var UserService
     */
    private $userService;

    /**
     * @var GroupService
     */
    private $groupService;

    /**
     * @var GroupService
     */
    private $historyEventService;

    /**
     * @var OutboundEmailService
     */
    private $outboundEmailService;

    /**
     * @var EmailConfirmationService
     */
    private $emailConfirmationService;

    /**
     * @var ChargifyDirectHelper
     */
    private $chargifyDirectHelper;

    /**
     * @var ChargifyClient
     */
    private $chargifyClient;

    /**
     * @param ChargifyDirectHelper $chargifyDirectHelper
     * @return $this
     */
    public function setChargifyDirectHelper(ChargifyDirectHelper $chargifyDirectHelper)
    {
        $this->chargifyDirectHelper = $chargifyDirectHelper;
        return $this;
    }

    /**
     * @param ChargifyClient $chargifyClient
     * @return $this
     */
    public function setChargifyClient(ChargifyClient $chargifyClient)
    {
        $this->chargifyClient = $chargifyClient;
        return $this;
    }

    /**
     * @param $websiteId
     * @param $redirectUrl
     * @return array
     * @throws \ChargifyV2\Exception
     */
    public function getSecureFields($websiteId, $redirectUrl)
    {
        $chargifySettings = $this->getContainer()->get('settings')['chargify'];
        if (!isset($chargifySettings[$websiteId])) {
            throw new \InvalidArgumentException('Website ID "' . $websiteId . '" is not found in chargify settings');
        }
        $chargifySettings = $chargifySettings[$websiteId];

        $chargifyDirectHelper = new ChargifyDirectHelper(
            $chargifySettings['api_id'],
            $chargifySettings['api_secret'],
            $redirectUrl
        );

        $chargifyDirectHelper->setData([
            'signup' => [
                'customer' => [
                    'reference' => 'v2-' . Uuid::uuid4()
                ]
            ]
        ]);

        return $chargifyDirectHelper->getSecureFields();
    }

    /**
     * @param array $data
     * @param AbstractMapper|null $chargifyMapper
     * @return User
     * @throws ChargifyException
     * @throws ConflictException
     * @throws ValidationFailedException
     * @throws \Exception
     */
    public function signUp(array $data, AbstractMapper $chargifyMapper = null)
    {
        if (!isset($data['user']) || !is_array($data['user']) || !isset($data['user']['email'])) {
            throw new \InvalidArgumentException("Not all fields defined for user param");
        }

        if (isset($data['portal'])) {
            if (!is_array($data['portal']) || !isset($data['portal']['name'])) {
                throw new \InvalidArgumentException("Not all fields defined for portal param");
            }
            if ($chargifyMapper === null) {
                $chargifyMapper = $this->getChargifySignupMapper($data);
            }
            $data = $this->mergeChargifyData($data, $chargifyMapper->getCustomer());

            $user   = $this->getUserService()->createUser($data['user']);
            $portal = $this->getPortalService()->createPortal($data['portal'], $user, false);

            $this->createSubscription($chargifyMapper->getNewSubscription(), $portal);
        } else {
            $user   = $this->getUserService()->createUser($data['user']);
        }

        $this->getEmailConfirmationService()->sendEmailConfirmation($user, EmailConfirmation::SCOPE_SIGN_UP);

        $this->getEntityManager()->flush();

        return $user;
    }

    /**
     * @param $data
     * @param null $invitedBy
     * @param bool $requiresEmailConfirmation
     * @return Invitation
     * @throws NotFoundException
     * @throws ValidationFailedException
     * @throws \Doctrine\ORM\ORMException
     */
    public function inviteUser($data, $invitedBy = null, $requiresEmailConfirmation = true)
    {
        if (empty($data['email'])) {
            throw new ValidationFailedException();
        }

        if (!isset($data['portal']) || empty($data['portal']['id'])) {
            throw new ValidationFailedException();
        }

        $portal = $this->getEntityManager()->getReference(Portal::getEntityName(), $data['portal']['id']);

        if (!$portal) {
            throw new NotFoundException();
        }

        if (is_numeric($invitedBy)) {
            $userId = $invitedBy;
            /** @var User $invitedBy */
            $invitedBy = $this->getEntityManager()->getReference(User::ENTITY_NAME, $userId);
        }

        $userExists = $this->getUserService()->checkEmailExists($data['email']);

        $invitation = new Invitation();
        $invitation->setPortal($portal)
            ->setToken(TokenHelper::getSecureToken(16))
            ->setCreatedBy($invitedBy);
        unset($data['portal']);

        /*if (isset($data['group'])) {
            if (isset($data['group']['id'])) {
                $group = $this->getEntityManager()->getReference(Group\PortalGroup::ENTITY_NAME, $data['group']['id']);
                $invitation->setGroup($group);
            }
            unset($data['group']);
        }*/
        if (isset($data['groups'])) {
            foreach ($data['groups'] as $g) {
                if (isset($g['id'])) {
                    $group = $this->getEntityManager()->getReference(Group\WorkspaceGroup::ENTITY_NAME, $g['id']);
                    $invitationWorkspaceGroup = new InvitationWorkspaceGroup();
                    $invitationWorkspaceGroup->setGroup($group);
                    $invitation->addWorkspaceGroup($invitationWorkspaceGroup);
                }
            }
            unset($data['groups']);
        }

        if (isset($data['workspaces'])) {
            foreach ($data['workspaces'] as $ws) {
                if (isset($ws['id'])) {
                    $workspace = $this->getEntityManager()->getReference(Workspace::ENTITY_NAME, $ws['id']);
                    $invitationWorkspace = new InvitationWorkspace();
                    $invitationWorkspace->setWorkspace($workspace);
                    $invitation->addWorkspace($invitationWorkspace);
                }
            }
            unset($data['workspaces']);
        }

        if (!empty($data)) {
            $hydrator = new ClassMethodsHydrator();
            $hydrator->hydrate($data, $invitation);
        }

        $this->getEntityManager()->persist($invitation);
        $this->getEntityManager()->flush();

        if ($requiresEmailConfirmation) {
            $invitationUrl =  $this->getInvitationUrl($invitation);

            //send confirmation email
            if ($userExists) {
                $user = $this->getUserService()->getUserByEmail($data['email']);
                if ($user->getLocale()) {
                    $userLang = $user->getLocale()->getLang();
                } else {
                    $userLang = 'en';
                }
                $this->getOutboundEmailService()
                    ->setTo($data['email'])
                    ->setLang($userLang)
                    ->sendPortalInvitationNotification($invitation, $invitationUrl);
            } else {
                $this->getOutboundEmailService()
                    ->setTo($data['email'])
                    ->setLang('en')
                    ->sendSignUpInvitationNotification($invitation, $invitationUrl);
            }
            $this->getHistoryEventService()->addEvent(
                InvitationHistoryEvent::TYPE_PORTAL_INVITATION_SENT,
                $invitation,
                $invitedBy,
                $data['email']
            );
        } else {
            /*if(isset($data['password'])) {
                $this->getHistoryEventService()->addEvent(
                    InvitationHistoryEvent::TYPE_PORTAL_INVITATION_SENT,
                    $invitation,
                    $invitedBy,
                    $data['email']
                );
            }*/
            $this->acceptInvitation(
                $invitation->getToken(),
                isset($data['password']) ? $data['password'] : null,
                !isset($data['password']),
                !isset($data['password'])
            );
        }

        $user = $this->getUserService()->getUserByEmail($data['email']);
        if ($user != null) {
            $invitation->setInvitedUserId($user->getId());
        }
        return $invitation;
    }


    /**
     * @param $invitationId
     * @return Invitation
     * @throws NotFoundException
     */
    public function resendInvitation($invitationId)
    {
        $invitation = $this->getInvitationById($invitationId);
        if (!$invitation) {
            throw new NotFoundException();
        }
        $invitationUrl =  $this->getInvitationUrl($invitation);
        $email = $invitation->getEmail();

        $userExists = $this->getUserService()->checkEmailExists($email);
        //send confirmation email
        if ($userExists) {
            $user = $this->getUserService()->getUserByEmail($email);
            if ($user->getLocale()) {
                $userLang = $user->getLocale()->getLang();
            } else {
                $userLang = 'en';
            }
            $this->getOutboundEmailService()
                ->setTo($email)
                ->setLang($userLang)
                ->sendPortalInvitationNotification($invitation, $invitationUrl);
        } else {
            $this->getOutboundEmailService()
                ->setTo($email)
                ->setLang('en')
                ->sendSignUpInvitationNotification($invitation, $invitationUrl);
        }
        $this->getHistoryEventService()->addEvent(
            InvitationHistoryEvent::TYPE_PORTAL_INVITATION_RESENT,
            $invitation,
            $invitation->getCreatedBy(),
            $email
        );
        return $invitation;
    }
    /**
     * @param $portal
     * @return array
     * @throws \Doctrine\ORM\ORMException
     */
    public function getPortalInvitations($portal)
    {
        if (is_numeric($portal)) {
            $portalId = $portal;
            /** @var Portal $portal */
            $portal = $this->getEntityManager()->getReference(Portal::ENTITY_NAME, $portalId);
        }

        $invitations = $this->getEntityManager()->getRepository(Invitation::ENTITY_NAME)->findBy([
           'portal' => $portal
        ]);

        return $invitations;
    }

    private function getInvitationUrl(Invitation $invitation)
    {
        return UrlHelper::getWebApplicationBaseUrl(
            $this->getContainer(),
            '#/invitations/accept/' . urlencode($invitation->getToken())
        );
    }

    /**
     * @param $token
     * @return Portal
     * @throws ConflictException
     * @throws NotFoundException
     * @throws ValidationFailedException
     * @throws \Exception
     */
    public function acceptInvitation($token, $password = null, $sendWelcomeMsg = true, $supressHistoryEvent = false)
    {
        $invitation = $this->getInvitationByToken($token);

        if (!$invitation) {
            throw new NotFoundException();
        }

        $email = $invitation->getEmail();
        $name = $invitation->getFirstName().' '.$invitation->getLastName();

        $existingUser = $this->getUserService()->checkEmailExists($email);

        if (!$existingUser) {
            if ($password != null) {
                $newUser = [
                    'email' => $email,
                    'name' => $name,
                    'email_confirmed' => true,
                    'password' => $password
                ];
            } else {
                $newUser = [
                    'email' => $email,
                    'name' => $name,
                    'email_confirmed' => true
                ];
            }
            $user = $this->getUserService()->createUser($newUser);
        } else {
            $user = $this->getUserService()->getUserByEmail($email);
            if (!$user->isEmailConfirmed()) {
                $user->setEmailConfirmed(true);
            }
        }

        $portal = $invitation->getPortal();

        //give permission to the portal
        $this->getPermissionService()->addPermission(
            $portal,
            $user,
            [PermissionType::PORTAL_ACCESS],
            $invitation->getCreatedBy(),
            $portal
        );
        if (!$supressHistoryEvent) {
            $this->getHistoryEventService()->addEvent(
                InvitationHistoryEvent::TYPE_PORTAL_INVITATION_ACCEPTED,
                $invitation,
                $invitation->getCreatedBy(),
                $user->getId()
            );
        }
        $invitationWorkspaces = $invitation->getWorkspaces();
        foreach ($invitationWorkspaces as $invitationWorkspace) {
            $workspace = $invitationWorkspace->getWorkspace();
            if ($workspace) {
                //give permission to the workspace
                $this->getPermissionService()->addPermission(
                    $workspace,
                    $user,
                    [PermissionType::WORKSPACE_ACCESS],
                    $invitation->getCreatedBy(),
                    $portal
                );
            }
        }

        $invitationWorkspaceGroups = $invitation->getWorkspaceGroups();
        foreach ($invitationWorkspaceGroups as $invitationWorkspaceGroup) {
            $group = $invitationWorkspaceGroup->getGroup();
            if ($group) {
                //add user to the group
                $this->getGroupService()->createGroupMembership($group, $user, $invitation->getCreatedBy(), false);
            }
        }

        //remove invitation
        $this->getEntityManager()->remove($invitation);
        $this->getEntityManager()->flush();

        //send welcome email if user was just registered
        if (!$existingUser && $sendWelcomeMsg) {
            $this->getOutboundEmailService()
                ->setTo($user->getEmail())
                ->setLang($user->getLocale()->getLang())
                ->sendSignUpInvitationWelcomeNotification($user, $user->getRawPassword(), $portal);
        }

        return $portal;
    }

    /**
     * @param $invitationId
     */
    public function deleteInvitation($invitationId)
    {
        $invitation = $this->getInvitationById($invitationId);
        $this->getHistoryEventService()->addEvent(
            InvitationHistoryEvent::TYPE_PORTAL_INVITATION_DELETE,
            $invitation,
            $invitation->getCreatedBy(),
            $invitation->getEmail()
        );
        if ($invitation) {
            $this->getEntityManager()->remove($invitation);
            $this->getEntityManager()->flush();
        }
    }

    public function createSubscription(Subscription $subscription, Portal $portal)
    {
        $subscription->setPortal($portal);
        $this->getEntityManager()->persist($subscription);
    }

    /**
     * @param $token
     * @return null|Invitation
     */
    public function getInvitationByToken($token)
    {
        $repository = $this->getEntityManager()->getRepository(Invitation::ENTITY_NAME);
        $invitation = $repository->findOneBy(array(
            'token' => $token
        ));

        return $invitation;
    }

    /**
     * @param $invitationId
     * @return null|Invitation
     */
    public function getInvitationById($invitationId)
    {
        $repository = $this->getEntityManager()->getRepository(Invitation::ENTITY_NAME);
        $invitation = $repository->findOneBy(array(
            'id' => $invitationId
        ));

        return $invitation;
    }

    /**
     * @param $data
     * @return SignUpMapper
     * @throws ChargifyException
     */
    private function getChargifySignUpMapper($data)
    {
        $requiredKeys = [
            'website_id',
            'signature',
            'api_id',
            'timestamp',
            'nonce',
            'status_code',
            'result_code',
            'call_id'
        ];

        if (!isset($data['chargify'])
            || !is_array($data['chargify'])
            || count(array_intersect_key(array_flip($requiredKeys), $data['chargify'])) !== count($requiredKeys)
        ) {
            throw new \InvalidArgumentException("Not all fields defined for chargify param");
        }

        $data = $data['chargify'];

        $chargifyClient = $this->getChargifyClient();
        if (!$chargifyClient) {
            $chargifyClient = $this->createChargifyClient($data['website_id']);
        }

        $chargifyDirectHelper = $this->getChargifyDirectHelper();
        if (!$chargifyDirectHelper) {
            $chargifyDirectHelper = $this->createChargifyDirectHelper($data['website_id']);
        }

        if (!$chargifyDirectHelper->isValidResponseSignature(
            $data['signature'],
            $data['api_id'],
            $data['timestamp'],
            $data['nonce'],
            $data['status_code'],
            $data['result_code'],
            $data['call_id']
        )) {
            throw new \InvalidArgumentException('Chargify signature is not valid');
        }

        $callInfo           = $chargifyClient->getCall($data['call_id']);
        $signUpResponse     = $callInfo->call->response;
        //TODO: tmp fix until chargify fixes bug with components in response
        $signUpRequest      = $callInfo->call->request;

        if ($signUpResponse->result->status_code != 200) {
            throw new ChargifyException($signUpResponse->result->errors);
        }

        $chargifySignUpMapper = new SignUpMapper(
            $signUpResponse,
            $signUpRequest,
            $data['website_id'],
            $this->getEntityManager()
        );

        return $chargifySignUpMapper;
    }

    /**
     * @param $data
     * @param Map $chargifyCustomer
     * @return User
     */
    private function mergeChargifyData($data, Map $chargifyCustomer)
    {
        //set name
        $nameParts = [
            $chargifyCustomer->get('first_name')->getOrElse(''),
            $chargifyCustomer->get('last_name')->getOrElse('')
        ];
        $data['user']['name'] = trim(implode(' ', $nameParts));

        //set address
        $addressParts = [
            $chargifyCustomer->get('address')->getOrElse(''),
            $chargifyCustomer->get('address_2')->getOrElse('')
        ];
        $data['user']['address'] = trim(implode(' ', $addressParts));

        //set phone
        $data['user']['phone'] = $chargifyCustomer->get('phone')->getOrElse(null);

        return $data;
    }

    /**
     * @return ChargifyDirectHelper
     */
    private function getChargifyDirectHelper()
    {
        return $this->chargifyDirectHelper;
    }

    /**
     * @return ChargifyClient
     */
    private function getChargifyClient()
    {
        return $this->chargifyClient;
    }

    /**
     * @param $websiteId
     * @return ChargifyDirectHelper
     */
    private function createChargifyDirectHelper($websiteId)
    {
        $chargifySettings = $this->getContainer()->get('settings')['chargify'];
        if (!isset($chargifySettings[$websiteId])) {
            throw new \InvalidArgumentException('Website ID "' . $websiteId . '" is not found in chargify settings');
        }

        return  new ChargifyDirectHelper(
            $chargifySettings[$websiteId]['api_id'],
            $chargifySettings[$websiteId]['api_secret']
        );
    }

    /**
     * @param $websiteId
     * @return ChargifyClient
     */
    private function createChargifyClient($websiteId)
    {
        $chargifySettings = $this->getContainer()->get('settings')['chargify'];
        if (!isset($chargifySettings[$websiteId])) {
            throw new \InvalidArgumentException('Website ID "' . $websiteId . '" is not found in chargify settings');
        }

        return new ChargifyClient(
            $chargifySettings[$websiteId]['api_id'],
            $chargifySettings[$websiteId]['api_password']
        );
    }

    /**
     * @return UserService
     */
    private function getUserService()
    {
        if (!$this->userService) {
            $this->userService = $this->getContainer()->get('UserService');
        }
        return $this->getContainer()->get('UserService');
    }

    /**
     * @return PermissionService
     */
    private function getPermissionService()
    {
        if (!$this->permissionService) {
            $this->permissionService = $this->getContainer()->get('PermissionService');
        }
        return $this->permissionService;
    }

    /**
     * @return PortalService
     */
    private function getPortalService()
    {
        if (!$this->portalService) {
            $this->portalService = $this->getContainer()->get('PortalService');
        }
        return $this->portalService;
    }

    private function getEmailConfirmationService()
    {
        if (!$this->emailConfirmationService) {
            $this->emailConfirmationService = $this->getContainer()->get('EmailConfirmationService');
        }
        return $this->emailConfirmationService;
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

    private function getGroupService()
    {
        if (!$this->groupService) {
            $this->groupService = $this->getContainer()->get('GroupService');
        }

        return $this->groupService;
    }

    private function getHistoryEventService()
    {
        if (!$this->historyEventService) {
            $this->historyEventService = $this->getContainer()->get('HistoryEventService');
        }

        return $this->historyEventService;
    }
}
