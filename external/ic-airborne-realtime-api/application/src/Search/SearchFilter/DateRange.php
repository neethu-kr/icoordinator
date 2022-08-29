<?php

namespace iCoordinator\Search\SearchFilter;

use Carbon\Carbon;

class DateRange
{
    /**
     * @var Carbon
     */
    private $from;

    /**
     * @var Carbon
     */
    private $to;

    public function __construct(Carbon $from = null, Carbon $to = null)
    {
        if ($from === null && $to === null) {
            throw new \InvalidArgumentException('At least one of params should be defined');
        }
        $this->from = $from;
        $this->to = $to;
    }

    /**
     * @return Carbon|null
     */
    public function getFrom()
    {
        return $this->from;
    }

    /**
     * @return Carbon|null
     */
    public function getTo()
    {
        return $this->to;
    }
}
