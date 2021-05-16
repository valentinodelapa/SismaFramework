<?php

namespace Sisma\Core\ObjectRelationalMapper\Enumerators;

use Sisma\Core\BaseClasses\BaseEnumerator;

class OrmOperator extends BaseEnumerator
{
    private const EQUAL = '=';
    private const NOT_EQUAL_ONE = '!=';
    private const NOT_EQAUL_TWO = '<>';
    private const GREATER = '>';
    private const LESS = '<';
    private const GREATER_OR_EQUAL = '>=';
    private const LESS_OR_EQUAL = '<=';
    private const IN = 'IN';
    private const NOT_IN = 'NOT IN';
    private const LIKE = 'LIKE';
    private const NOT_LIKE = 'NOT LIKE';
    private const IS_NULL = 'IS NULL';
    private const IS_NOT_NULL = 'IS NOT NULL';
    
    private const AND = 'AND';
    private const OR = 'OR';
    private const NOT = 'NOT';
}
