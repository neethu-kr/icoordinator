<?php

namespace iCoordinator\Search\SearchFilter;

class Term
{
    /**
     * @var string
     */
    private $term;

    public function __construct($term)
    {
        $this->term = $term;
    }

    /**
     * @return string
     */
    public function getTerm()
    {
        return $this->term;
    }
}
