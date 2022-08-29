<?php

namespace iCoordinator\Search\SearchFilter;

class NumericRange
{
    /**
     * @var int
     */
    private $from;

    /**
     * @var int
     */
    private $to;

    public function __construct($from = null, $to = null)
    {
        if ($from === null && $to === null) {
            throw new \InvalidArgumentException('At least one of params should be defined');
        }
        $this->from = $from;
        $this->to = $to;
    }

    /**
     * @return int|null
     */
    public function getFrom()
    {
        return $this->from;
    }

    /**
     * @return int|null
     */
    public function getTo()
    {
        return $this->to;
    }
}
