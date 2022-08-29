<?php

namespace iCoordinator\Entity;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * @Entity
 */
class Folder extends File
{
    const ENTITY_NAME = 'entity:Folder';

    /**
     * @var ArrayCollection
     * @OneToMany(targetEntity="File", mappedBy="parent", fetch="EXTRA_LAZY")
     */
    protected $children;


    public function __construct()
    {
        parent::__construct();
        $this->children = new ArrayCollection();
    }

    public static function getType()
    {
        return 'folder';
    }

    public static function getEntityName()
    {
        return self::ENTITY_NAME;
    }

    /**
     * @return ArrayCollection
     */
    public function getChildren()
    {
//        $criteria = Criteria::create();
//        if ($showPending !== true) {
//            $criteria->where(Criteria::expr()->eq('approved', true));
//        }
//        return $this->comments->matching($criteria);

        return $this->children;
    }

    /**
     * @param ArrayCollection|array $children
     * @return Folder
     */
    public function setChildren($children)
    {
        if (is_array($children)) {
            $children = new ArrayCollection($children);
        }
        $this->children = $children;
        return $this;
    }

    /**
     * @param File $child
     * @return $this
     */
    public function addChild(File $child)
    {
        $child->setParent($this);
        $this->children->add($child);

        return $this;
    }

    /**
     * @param File $child
     * @return $this
     */
    public function removeChild(File $child)
    {
        $child->setParent(null);
        $this->children->removeElement($child);

        return $this;
    }

    public function jsonSerialize($mini = false, $fields = array(), $childrenOffset = 0, $childrenLimit = 100)
    {
        $result = parent::jsonSerialize($mini, $fields);

        unset($result['mime_type']);
        //unset($result['lock']);
        $result['entity_type'] = 'folder';

        //TODO refactoring for better custom fields extracting
        if (!empty($fields)) {
            foreach (array_keys($result) as $key) {
                if (!in_array($key, $fields)) {
                    unset($result[$key]);
                }
            }
        }

        return $result;
    }
}
