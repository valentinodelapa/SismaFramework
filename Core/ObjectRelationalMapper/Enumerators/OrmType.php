<?php

namespace Sisma\Core\ObjectRelationalMapper\Enumerators;

use Sisma\Core\BaseClasses\BaseEnumerator;

class OrmType extends BaseEnumerator
{
    private const BOOLEAN = 'B';
    private const NULL = '0';
    private const INTEGER = 'i';
    private const STRING = 's';
    private const BINARY = 'b';
    private const DECIMAL = 'd';
    private const DATE = 'D';
    private const STMT = 'X';
    private const ENTITY = 'E';
    private const ENUMERATOR = 'e';
    private const GENERIC = '?';
}
