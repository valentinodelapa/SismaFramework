<?php

namespace SismaFramework\Core\ObjectRelationalMapper\Enumerations;

enum OrmKeyword: string
{
    case insertInto = 'INSERT INTO';
    case update = 'UPDATE';
    case deleteFrom = 'DELETE FROM';
    case select = 'SELECT';
    case insertValue = 'VALUES';
    case set = 'SET';
    case from = 'FROM';
    case where = 'WHERE';
    case having = 'HAVING';
    case distinct = 'DISTINCT';
    case join = 'JOIN';
    case orderBy = 'ORDER BY';
    case groupBy = 'GROUP_BY';
    case asc = 'ASC';
    case desc = 'DESC';
    case limit = 'LIMIT';
    case offset = 'OFFSET';
    case placeholder = '?';
    case openBlock = '(';
    case closeBlock = ')';
}
