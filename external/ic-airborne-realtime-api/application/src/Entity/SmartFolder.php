<?php

namespace iCoordinator\Entity;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * @Entity
 */
class SmartFolder extends File
{
    const ENTITY_NAME = 'entity:SmartFolder';

    /**
     * @var ArrayCollection
     * @OneToMany(
     *  targetEntity="iCoordinator\Entity\MetaFieldCriterion",
     *  mappedBy="smart_folder",
     *  fetch="EXTRA_LAZY",
     *  cascade={"all"}
     * )
     */
    protected $meta_fields_criteria;


    public function __construct()
    {
        parent::__construct();
        $this->meta_fields_criteria = new ArrayCollection();
    }

    public static function getType()
    {
        return 'smart_folder';
    }

    public static function getEntityName()
    {
        return self::ENTITY_NAME;
    }

    /**
     * @return ArrayCollection
     */
    public function getMetaFieldsCriteria()
    {
        return $this->meta_fields_criteria;
    }

    /**
     * @param $meta_fields_criteria
     * @return $this
     */
    public function setMetaFieldsCriteria($meta_fields_criteria)
    {
        $this->meta_fields_criteria = $meta_fields_criteria;
        return $this;
    }

    public function jsonSerialize($mini = false, $fields = array(), $childrenOffset = 0, $childrenLimit = 100)
    {
        $result = parent::jsonSerialize($mini, $fields);

        unset($result['mime_type']);
        unset($result['lock']);
        $result['entity_type'] = 'smart_folder';

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
