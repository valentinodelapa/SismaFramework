<?php

namespace Sisma\Core\ObjectRelationalMapper\Enumerators;

use Sisma\Core\BaseClasses\BaseEnumerator;

class OrmKeyword extends BaseEnumerator
{
    private const INSERT_INTO = 'INSERT INTO';
    private const UPDATE = 'UPDATE';
    private const DELETE_FROM = 'DELETE FROM';
    private const SELECT = 'SELECT';
    private const INSERT_VALUES = 'VALUES';
    private const SET = 'SET';
    private const FROM = 'FROM';
    private const WHERE = 'WHERE';
    private const HAVING = 'HAVING';
    private const DISTINCT = 'DISTINCT';
    private const JOIN = 'JOIN';
    private const ORDER_BY = 'ORDER BY';
    private const GROUP_BY = 'GROUP_BY';
    private const ASC = 'ASC';
    private const DESC = 'DESC';
    private const LIMIT = 'LIMIT';
    private const OFFSET = 'OFFSET';
    private const PLACEHOLDER = '?';
    private const OPEN_BLOCK = '(';
    private const CLOSE_BLOCK = ')';
}
