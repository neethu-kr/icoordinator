<?php

namespace iCoordinator\Search\SearchFilter;

class Terms
{
    /**
     * @var array
     */
    private $terms;

    public function __construct(array $terms)
    {
        $this->terms = $terms;
    }

    /**
     * @return array
     */
    public function getTerms()
    {
        return $this->terms;
    }
}
