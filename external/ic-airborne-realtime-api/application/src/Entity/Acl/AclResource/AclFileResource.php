<?php

namespace iCoordinator\Entity\Acl\AclResource;

use iCoordinator\Entity\Acl\AclResource;

/**
 * @Entity
 */
class AclFileResource extends AclResource
{
    const ENTITY_NAME = 'entity:Acl\AclResource\AclFileResource';

    const ACL_RESOURCE_ENTITY_TYPE = 'file';

    /**
     * @var \iCoordinator\Entity\File
     * @ManyToOne(targetEntity="\iCoordinator\Entity\File")
     * @JoinColumn(name="entity_id", referencedColumnName="id")
     */
    protected $file;

    /**
     * @return string
     */
    public function getAclResourceEntityType()
    {
        return self::ACL_RESOURCE_ENTITY_TYPE;
    }

    /**
     * @return \iCoordinator\Entity\File
     */
    public function getResource()
    {
        return $this->getFile();
    }

    /**
     * @return \iCoordinator\Entity\File
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * @param \iCoordinator\Entity\File $file
     * @return AclFileResource
     */
    public function setFile($file)
    {
        $this->file = $file;
        return $this;
    }

    public function jsonSerialize()
    {
        return $this->getFile()->jsonSerialize(true);
    }
}
