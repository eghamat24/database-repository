<?php

namespace Eghamat24\DatabaseRepository\Models\Enums;

class GriewFilterOperator extends Enum
{
    public const IS_EQUAL_TO = 'is_equal_to';
    public const IS_EQUAL_TO_OR_NULL = 'is_equal_to_or_null';
    public const IS_NOT_EQUAL_TO = 'is_not_equal_to';
    public const IS_NULL = 'is_null';
    public const IS_NOT_NULL = 'is_not_null';
    public const START_WITH = 'start_with';
    public const DOES_NOT_CONTAINS = 'does_not_contains';
    public const CONTAINS = 'contains';
    public const ENDS_WITH = 'ends_with';
    public const IN = 'in';
    public const NOT_IN = 'not_In';
    public const BETWEEN = 'between';
    public const IS_GREATER_THAN_OR_EQUAL_TO = 'is_greater_than_or_equal_to';
    public const IS_GREATER_THAN = 'is_greater_than';
    public const IS_LESS_THAN_OR_EQUAL_TO = 'is_less_than_or_equal_to';
    public const IS_LESS_THAN = 'is_less_than';
    public const IS_AFTER_THAN_OR_EQUAL_TO = 'is_after_than_or_equal_to';
    public const IS_AFTER_THAN = 'is_after_than';
    public const IS_BEFORE_THAN_OR_EQUAL_TO = 'is_Before_than_or_equal_to';
    public const IS_BEFORE_THAN = 'is_before_than';
//    public const IS_INSIDE_POLYGON = 'is_inside_polygon';
//    public const IS_NEAR_TO_POINT = 'is_near_to_point';
}
