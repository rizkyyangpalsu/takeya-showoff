<?php

return [
    'entities' => [
        'rule' => App\Models\Rule::class,
        'item' => App\Models\Rule\Item::class,
    ],

    'datatype' => [
        'string',
        'bool',
        'int',
        'float',
        'double',
    ],

    'operators' => [
        // Logical
        'AND' => Ruler\Operator\LogicalAnd::class,
        'OR' => Ruler\Operator\LogicalOr::class,
        'NOT' => Ruler\Operator\LogicalNot::class,
        'XOR' => Ruler\Operator\LogicalXor::class,

        // Arithmetic and Other
        'GREATER_THAN' => Ruler\Operator\GreaterThan::class,                                                       // true if $a > $b
        'GREATER_THAN_OR_EQUAL_TO' => Ruler\Operator\GreaterThanOrEqualTo::class,                                  // true if $a >= $b
        'LESS_THAN' => Ruler\Operator\LessThan::class,                                                             // true if $a < $b
        'LESS_THAN_OR_EQUAL_TO' => Ruler\Operator\LessThanOrEqualTo::class,                                        // true if $a <= $b
        'EQUAL_TO' => Ruler\Operator\EqualTo::class,                                                               // true if $a == $b
        'NOT_EQUAL_TO' => Ruler\Operator\NotEqualTo::class,                                                        // true if $a != $b
        'STR_CONTAINS' => Ruler\Operator\StringContains::class,                                                    // true if strpos($b, $a) !== false
        'STR_DOES_NOT_CONTAINS' => Ruler\Operator\StringDoesNotContain::class,                                     // true if strpos($b, $a) === false
        'STR_CONTAINS_INSENSITIVE' => Ruler\Operator\StringContainsInsensitive::class,                             // true if stripos($b, $a) !== false
        'STR_DOES_NOT_CONTAINS_INSENSITIVE' => Ruler\Operator\StringDoesNotContainInsensitive::class,              // true if stripos($b, $a) === false
        'START_WITH' => Ruler\Operator\StartsWith::class,                                                          // true if strpos($b, $a) === 0
        'START_WITH_INSENSITIVE' => Ruler\Operator\StartsWithInsensitive::class,                                   // true if stripos($b, $a) === 0
        'ENDS_WITH' => Ruler\Operator\EndsWith::class,                                                             // true if strpos($b, $a) === len($a) - len($b)
        'ENDS_WITH_INSENSITIVE' => Ruler\Operator\EndsWithInsensitive::class,                                      // true if stripos($b, $a) === len($a) - len($b)
        'SAME_AS' => Ruler\Operator\SameAs::class,                                                                 // true if $a === $b
        'NOT_SAME_AS' => Ruler\Operator\NotSameAs::class,                                                          // true if $a !== $b
    ],
];
