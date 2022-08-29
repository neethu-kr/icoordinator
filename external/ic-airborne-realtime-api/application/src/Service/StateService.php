<?php

namespace iCoordinator\Service;

use GuzzleHttp\Client;
use iCoordinator\Entity\Portal;
use iCoordinator\Entity\User;
use iCoordinator\Entity\Workspace;

class StateService extends AbstractService
{
    const LEVEL_ENTRIES_LIMIT_DEFAULT = 20000;

    public function getClientVersion()
    {
        if (isset($_SERVER['HTTP_CLIENT_VERSION'])) {
            return substr($_SERVER['HTTP_CLIENT_VERSION'], 0, strlen("1.4"));
        } else {
            return "";
        }
    }

    public function cmpClean($a, $b)
    {
        return strcmp($a['name'], $b['name']);
    }

    public function getVisibleChildren(&$node, $selSyncData, $fileIds, $level, $read = false)
    {
        if ($node['id']) {
            $visibleEntries[] = array(
                'gzentry' => substr($node['gzentry'], 0, -1).',"level":'.$level.'}'
            );
        } else {
            $visibleEntries = null;
        }
        if (count($node['children'])) {
            foreach ($node['children'] as $key => $child) {
                $plainChild = (array) json_decode($child['gzentry']);
                $node['children'][$key]['name'] = $plainChild['name'];
            }
            usort($node['children'], array($this, "cmpClean"));
            while ($child = array_shift($node['children'])) {
                if (isset($fileIds[$child['id']])) {
                    if (!$fileIds[$child['id']]) {
                    } else {
                        if (!isset($selSyncData[$child['id']])) {
                            if ($visibleEntries) {
                                $visibleEntries = array_merge(
                                    $visibleEntries,
                                    $this->getVisibleChildren(
                                        $child,
                                        $selSyncData,
                                        $fileIds,
                                        $level + 1,
                                        true
                                    )
                                );
                            } else {
                                $visibleEntries = $this->getVisibleChildren(
                                    $child,
                                    $selSyncData,
                                    $fileIds,
                                    $level + 1,
                                    true
                                );
                            }
                        }
                    }
                } elseif ($read) {
                    if (!isset($selSyncData[$child['id']])) {
                        if ($visibleEntries) {
                            $visibleEntries = array_merge(
                                $visibleEntries,
                                $this->getVisibleChildren(
                                    $child,
                                    $selSyncData,
                                    $fileIds,
                                    $level + 1,
                                    $read
                                )
                            );
                        } else {
                            $visibleEntries = $this->getVisibleChildren(
                                $child,
                                $selSyncData,
                                $fileIds,
                                $level + 1,
                                $read
                            );
                        }
                    }
                }
            }
        }
        return $visibleEntries;
    }
    public function getVisibleChildren2(&$node, $level)
    {
        if (isset($node['id']) && $node['id'] > 0) {
            $visibleEntries[] = substr($node['gzentry'], 0, -1).',"level":'.$level.'}';
        } elseif (!isset($visibleEntries)) {
            $visibleEntries = array();
        }
        if (count($node['children'])) {
            foreach ($node['children'] as $key => $child) {
                $plainChild = (array) json_decode($child['gzentry']);
                $node['children'][$key]['name'] = $plainChild['name'];
            }
            usort($node['children'], array($this, "cmpClean"));
            while ($child = array_shift($node['children'])) {
                $visibleEntries = array_merge(
                    $visibleEntries,
                    $this->getVisibleChildren2(
                        $child,
                        $level + 1
                    )
                );
            }
        }
        return $visibleEntries;
    }
    public function getVisibleChildrenOneLevel($node, $selSyncData, $fileIds)
    {
        $visibleEntries = array();
        $plainChildren = array();
        if (count($node['children'])) {
            foreach ($node['children'] as $key => $child) {
                $plainChildren[] = (array) json_decode($child['gzentry']);
            }
            usort($plainChildren, array($this, "cmpClean"));
            while ($child = array_shift($plainChildren)) {
                if (isset($fileIds[$child['id']])) {
                    if (!$fileIds[$child['id']]) {
                    } else {
                        if (!isset($selSyncData[$child['id']])) {
                            $visibleEntries[] = array(
                                'id' => $child['id'],
                                'parent' => $child['parent'],
                                'type' => $child['type'],
                                'name' => $child['name'],
                                'hash' => $child['hash'],
                                'version' => $child['version'],
                                'size' => $child['size'],
                                'modified_at' => $child['modified_at'],
                                'content_modified_at' => $child['content_modified_at']
                            );
                        }
                    }
                } elseif (!isset($selSyncData[$child['id']])) {
                    $visibleEntries[] = array(
                        'id' => $child['id'],
                        'parent' => $child['parent'],
                        'type' => $child['type'],
                        'name' => $child['name'],
                        'hash' => $child['hash'],
                        'version' => $child['version'],
                        'size' => $child['size'],
                        'modified_at' => $child['modified_at'],
                        'content_modified_at' => $child['content_modified_at']
                    );
                }
            }
        }
        return $visibleEntries;
    }

    public function getAccessibleChildren(&$node, $fileIds, &$topNode, $read = false)
    {

        if ($node['id'] && $read) {
            $visibleEntries[] = array(
                'gzentry' => $node['gzentry']
            );
        } else {
            $visibleEntries = null;
        }
        if (count($node['children'])) {
            foreach ($node['children'] as $key => $child) {
                $plainChild = (array) json_decode($child['gzentry']);
                $node['children'][$key]['name'] = $plainChild['name'];
            }
            usort($node['children'], array($this, "cmpClean"));
            while ($child = array_shift($node['children'])) {
                if (isset($fileIds[$child['id']])) {
                    if (!$fileIds[$child['id']]) {
                    } else {
                        if ($visibleEntries) {
                            $visibleEntries = array_merge(
                                $visibleEntries,
                                $this->getAccessibleChildren($child, $fileIds, $topNode, true)
                            );
                        } else {
                            $visibleEntries = $this->getAccessibleChildren(
                                $child,
                                $fileIds,
                                $topNode,
                                true
                            );
                        }
                    }
                } elseif ($read) {
                    if ($visibleEntries) {
                        $visibleEntries = array_merge(
                            $visibleEntries,
                            $this->getAccessibleChildren($child, $fileIds, $topNode, $read)
                        );
                    } else {
                        $visibleEntries = $this->getAccessibleChildren($child, $fileIds, $topNode, $read);
                    }
                }
            }
        }
        unset($topNode[$node['id']]);
        unset($node);
        return $visibleEntries;
    }

    public function cmp($a, $b)
    {
        return strcmp($a->getName(), $b->getName());
    }

    public function getTreeMap(&$entries, $getTop = false)
    {
        //Helper to create nodes
        $tree_node = function ($id, $parent, $entry) {
            return array(
                'id' => $id,
                'parent' => $parent,
                'gzentry' => $entry,
                'children' => array()
            );
        };

        $tree = $tree_node(0, -1, null); //root node
        $map = array(0 => &$tree);
        if (!empty($entries)) {
            while ($jsonentry = array_shift($entries)) {
                $entry = json_decode($jsonentry['json']);
                if ($entry != null) {
                    $id = (int)$entry->id;
                    $parentId = (int)$entry->parent;
                    if (isset($map[$id])) {
                        $children = $map[$id]['children'];
                        unset($map[$id]);
                        $map[$id] =& $map[$parentId]['children'][];
                        $map[$id] = $tree_node(
                            $id,
                            $parentId,
                            $jsonentry['json']
                        );
                        $map[$id]['children'] = $children;
                    } else {
                        $map[$id] =& $map[$parentId]['children'][];
                        $map[$id] = $tree_node(
                            $id,
                            $parentId,
                            $jsonentry['json']
                        );
                    }
                }
            }
        }
        if ($getTop) {
            $drop = array_shift($map);
            unset($drop);
        } else {
            $map = array_shift($map);
        }
        return $map;
    }
    public function getTreeMap2(&$entries, $getTop = false)
    {
        //Helper to create nodes
        $tree_node = function ($id, $parent, $entry) {
            return array(
                'id' => $id,
                'parent' => $parent,
                'gzentry' => $entry,
                'children' => array()
            );
        };

        $tree = $tree_node(0, -1, null); //root node
        $map = array(0 => &$tree);
        if (!empty($entries)) {
            while ($jsonentry = array_shift($entries)) {
                $entry = json_decode($jsonentry);
                if ($entry != null) {
                    $id = (int)$entry->id;
                    $parentId = (int)$entry->parent;
                    if (isset($map[$id])) {
                        $children = $map[$id]['children'];
                        unset($map[$id]);
                        $map[$id] =& $map[$parentId]['children'][];
                        $map[$id] = $tree_node(
                            $id,
                            $parentId,
                            $jsonentry
                        );
                        $map[$id]['children'] = $children;
                    } else {
                        $map[$id] =& $map[$parentId]['children'][];
                        $map[$id] = $tree_node(
                            $id,
                            $parentId,
                            $jsonentry
                        );
                    }
                }
            }
        }
        if ($getTop) {
            $drop = array_shift($map);
            unset($drop);
        } else {
            $map = array_shift($map);
        }
        return $map;
    }
    public function getPortalState($portal, $user, $isDesktopClient, $slimState = false)
    {
        if (is_numeric($portal)) {
            $portalId = $portal;
            /** @var User $user */
            $portal = $this->getEntityManager()->getReference(Portal::ENTITY_NAME, $portalId);
        }
        if (is_numeric($user)) {
            $userId = $user;
            /** @var User $user */
            $user = $this->getEntityManager()->getReference(User::ENTITY_NAME, $userId);
        }
        $stateContent = "";
        $portalState = "";
        $workspaceStates = array();
        $workspaceService = $this->getContainer()->get('WorkspaceService');
        $folderService = $this->getFolderService();
        $permissionService = $this->getPermissionService();
        $paginator = $workspaceService->getWorkspacesAvailableForUser($user, $portal);
        $workspaces = $paginator->getIterator()->getArrayCopy();
        unset($paginator);
        usort($workspaces, array($this, "cmp"));
        foreach ($workspaces as $workspace) {
            $workspaceStateContent = "";
            $workspaceState = "";
            $selSyncData = null;
            $fileIds = null;
            if (!$workspace->getIsDeleted()) {
                if ($slimState) {
                    $portalState = hash(
                        'sha256',
                        $portalState . $workspace->getName()
                    );
                } else {
                    $stateContent .= hash('sha256', $workspace->getName());
                }
                $entries = $folderService->getAllWorkspaceChildren($workspace);
                if (!empty($entries)) {
                    $treeSortedMap = $this->getTreeMap($entries);
                    unset($entries);
                    if (!$permissionService->isWorkspaceAdmin($user, $workspace)) {
                        $fileIds = $folderService->getWorkspaceFileIdsAvailableForUser(
                            $user,
                            $workspace,
                            false
                        );
                        $read = false;
                    } else {
                        $read = true;
                    }
                    if ($isDesktopClient) {
                        $selectiveSyncService = $this->getSelectiveSyncService();
                        $selSyncData = $selectiveSyncService->getSelectiveSyncFileIds($user, $workspace);
                    }
                    $visibleEntries = $this->getVisibleChildren(
                        $treeSortedMap,
                        $selSyncData,
                        $fileIds,
                        -1,
                        $read
                    );
                    unset($treeSortedMap);
                    unset($selSyncData);
                    unset($fileIds);
                    $clientVersion = $this->getClientVersion();

                    if ($visibleEntries) {
                        while ($visibleEntry = array_shift($visibleEntries)) {
                            $node = (array) json_decode($visibleEntry['gzentry']);
                            if ($slimState) {
                                $portalState = hash(
                                    'sha256',
                                    $portalState .
                                    $node['name'] . $node['hash'] .
                                    ($clientVersion == '1.4' ?
                                        '' : $node['level']
                                    )
                                );
                                $workspaceState = hash(
                                    'sha256',
                                    $workspaceState .
                                    $node['name'] . $node['hash'] .
                                    ($clientVersion == '1.4' ?
                                        '' : $node['level']
                                    )
                                );
                            } else {
                                $workspaceStateContent .= hash(
                                    'sha256',
                                    $node['name'] . $node['hash'] .
                                    ($clientVersion == '1.4' ?
                                        '' : $node['level']
                                    )
                                );
                            }
                        }
                        unset($visibleEntries);
                        $stateContent .= $workspaceStateContent;
                    }
                }
                if ($workspaceState == "") {
                    $workspaceState = hash('sha256', '');
                }
                $workspaceStates[] = array(
                    'id' => $workspace->getId(),
                    'name' => $workspace->getName(),
                    'portal' => array('id' => $portal->getId()),
                    'state' => $slimState ? $workspaceState : hash('sha256', $workspaceStateContent)
                );
                unset($workspaceStateContent);
            }
        }
        if ($portalState == "") {
            $portalState = hash('sha256', '');
        }
        return array(
            'state' => $slimState ? $portalState : hash('sha256', $stateContent),
            'workspaceStates' => $workspaceStates
        );
    }
    public function getWorkspaceState($workspace, $userId, $isDesktopClient)
    {

        $selSyncData = null;
        $fileIds = null;
        $stateContent = "";
        $debugEntries = array();
        $entries = $this->getFolderService()->getAllWorkspaceChildren($workspace);

        $treeSortedMap = $this->getTreeMap($entries);
        unset($entries);
        if (!$this->getPermissionService()->isWorkspaceAdmin($userId, $workspace)) {
            $fileIds = $this->getFolderService()->getWorkspaceFileIdsAvailableForUser($userId, $workspace, false);
            $read = false;
        } else {
            $read = true;
        }
        if ($isDesktopClient) {
            $selectiveSyncService = $this->getSelectiveSyncService();
            $selSyncData = $selectiveSyncService->getSelectiveSyncFileIds($userId, $workspace);
        }
        $visibleEntries = $this->getVisibleChildren(
            $treeSortedMap,
            $selSyncData,
            $fileIds,
            -1,
            $read
        );
        unset($treeSortedMap);
        unset($selSyncData);
        $clientVersion = $this->getClientVersion();
        if ($visibleEntries) {
            while ($visibleEntry = array_shift($visibleEntries)) {
                $node = (array) json_decode($visibleEntry['gzentry']);
                $stateContent .= hash(
                    'sha256',
                    $node['name'] . $node['hash'].
                    ($clientVersion == '1.4' ? '' : $node['level'])
                );
            }
            unset($visibleEntries);
        }
        return hash('sha256', $stateContent);
    }

    public function createState(
        $auth_token,
        $client_state,
        $slim_state
    ) {
        $client = new Client(['headers' => ['Authorization' => $auth_token]]);
        $host = $_SERVER['VIRTUAL_HOST'];
        if (strpos($_SERVER['WEB_BASE_URL'], "https") === true) {
            $host = $_SERVER['VIRTUAL_HOST'];
            $host = "https://".$host;
        } else {
            $host = "http://".$host;
        }
        $response = $client->request('GET', $host.'/saveState?client_state='.$client_state.'&slim_state='.$slim_state);
    }

    public function createWorkspaceState(
        $auth_token,
        $workspace,
        $client_state,
        $slim_state
    ) {
        $client = new Client(['headers' => ['Authorization' => $auth_token]]);
        $host = $_SERVER['VIRTUAL_HOST'];
        if (strpos($_SERVER['WEB_BASE_URL'], "https") === true) {
            $host = $_SERVER['VIRTUAL_HOST'];
            $host = "https://".$host;
        } else {
            $host = "http://".$host;
        }
        $response = $client->request(
            'GET',
            $host.'/state/'.$workspace.'/saveWorkspace?client_state='.$client_state.'&slim_state='.$slim_state
        );
    }

    public function createFolderTreeState(
        $auth_token,
        $folder,
        $client_state,
        $slim_state
    ) {
        $client = new Client(['headers' => ['Authorization' => $auth_token]]);
        $host = $_SERVER['VIRTUAL_HOST'];
        if (strpos($_SERVER['WEB_BASE_URL'], "https") === true) {
            $host = $_SERVER['VIRTUAL_HOST'];
            $host = "https://".$host;
        } else {
            $host = "http://".$host;
        }
        $response = $client->request(
            'GET',
            $host.'/state/'.$folder.'/saveFolders?client_state='.$client_state.'&slim_state='.$slim_state
        );
    }

    /**
     * @param $workspace
     * @param $user
     * @param $limit
     * @param $offset
     * @param null $types
     * @return Paginator
     */
    public function getEntriesForLevel($parentArrays, $workspace)
    {
        if (!$workspace instanceof Workspace) {
            $workspaceId = $workspace;
            $workspace = $this->getWorkspace($workspaceId);
        }
        $lastRowId = 0;
        $parentSql = "";
        $result = array();
        $em = $this->getEntityManager();
        $pdo = $em->getConnection();
        if ($parentArrays != null) {
            $parentSql .= ' AND (';
            while ($parents = array_shift($parentArrays)) {
                $parentSql .= ' parent in ('.implode(",", $parents).')';
                if (count($parentArrays)) {
                    $parentSql .= ' OR ';
                }
            }
            $parentSql .= ')';
        }
        do {
            $sql = "select CONCAT('{\"id\":',id,',\"parent\":',IF(parent IS NULL,0,parent)".
                ",',\"type\":\"',type,'\",\"name\":\"',name,'\"".
                ",\"hash\":',IF(hash IS NULL,'null',CONCAT('\"',hash,'\"'))".
                ",',\"version\":',etag,',\"size\":',size,'".
                ",\"modified_at\":',IF(modified_at IS NULL, 'null',".
                "CONCAT('\"',DATE_FORMAT(modified_at, '%Y-%m-%dT%TZ'),'\"'))".
                ",',\"content_modified_at\":',IF(content_modified_at IS NULL,'null',".
                "CONCAT('\"',DATE_FORMAT(content_modified_at, '%Y-%m-%dT%TZ')".
                ",'\"')),'}') as json".
                " FROM files where is_deleted != 1 AND is_trashed != 1 AND is_uploading!= 1 AND type != 'smart_folder'";

            if ($workspace != null) {
                $sql .= ' AND workspace_id = ' . $workspace->getId();
            }
            $sql .= ' AND id > ' . $lastRowId;
            if ($parentSql == "") {
                $sql .= ' AND parent is null';
            } else {
                $sql .= $parentSql;
            }
            $sql .= ' ORDER BY id ASC LIMIT ' . self::LEVEL_ENTRIES_LIMIT_DEFAULT;

            $stmt = $pdo->prepare($sql);
            $stmt->execute();

            $data = $stmt->fetchAll();
            $cnt = count($data);

            $result = array_merge($result, $data);
            if ($cnt == self::LEVEL_ENTRIES_LIMIT_DEFAULT) {
                $node = json_decode($data[self::LEVEL_ENTRIES_LIMIT_DEFAULT-1]['json']);
                $lastRowId = $node->id;
            }
            unset($data);
        } while ($cnt == self::LEVEL_ENTRIES_LIMIT_DEFAULT);
        return $result;
    }

    /**
     * @return SelectiveSyncService
     */
    private function getSelectiveSyncService()
    {
        return $this->getContainer()->get('SelectiveSyncService');
    }

    /**
     * @return PermissionService
     */
    protected function getPermissionService()
    {

        return $this->getContainer()->get('PermissionService');
    }

    /**
     * @return FolderService
     */
    protected function getFolderService()
    {

        return $this->getContainer()->get('FolderService');
    }
}
