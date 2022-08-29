<?php

namespace iCoordinator\Controller;

use Carbon\Carbon;
use Datetime;
use iCoordinator\Entity\Acl\AclPermission;
use iCoordinator\Entity\File;
use iCoordinator\Entity\Group;
use iCoordinator\Entity\Invitation;
use iCoordinator\Entity\MetaField;
use iCoordinator\Entity\MetaFieldValue;
use iCoordinator\Entity\Portal;
use iCoordinator\Entity\SmartFolder;
use iCoordinator\Entity\User;
use iCoordinator\Entity\Workspace;
use iCoordinator\Permissions\PermissionType;
use iCoordinator\Permissions\Privilege\PortalPrivilege;
use iCoordinator\Permissions\Resource\FileResource;
use iCoordinator\Permissions\Role\UserRole;
use iCoordinator\Service\HistoryEventService;
use Slim\Http\Request;
use Slim\Http\Response;

class HistoryEventsController extends AbstractRestController
{
    private $sources;

    private function populateSelectedEntry($entry, $source)
    {
        $historyEventService = $this->getHistoryEventService();
        if (isset($this->sources['portal'][$entry['portal_id']])) {
            $portal = $this->sources['portal'][$entry['portal_id']];
        } else {
            $portal = $historyEventService->getEntity(Portal::ENTITY_NAME, $entry['portal_id']);
            if ($portal) {
                $this->sources['portal'][$entry['portal_id']] = $portal;
            }
        }
        unset($entry['portal_id']);
        $entry['portal'] = $portal;
        if (isset($this->sources['workspace'][$entry['workspace_id']])) {
            $workspace = $this->sources['workspace'][$entry['workspace_id']];
        } else {
            $workspace = $historyEventService->getEntity(Workspace::ENTITY_NAME, $entry['workspace_id']);
            if ($workspace) {
                $this->sources['workspace'][$entry['workspace_id']] = $workspace;
            }
        }
        unset($entry['workspace_id']);
        $entry['workspace'] = $workspace;
        if (isset($this->sources['user'][$entry['created_by']])) {
            $user = $this->sources['user'][$entry['created_by']];
        } else {
            $user = $historyEventService->getEntity(User::ENTITY_NAME, $entry['created_by']);
            if ($user) {
                $this->sources['user'][$entry['created_by']] = $user;
            }
        }
        $entry['created_by'] = $user;
        if (!is_null($entry['group_user'])) {
            if (isset($this->sources['user'][$entry['group_user']])) {
                $group_user = $this->sources['user'][$entry['group_user']];
            } else {
                $group_user = $historyEventService->getEntity(User::ENTITY_NAME, $entry['group_user']);
                if ($group_user) {
                    $this->sources['user'][$entry['group_user']] = $group_user;
                }
            }
            $entry['group_user'] = $group_user;
        }
        unset($entry['source_id']);
        $entry['source'] = $source;

        return $entry;
    }
    public function getHistoryEventsAction(Request $request, Response $response, $args)
    {

        $lastOffset = 0;
        $auth = $this->getAuth();
        $acl = $this->getAcl();
        $editPermission = PermissionType::getPermissionTypeBitMask(File::RESOURCE_ID, PermissionType::FILE_EDIT);
        $readPermission = PermissionType::getPermissionTypeBitMask(File::RESOURCE_ID, PermissionType::FILE_READ);
        $grantEditPermission = PermissionType::getPermissionTypeBitMask(
            File::RESOURCE_ID,
            PermissionType::FILE_GRANT_EDIT
        );
        $grantReadPermission = PermissionType::getPermissionTypeBitMask(
            File::RESOURCE_ID,
            PermissionType::FILE_GRANT_READ
        );


        $allowed = $readPermission | $editPermission | $grantReadPermission | $grantEditPermission;

        $portal = $request->getParam('portal', null);
        $workspace = $request->getParam('workspace', null);
        $startDate = $request->getParam('start_date', (new DateTime())->format('Y-m-d'));
        $limit = $request->getParam('limit', 500);
        $offset = $request->getParam('offset', 0);

        $historyEventService = $this->getHistoryEventService();

        $userId = $auth->getIdentity();
        $entries = $historyEventService->getUserHistoryEvents(
            $userId,
            new DateTime($startDate),
            $portal,
            $workspace,
            $limit,
            $offset
        );
        $numEntries = count($entries);
        if (count($entries)) {
            $lastOffset = $entries[count($entries) - 1]['id'];
        }
        //$entries = $paginator->getIterator()->getArrayCopy();
        $selectedEntries = array();
        foreach ($entries as $index => $entry) {
        /*    $source = $entry->getSource();
            if ($source instanceof File || $source instanceof Folder) {
                if ($this->getFileService()->userIsAllowed($userId, $source) ||
                   $this->getPermissionService()->isInResourceUserIds($userId, $source)) {
                    array_push($selectedEntries, $entry);
                }
            } elseif ($source instanceof AclPermission) {
                $permissionSource = $source->getAclResource()->getResource();
                if ($this->getPermissionService()->isInResourceUserIds($userId, $permissionSource)) {
                    array_push($selectedEntries, $entry);
                }
            } elseif ($source instanceof MetaField) {
                //$this->getPermissionService()->isInResourceUserIds($userId, $source);
                array_push($selectedEntries, $entry);
            } elseif ($source instanceof MetaFieldValue) {
                //$this->getPermissionService()->isInResourceUserIds($userId, $source);
                array_push($selectedEntries, $entry);
            } elseif ($source instanceof Workspace) {
                if ($this->getPermissionService()->isInResourceUserIds($userId, $source)) {
                    array_push($selectedEntries, $entry);
                }
            } elseif ($source instanceof SmartFolder) {
                if ($this->getPermissionService()->isInResourceUserIds($userId, $source)) {
                    array_push($selectedEntries, $entry);
                }
            } elseif ($source instanceof Invitation) {
                //$this->getPermissionService()->isInResourceUserIds($userId, $source);
                array_push($selectedEntries, $entry);
            } elseif ($source instanceof Portal) {
                if ($this->getPermissionService()->isInResourceUserIds($userId, $source)) {
                    array_push($selectedEntries, $entry);
                }
            } elseif ($source instanceof User) {
                $role = new UserRole($auth->getIdentity());
                $privilege = PortalPrivilege::PRIVILEGE_GRANT_ADMIN_PERMISSION;
                if ($acl->isAllowed($role, SystemResource::RESOURCE_ID, $privilege)) {
                    array_push($selectedEntries, $entry);
                }

            } elseif ($source instanceof Group) {
                //$this->getPermissionService()->isInResourceUserIds($userId, $source);
                array_push($selectedEntries, $entry);
            }
            if (count($selectedEntries)>=$limit) {
                break;
            }
        */
            $entry['created_at'] = Carbon::createFromFormat(
                'Y-m-d H:i:s',
                $entry['created_at']
            )->format(DateTime::ISO8601);
            if (isset($entry['source_id'])) {
                switch ($entry['source_type']) {
                    case 'file':
                    case 'folder':
                        if (isset($this->sources['file'][$entry['source_id']])) {
                            $source = $this->sources['file'][$entry['source_id']];
                        } else {
                            $source = $historyEventService->getEntity(File::ENTITY_NAME, $entry['source_id']);
                            if ($source) {
                                $this->sources['file'][$entry['source_id']] = $source;
                            }
                        }
                        if ($entry['type'] == 'FILE_MOVE' || $entry['type'] == 'FOLDER_MOVE') {
                            $parentNameBefore = $parentNameAfter = '';
                            $workspaceNameBefore = $workspaceNameAfter = '';
                            $portalNameBefore = $portalNameAfter = '';
                            $workspaceBefore = $workspaceAfter = null;
                            $portalBefore = $portalAfter = null;

                            $beforeAfter = preg_split('/:/', $entry['description']);
                            $parentBefore = $beforeAfter[0];
                            $parentAfter = $beforeAfter[1];
                            if ($parentBefore) {
                                $parentBefore = $historyEventService->getEntity(File::ENTITY_NAME, $parentBefore);
                                $parentNameBefore = $parentBefore->getName();
                                $workspaceBefore = $parentBefore->getWorkspace();
                                $workspaceNameBefore = $workspaceBefore->getName();
                                $portalBefore = $workspaceBefore->getPortal();
                                $portalNameBefore = $portalBefore->getName();
                            }
                            if ($parentAfter) {
                                $parentAfter = $historyEventService->getEntity(File::ENTITY_NAME, $parentAfter);
                                $parentNameAfter = $parentAfter->getName();
                                $workspaceAfter = $parentAfter->getWorkspace();
                                $workspaceNameAfter = $workspaceAfter->getName();
                                $portalAfter = $workspaceAfter->getPortal();
                                $portalNameAfter = $portalAfter->getName();
                            }
                            $entry['description'] = $parentNameBefore . ':' . $parentNameAfter;
                            if ($workspaceAfter != $workspaceBefore) {
                                $entry['description'] .= ':' . $workspaceNameBefore . ':' . $workspaceNameAfter;
                                if ($portalAfter != $portalBefore) {
                                    $entry['description'] .= ':' . $portalNameBefore . ':' . $portalNameAfter;
                                }
                            }
                        }
                        if ($source) {
                            if ($this->getFileService()->userIsAllowed($userId, $source) ||
                                $this->getFileService()->getFileFolderHighestPermission($source, $userId) & $allowed
                            ) {
                                if ($entry['type'] == 'FILE_SHARED_LINK_CREATE' ||
                                        $entry['type'] == 'FOLDER_SHARED_LINK_CREATE'
                                ) {
                                    $parts = preg_split('/:/', $entry['description']);
                                    if ($parts[1] == 'restricted') {
                                        // Skip default created entry when uploading/creating from web user interface
                                    } else {
                                        array_push($selectedEntries, $this->populateSelectedEntry($entry, $source));
                                    }
                                } else {
                                    array_push($selectedEntries, $this->populateSelectedEntry($entry, $source));
                                }
                            }
                        }
                        break;
                    case 'permission':
                        if (isset($this->sources['permission'][$entry['source_id']])) {
                            $source = $this->sources['permission'][$entry['source_id']];
                        } else {
                            $source = $historyEventService->getEntity(
                                AclPermission::ENTITY_NAME,
                                $entry['source_id']
                            );
                            if ($source) {
                                $this->sources['permission'][$entry['source_id']] = $source;
                            }
                        }
                        if ($source) {
                            $permissionSource = $source->getAclResource()->getResource();
                            $isadmin = false;
                            if ($permissionSource->getEntityName() == Workspace::ENTITY_NAME) {
                                $role = new UserRole($userId);
                                if (is_numeric($portal)) {
                                    $portalId = $portal;
                                    /** @var Portal $portal */
                                    $portal = $historyEventService->getEntity(
                                        Portal::ENTITY_NAME,
                                        $portalId
                                    );
                                }
                                $isadmin = $acl->isAllowed(
                                    $role,
                                    $portal,
                                    PortalPrivilege::PRIVILEGE_MANAGE_WORKSPACES
                                );
                            }
                            if ($isadmin ||
                                $this->getPermissionService()->isInResourceUserIds($userId, $permissionSource)) {
                                array_push($selectedEntries, $this->populateSelectedEntry($entry, $source));
                            }
                        }
                        break;
                    case 'metafield':
                        if (isset($this->sources['metafield'][$entry['source_id']])) {
                            $source = $this->sources['metafield'][$entry['source_id']];
                        } else {
                            $source = $historyEventService->getEntity(
                                MetaField::ENTITY_NAME,
                                $entry['source_id']
                            );
                            if ($source) {
                                $this->sources['metafield'][$entry['source_id']] = $source;
                            }
                        }
                        //if ($source) {
                            array_push($selectedEntries, $this->populateSelectedEntry($entry, $source));
                        //}
                        break;
                    case 'metafieldvalue':
                        $pattern = "/T\d{2}:\d{2}:\d{2}((\+|-)[0-1][0-9]{3})/";
                        $newDescription = preg_replace($pattern, "", $entry['description']);
                        $parts = preg_split('/:/', $newDescription);
                        if ($entry['type'] == 'METAFIELD_VALUE_REMOVE') {
                            $fileId = $parts[1];
                            $metaFieldId = $parts[0];
                            $metaField = $historyEventService->getEntity(
                                MetaField::ENTITY_NAME,
                                $metaFieldId
                            );
                            if ($metaField != null) {
                                $entry['description'] = $metaField->getName() . ':' . $parts[2];
                            } elseif ($parts[3] != "") {
                                $entry['description'] = $parts[3] . ':' . $parts[2];
                            } else {
                                continue 2;
                            }
                        } else {
                            $fileId = $parts[2];
                            if ($entry['type'] == 'METAFIELD_VALUE_ASSIGN') {
                                if (is_numeric($parts[0])) {
                                    $metaFieldId = $parts[0];
                                    $metaField = $historyEventService->getEntity(
                                        MetaField::ENTITY_NAME,
                                        $metaFieldId
                                    );
                                    if ($metaField != null) {
                                        $entry['description'] = $metaField->getName() . ':' . $parts[1];
                                    } elseif ($parts[3] != "") {
                                        $entry['description'] = $parts[3] . ':' . $parts[1];
                                    } else {
                                            continue 2;
                                    }
                                } elseif ($parts[3] != "") {
                                    $entry['description'] = $parts[3] . ':' . $parts[1];
                                } else {
                                    continue 2;
                                }
                            } else {
                                if (is_numeric($parts[1])) {
                                    $metaFieldId = $parts[1];
                                    $metaField = $historyEventService->getEntity(
                                        MetaField::ENTITY_NAME,
                                        $metaFieldId
                                    );
                                    if ($metaField != null) {
                                        $entry['description'] = $parts[0] . ':' . $metaField->getName();
                                    } elseif ($parts[3] != "") {
                                        $entry['description'] = $parts[0] . ':' . $parts[3];
                                    } else {
                                        continue 2;
                                    }
                                } elseif ($parts[3] != "") {
                                    $entry['description'] = $parts[0] . ':' . $parts[3];
                                } else {
                                    continue 2;
                                }
                            }
                        }
                        if ($fileId != '') {
                            if (isset($this->sources['file'][$fileId])) {
                                $source = $this->sources['file'][$fileId];
                                array_push($selectedEntries, $this->populateSelectedEntry($entry, $source));
                            } else {
                                $source = $historyEventService->getEntity(
                                    File::ENTITY_NAME,
                                    $fileId
                                );
                                if ($source) {
                                    if ($this->getPermissionService()->isInResourceUserIds(
                                        $userId,
                                        $source->getWorkspace()
                                    )
                                    ) {
                                        if ($this->getFileService()->userIsAllowed($userId, $source) ||
                                            $this->getFileService()->getFileFolderHighestPermission(
                                                $source,
                                                $userId
                                            ) & $allowed
                                        ) {
                                            $this->sources['file'][$fileId] = $source;
                                            array_push($selectedEntries, $this->populateSelectedEntry($entry, $source));
                                        }
                                    }
                                }
                            }
                        } else {
                            if (isset($this->sources['metafieldvalue'][$entry['source_id']])) {
                                $source = $this->sources['metafieldvalue'][$entry['source_id']];
                            } else {
                                $source = $historyEventService->getEntity(
                                    MetaFieldValue::ENTITY_NAME,
                                    $entry['source_id']
                                );
                                if ($source) {
                                    $file = $source->getResource();
                                    if ($this->getPermissionService()->isInResourceUserIds(
                                        $userId,
                                        $file->getWorkspace()
                                    ) &&
                                        ($this->getFileService()->userIsAllowed($userId, $file) ||
                                            $this->getFileService()->getFileFolderHighestPermission(
                                                $file,
                                                $userId
                                            ) & $allowed)
                                        ) {
                                            $this->sources['metafieldvalue'][$entry['source_id']] = $source;
                                    } else {
                                        $source = null;
                                    }
                                }
                            }
                            if (!(($entry['type'] == 'METAFIELD_VALUE_ASSIGN' ||
                                    $entry['type'] == 'METAFIELD_VALUE_CHANGE')
                                && count($parts) < 3 && !$source)
                            ) {
                                // Remove old format entries if source no longer exists since info in description
                                // is not enough to create an informative history entry
                                array_push($selectedEntries, $this->populateSelectedEntry($entry, $source));
                            }
                        }
                        break;
                    case 'workspace':
                        if (isset($this->sources['workspace'][$entry['source_id']])) {
                            $source = $this->sources['workspace'][$entry['source_id']];
                        } else {
                            $source = $historyEventService->getEntity(
                                Workspace::ENTITY_NAME,
                                $entry['source_id']
                            );
                            if ($source) {
                                $this->sources['workspace'][$entry['source_id']] = $source;
                            }
                        }
                        $role       = new UserRole($userId);
                        if (is_numeric($portal)) {
                            $portalId = $portal;
                            /** @var Portal $portal */
                            $portal = $historyEventService->getEntity(Portal::ENTITY_NAME, $portalId);
                        }

                        if ($source) {
                            if ($acl->isAllowed($role, $portal, PortalPrivilege::PRIVILEGE_MANAGE_WORKSPACES) ||
                                $this->getPermissionService()->isInResourceUserIds($userId, $source)) {
                                array_push($selectedEntries, $this->populateSelectedEntry($entry, $source));
                            }
                        }
                        break;
                    case 'smartfolder':
                        if (isset($this->sources['smartfolder'][$entry['source_id']])) {
                            $source = $this->sources['smartfolder'][$entry['source_id']];
                        } else {
                            $source = $historyEventService->getEntity(
                                SmartFolder::ENTITY_NAME,
                                $entry['source_id']
                            );
                            if ($source) {
                                $this->sources['smartfolder'][$entry['source_id']] = $source;
                            }
                        }
                        if ($source) {
                            if ($this->getPermissionService()->isInResourceUserIds($userId, $source->getWorkspace())) {
                                array_push($selectedEntries, $this->populateSelectedEntry($entry, $source));
                            }
                        }
                        break;
                    case 'invitation':
                        if (isset($this->sources['invitiation'][$entry['source_id']])) {
                            $source = $this->sources['invitiation'][$entry['source_id']];
                        } else {
                            $source = $historyEventService->getEntity(
                                Invitation::ENTITY_NAME,
                                $entry['source_id']
                            );
                            if ($source) {
                                $this->sources['invitation'][$entry['source_id']] = $source;
                            }
                        }
                        $portal = $historyEventService->getEntity(
                            Portal::ENTITY_NAME,
                            $entry['portal_id']
                        );
                        $role = new UserRole($auth->getIdentity());
                        $privilege = PortalPrivilege::PRIVILEGE_GRANT_ADMIN_PERMISSION;
                        if ($acl->isAllowed($role, $portal, $privilege)) {
                            if ($entry['type'] == 'INVITATION_USER_PORTAL_ACCEPTED') {
                                $invitedUser = $historyEventService->getEntity(
                                    User::ENTITY_NAME,
                                    $entry['description']
                                );
                                $entry['description'] = $invitedUser->getEmail();
                            }
                            array_push($selectedEntries, $this->populateSelectedEntry($entry, $source));
                        }
                        break;
                    case 'user':
                        if (isset($this->sources['user'][$entry['source_id']])) {
                            $source = $this->sources['user'][$entry['source_id']];
                        } else {
                            $source = $historyEventService->getEntity(User::ENTITY_NAME, $entry['source_id']);
                            if ($source) {
                                $this->sources['user'][$entry['source_id']] = $source;
                            }
                        }
                        $portal = $historyEventService->getEntity(
                            Portal::ENTITY_NAME,
                            $entry['portal_id']
                        );
                        if ($source) {
                            $role = new UserRole($auth->getIdentity());
                            $privilege = PortalPrivilege::PRIVILEGE_GRANT_ADMIN_PERMISSION;
                            if ($acl->isAllowed($role, $portal, $privilege)) {
                                array_push($selectedEntries, $this->populateSelectedEntry($entry, $source));
                            }
                        }
                        break;
                    case 'group':
                        if (isset($this->sources['group'][$entry['source_id']])) {
                            $source = $this->sources['group'][$entry['source_id']];
                        } else {
                            $source = $historyEventService->getEntity(Group::ENTITY_NAME, $entry['source_id']);
                            if ($source) {
                                $this->sources['group'][$entry['source_id']] = $source;
                            }
                        }
                        $workspace = $historyEventService->getEntity(Workspace::ENTITY_NAME, $entry['workspace_id']);
                        if ($workspace) {
                            if ($this->getPermissionService()->isInResourceUserIds($userId, $workspace)) {
                                array_push($selectedEntries, $this->populateSelectedEntry($entry, $source));
                            }
                        } else {
                            array_push($selectedEntries, $this->populateSelectedEntry($entry, $source));
                        }
                        break;
                }
            }
        }
        $entries = $selectedEntries;


        $result = array(
            'has_more' => ($numEntries == $limit),
            'last_offset' => $lastOffset,
            'entries' => $entries
        );

        return $response->withJson($result);
    }

    /**
     * @return HistoryEventService
     */
    public function getHistoryEventService()
    {
        return $historyEventService = $this->getContainer()->get('HistoryEventService');
    }

    /**
     * @return FileService
     */
    private function getFileService()
    {
        return $this->getContainer()->get('FileService');
    }

    /**
     * @return PermissionService
     */
    private function getPermissionService()
    {
        return $this->getContainer()->get('PermissionService');
    }
}
