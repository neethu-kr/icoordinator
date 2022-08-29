<?php

namespace iCoordinator\Service\Helper;

use Carbon\Carbon;
use DoctrineExtensions\Query\Mysql\Date;
use iCoordinator\Search\SearchFilter\DateRange;
use iCoordinator\Search\SearchFilter\NumericRange;
use iCoordinator\Search\SearchFilter\Term;
use iCoordinator\Search\SearchFilter\Terms;

class FilterParamsParser
{
    const PARAM_TYPE_NUMERIC_RANGE = 'numeric_range';
    const PARAM_TYPE_DATE_RANGE = 'date_range';
    const PARAM_TYPE_NUMERIC_LIST = 'numeric_list';
    const PARAM_TYPE_LIST = 'list';

    /**
     * @param $value
     * @param null $type
     * @return array|string|null
     * @throws \Exception
     */
    public static function parse($value, $type = null)
    {
        switch ($type) {
            case self::PARAM_TYPE_NUMERIC_RANGE:
                $parts = explode(',', $value);
                if (count($parts) < 2) {
                    throw new \Exception('Wrong numeric range param format');
                }
                $from = $parts[0];
                $to = $parts[1];
                if ((!empty($from) && !is_numeric($from)) || (!empty($to) && !is_numeric($to))) {
                    throw new \Exception('Wrong numeric range param format');
                }
                if (empty($from)) {
                    $from = null;
                }
                if (empty($to)) {
                    $to = null;
                }
                if ($from !== null || $to !== null) {
                    return new NumericRange($from, $to);
                }
                break;

            case self::PARAM_TYPE_DATE_RANGE:
                $parts = explode(',', $value);
                if (count($parts) < 2) {
                    throw new \Exception('Wrong date range param format');
                }
                $from = $parts[0];
                $to = $parts[1];
                if (!empty($from)) {
                    $from = Carbon::createFromFormat(Carbon::ISO8601, $from);
                } else {
                    $from = null;
                }
                if (!empty($to)) {
                    $to = Carbon::createFromFormat(Carbon::ISO8601, $from);
                } else {
                    $to = null;
                }
                if ($from !== null || $to !== null) {
                    return new DateRange($from, $to);
                }
                break;

            case self::PARAM_TYPE_NUMERIC_LIST:
                $parts = explode(',', $value);
                array_walk($parts, function ($value) {
                    if (!is_numeric($value)) {
                        throw new \Exception('Wrong numeric list param format');
                    }
                });
                if (count($parts) > 0) {
                    return new Terms($parts);
                }
                break;

            case self::PARAM_TYPE_LIST:
                $parts =  explode(',', $value);
                if (count($parts) > 0) {
                    return new Terms($parts);
                }
                break;

            default:
                if (!empty($value)) {
                    return new Term($value);
                }
                break;
        }

        return null;
    }
}
