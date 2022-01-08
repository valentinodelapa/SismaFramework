<?php

namespace SismaFramework\Core\ObjectRelationalMapper\Enumerations;

enum OrmType
{
    case typeBoolean;
    case typeNull;
    case typeInteger;
    case typeString;
    case typeBinary;
    case typeDecimal;
    case typeDate;
    case typeStatement;
    case typeEntity;
    case typeEnumeration;
    case typeGeneric;
}
