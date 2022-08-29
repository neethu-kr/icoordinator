<?php

namespace iCoordinator\Entity;

use Carbon\Carbon;
use Datetime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use iCoordinator\Entity\Acl\AclResource;
use iCoordinator\Entity\Acl\AclResource\AclFileResource;
use iCoordinator\Permissions\Privilege\FilePrivilege;
use iCoordinator\Permissions\Resource\HavingDynamicPermissionsResourceInterface;
use iCoordinator\Permissions\Resource\HavingOwnerResourceInterface;

/**
 * @Entity
 * @Table(name="files", options={"collate"="utf8_general_ci"})
 * @InheritanceType("SINGLE_TABLE")
 * @DiscriminatorColumn(name="type", type="string", length=100)
 * @DiscriminatorMap({
 *  "file" = "File",
 *  "folder" = "Folder",
 *  "smart_folder" = "SmartFolder"
 * })
 * @HasLifecycleCallbacks
 */
class File extends AbstractEntity implements HavingDynamicPermissionsResourceInterface, HavingOwnerResourceInterface
{
    const ENTITY_NAME = 'entity:File';

    const RESOURCE_ID = 'file';

    /**
     * Types of file objects
     * @var array
     */
    public static $types = array('file', 'folder', 'smart_folder');

    /**
     * @var Portal
     * @ManyToOne(targetEntity="Portal")
     * @JoinColumn(name="portal_id", referencedColumnName="id")
     */
    protected $portal;

    /**
     * @var Workspace
     * @ManyToOne(targetEntity="Workspace")
     * @JoinColumn(name="workspace_id", referencedColumnName="id")
     */
    protected $workspace;

    /**
     * @var string
     * @Column(type="string", length=255, nullable=false)
     */
    protected $name;

    /**
     * @var bigint
     * @Column(type="bigint", nullable=false)
     */
    protected $size;

    /**
     * @var string
     * @Column(type="string", length=100, nullable=true)
     */
    protected $mime_type;

    /**
     * @var User
     * @ManyToOne(targetEntity="User")
     * @JoinColumn(name="created_by", referencedColumnName="id")
     */
    protected $created_by;


    /**
     * @var User
     * @ManyToOne(targetEntity="User")
     * @JoinColumn(name="owned_by", referencedColumnName="id")
     */
    protected $owned_by;


    /**
     * @var User
     * @ManyToOne(targetEntity="User")
     * @JoinColumn(name="modified_by", referencedColumnName="id")
     */
    protected $modified_by;

    /**
     * @var Carbon
     * @Column(type="datetime", nullable=true)
     */
    protected $content_created_at;

    /**
     * @var Carbon
     * @Column(type="datetime", nullable=true)
     */
    protected $content_modified_at;

    /**
     * @var Folder
     * @ManyToOne(targetEntity="Folder", inversedBy="children")
     * @JoinColumn(name="parent", referencedColumnName="id")
     */
    protected $parent = null;


    /**
     * @var Lock
     * @OneToOne(targetEntity="Lock", inversedBy="file", cascade={"all"})
     * @JoinColumn(name="lock_id", referencedColumnName="id")
     */
    protected $lock = null;

    /**
     * @var bool
     * @Column(type="boolean")
     */
    protected $is_trashed = false;

    /**
     * @var bool
     * @Column(type="boolean")
     */
    protected $is_deleted = false;

    /**
     * @var Carbon
     * @Column(type="datetime")
     */
    protected $created_at;

    /**
     * @var Carbon
     * @Column(type="datetime")
     */
    protected $modified_at;


    /**
     * @var int
     * @Column(type="integer")
     */
    protected $etag = 1;


    /**
     * @var FileVersion
     * @OneToOne(targetEntity="FileVersion", mappedBy="file", cascade={"all"})
     * @JoinColumn(name="version_id", referencedColumnName="id")
     */
    protected $version = null;


    /**
     * @var SharedLink
     * @OneToOne(targetEntity="SharedLink", mappedBy="file", cascade={"all"})
     **/
    protected $shared_link;


    /**
     * @var ArrayCollection
     * @OneToMany(
     *  targetEntity="iCoordinator\Entity\MetaFieldValue",
     *  mappedBy="resource",
     *  fetch="EXTRA_LAZY",
     *  cascade={"all"}
     * )
     */
    protected $meta_fields_values;

    /**
     * @var string
     * @Column(type="string", length=256)
     */
    protected $hash = null;

    /**
     * @var bool
     * @Column(type="boolean")
     */
    protected $is_uploading = false;

    /**
     * @var AclFileResource
     * @OneToOne(targetEntity="\iCoordinator\Entity\Acl\AclResource\AclFileResource", mappedBy="file")
     */
    protected $acl_resource = null;


    protected $selective_sync = null;

    protected $file_email_options = null;

    protected $version_comment = null;

    /**
     * @var Carbon
     */
    protected $version_created_at;


    public function __construct()
    {
        $this->meta_fields_values = new ArrayCollection();
    }

    public static function getType()
    {
        return 'file';
    }

    /**
     * Implementing HavingDynamicPermissionsResourceInterface
     *
     * @param $permissionType
     * @return null|string
     */
    public static function getPrivilegeForGrantingPermission($permissionType)
    {
        return FilePrivilege::getPrivilegeForGrantingPermission($permissionType);
    }

    /**
     * Implementing HavingDynamicPermissionsResourceInterface
     *
     * @return string
     */
    public static function getPrivilegeForReadingPermissions()
    {
        return FilePrivilege::getPrivilegeForReadingPermissions();
    }

    public static function getEntityName()
    {
        return self::ENTITY_NAME;
    }

    /**
     * @return User
     */
    public function getOwnedBy()
    {
        if (!$this->owned_by) {
            if ($this->getParent()) {
                $this->setOwnedBy($this->getParent()->getOwnedBy());
            } else {
                $this->setOwnedBy($this->getCreatedBy());
            }
        }

        return $this->owned_by;
    }

    /**
     * @param $owned_by
     * @return $this
     */
    public function setOwnedBy($owned_by)
    {
        $this->owned_by = $owned_by;
        return $this;
    }

    /**
     * @return Folder
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * @param mixed $parent
     * @return File
     */
    public function setParent($parent)
    {
        $this->parent = $parent;
        return $this;
    }

    /**
     * @return User
     */
    public function getCreatedBy()
    {
        return $this->created_by;
    }

    /**
     * @param mixed $created_by
     * @return File
     */
    public function setCreatedBy($created_by)
    {
        $this->created_by = $created_by;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getIsDeleted()
    {
        return $this->is_deleted;
    }

    /**
     * @param boolean $is_deleted
     * @return File
     */
    public function setIsDeleted($is_deleted)
    {
        $this->is_deleted = $is_deleted;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getModifiedBy()
    {
        return $this->modified_by;
    }

    /**
     * @param mixed $modified_by
     * @return File
     */
    public function setModifiedBy($modified_by)
    {
        $this->modified_by = $modified_by;
        return $this;
    }

    /**
     * @return FileVersion
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @param $version
     * @return $this
     */
    public function setVersion($version)
    {
        $this->version = $version;
        return $this;
    }

    /**
     * @PrePersist
     * @PreUpdate
     */
    public function updatedTimestamps()
    {
        $this->setModifiedAt(new Carbon());

        if ($this->getCreatedAt() == null) {
            $this->setCreatedAt(new Carbon());
        }
        return $this;
    }

    /**
     * @return \Carbon\Carbon
     */
    public function getCreatedAt()
    {
        return $this->created_at;
    }

    /**
     * @param \Carbon\Carbon $created_at
     * @return File
     */
    public function setCreatedAt($created_at)
    {
        $this->created_at = $created_at;
        return $this;
    }

    /**
     * Implementing Zend ACL ResourceInterface
     * @return string
     */
    public function getResourceId()
    {
        return self::RESOURCE_ID;
    }

    /**
     * @return AclFileResource
     */
    public function getAclResource()
    {
        return $this->acl_resource;
    }

    /**
     * @param AclResource $aclResource
     * @return $this
     */
    public function setAclResource(AclResource $aclResource)
    {
        $this->acl_resource = $aclResource;
        return $this;
    }

    public function jsonSerialize($mini = false)
    {
        $result = array(
            'entity_type' => 'file',
            'id' => $this->getId(),
            'name' => $this->getName(),
            'etag' => $this->getEtag()
        );

        if (!$mini) {
            $metaFieldsValues = array();
            foreach ($this->getMetaFieldsValues() as $metaFieldValue) {
                array_push($metaFieldsValues, $metaFieldValue->jsonSerialize(true));
            }
            $result = array_merge($result, array(
                'workspace' => $this->getWorkspace()->jsonSerialize(true),
                'owned_by' => ($this->getOwnedBy()) ? $this->getOwnedBy()->jsonSerialize(true) : null,
                'parent' => ($this->getParent()) ? $this->getParent()->jsonSerialize(true) : null,
                'lock' => ($this->getLock()) ? $this->getLock()->jsonSerialize() : null,
                'created_at' => ($this->getCreatedAt()) ?
                    $this->getCreatedAt()->format(DateTime::ISO8601) : null,
                'modified_at' => ($this->getModifiedAt()) ?
                    $this->getModifiedAt()->format(DateTime::ISO8601) : null,
                'content_created_at' => ($this->getContentCreatedAt()) ?
                    $this->getContentCreatedAt()->format(DateTime::ISO8601) : null,
                'content_modified_at' => ($this->getContentModifiedAt()) ?
                    $this->getContentModifiedAt()->format(DateTime::ISO8601) : null,
                'size' => $this->getSize(),
                'mime_type' => $this->getMimeType(),
                'meta_fields_values' => $metaFieldsValues,
                'hash' => $this->getHash(),
                'is_uploading' => $this->getIsUploading(),
                'shared_link' => ($this->getSharedLink()) ? $this->getSharedLink()->jsonSerialize() : null,
                'is_trashed' => $this->getIsTrashed(),
                'selective_sync' => $this->getSelectiveSync(),
                'file_email_options' => $this->getFileEmailOptions(),
                'modified_by' => ($this->getModifiedBy()) ? $this->getModifiedBy()->jsonSerialize(true) : null,
                'version_comment' => ($this->getVersionComment()) ? $this->getVersionComment() : null,
                'version_created_at' => ($this->getVersionCreatedAt()) ?
                    date(Datetime::ISO8601, strtotime($this->getVersionCreatedAt())) : null
            ));
        }

        return $result;
    }

    /**
     * @return boolean
     */
    public function getIsUploading()
    {
        return $this->is_uploading;
    }

    /**
     * @param boolean $is_uploading
     * @return File
     */
    public function setIsUploading($is_uploading)
    {
        $this->is_uploading = $is_uploading;
        return $this;
    }

    /**
     * @return string
     */
    public function getHash()
    {
        return $this->hash;
    }

    /**
     * @param string $hash
     * @return File
     */
    public function setHash($hash)
    {
        $this->hash = $hash;
        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return File
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return int
     */
    public function getEtag()
    {
        return $this->etag;
    }

    /**
     * @param int $etag
     * @return File
     */
    public function setEtag($etag)
    {
        $this->etag = $etag;
        return $this;
    }

    /**
     * @return ArrayCollection
     */
    public function getMetaFieldsValues()
    {
        return $this->meta_fields_values;
    }

    /**
     * @return ArrayCollection
     */
    public function getMetaFieldsValuesFiltered($metaField, $value)
    {
        $criteria = Criteria::create()->where(Criteria::expr()->in("meta_field", array($metaField)));
        $criteria->andWhere(Criteria::expr()->in("value", array($value)));
        return $this->getMetaFieldsValues()->matching($criteria);
    }

    /**
     * @param $meta_fields_values
     * @return $this
     */
    public function setMetaFieldsValues($meta_fields_values)
    {
        if (is_array($meta_fields_values)) {
            $meta_fields_values = new ArrayCollection($meta_fields_values);
        }
        $this->meta_fields_values = $meta_fields_values;
        return $this;
    }

    /**
     * @return \iCoordinator\Entity\Workspace
     */
    public function getWorkspace()
    {
        return $this->workspace;
    }

    /**
     * @param $workspace
     * @returns File
     */
    public function setWorkspace($workspace)
    {
        $this->workspace = $workspace;
        return $this;
    }

    /**
     * @return Lock
     */
    public function getLock()
    {
        return $this->lock;
    }

    /**
     * @param $lock
     * @return $this
     */
    public function setLock($lock)
    {
        $this->lock = $lock;
        return $this;
    }

    /**
     * @return \Carbon\Carbon
     */
    public function getModifiedAt()
    {
        return $this->modified_at;
    }

    /**
     * @param \Carbon\Carbon $modified_at
     * @return File
     */
    public function setModifiedAt($modified_at)
    {
        $this->modified_at = $modified_at;
        return $this;
    }

    /**
     * @return Carbon
     */
    public function getContentCreatedAt()
    {
        return $this->content_created_at;
    }

    /**
     * @param Carbon $content_created_at
     * @return File
     */
    public function setContentCreatedAt(Carbon $content_created_at)
    {
        $this->content_created_at = $content_created_at;
        return $this;
    }

    /**
     * @return \Carbon\Carbon
     */
    public function getContentModifiedAt()
    {
        return $this->content_modified_at;
    }

    /**
     * @param \Carbon\Carbon $content_modified_at
     * @return File
     */
    public function setContentModifiedAt(Carbon $content_modified_at)
    {
        $this->content_modified_at = $content_modified_at;
        return $this;
    }

    /**
     * @return int
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * @param int $size
     * @return File
     */
    public function setSize($size)
    {
        $this->size = $size;
        return $this;
    }

    /**
     * @return string
     */
    public function getMimeType()
    {
        return $this->mime_type;
    }

    /**
     * @param $mime_type
     * @return File
     */
    public function setMimeType($mime_type)
    {
        $this->mime_type = $mime_type;
        return $this;
    }

    /**
     * @return SharedLink
     */
    public function getSharedLink()
    {
        return $this->shared_link;
    }

    /**
     * @param $shared_link
     * @return $this
     */
    public function setSharedLink($shared_link)
    {
        $this->shared_link = $shared_link;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getIsTrashed()
    {
        return $this->is_trashed;
    }

    /**
     * @param boolean $is_trashed
     * @return File
     */
    public function setIsTrashed($is_trashed)
    {
        $this->is_trashed = $is_trashed;
        return $this;
    }

    public function getSelectiveSync()
    {
        return $this->selective_sync;
    }

    public function setSelectiveSync($selective_sync)
    {
        $this->selective_sync = $selective_sync;
    }

    public function getFileEmailOptions()
    {
        return $this->file_email_options;
    }

    public function setFileEmailOptions($file_email_options)
    {
        $this->file_email_options = $file_email_options;
    }

    public function getVersionComment()
    {
        return $this->version_comment;
    }
    public function setVersionComment($version_comment)
    {
        $this->version_comment = $version_comment;
    }

    public function getVersionCreatedAt()
    {
        return $this->version_created_at;
    }
    public function setVersionCreatedAt($version_created_at)
    {
        $this->version_created_at = $version_created_at;
    }
}
