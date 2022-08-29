<?php

namespace iCoordinator\Entity;

class Error
{
    const FILE_SIZE_LIMIT_EXCEEDED = 'file_size_limit_exceeded';
    const WORKSPACE_SIZE_LIMIT_EXCEEDED = 'workspace_size_limit_exceeded';
    const LICENSE_UPDATE_REQUIRED = 'license_update_required';
    const VALIDATION_FAILED = 'validation_failed';
    const ITEM_SYNC_DISABLED = 'item_sync_disabled';
    const INVALID_CHARACTERS = 'invalid_characters';

    /**
     * @param string $type
     */
    public function __construct($type)
    {
        $this->type  = $type;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    public function jsonSerialize()
    {
        return array(
            'type' => $this->getType()
        );
    }
}
