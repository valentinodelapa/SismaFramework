<?php

namespace SismaFramework\Core\ObjectRelationalMapper\Enumerations;

enum OrmOperator: string
{
    case equal = '=';
    case notEqualOne = '!=';
    case notEqualTwo = '<>';
    case greater = '>';
    case less = '<';
    case greaterOrEqual = '>=';
    case lessOrEqual = '<=';
    case in = 'IN';
    case notIn = 'NOT IN';
    case like = 'LIKE';
    case notLike = 'NOT LIKE';
    case isNull = 'IS NULL';
    case isNotNull = 'IS NOT NULL';
    
    case and = 'AND';
    case or = 'OR';
    case not = 'NOT';
}
