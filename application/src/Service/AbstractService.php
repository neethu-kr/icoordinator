<?php

namespace iCoordinator\Service;

use Doctrine\DBAL\Logging\DebugStack;
use iCoordinator\EntityManagerAwareTrait;

class AbstractService implements ServiceInterface
{
    use EntityManagerAwareTrait;

    /**
     * @var bool
     */
    private $autoCommitChanges = true;

    /**
     * @return bool
     */
    public function getAutoCommitChanges()
    {
        return $this->autoCommitChanges;
    }

    /**
     * @param $autoCommitChanges
     */
    public function setAutoCommitChanges($autoCommitChanges)
    {
        $this->autoCommitChanges = $autoCommitChanges;
    }

    /**
     * @return DebugStack
     */
    protected function getSqlLogger()
    {
        return $this->getContainer()->get('sqlLogger');
    }
}
