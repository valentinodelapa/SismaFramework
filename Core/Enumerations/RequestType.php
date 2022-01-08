<?php

namespace SismaFramework\Core\Enumerations;

enum RequestType: string
{
    case methodHead = 'HEAD';
    case methodGet = 'GET';
    case methodPost = 'POST';
    case methodPut = 'PUT';
    case methodPatch = 'PATCH';
    case methodDelete = 'DELETE';
    case methodPurge = 'PURGE';
    case methodOptions = 'OPTIONS';
    case methodTrace = 'TRACE';
    case methodConnect = 'CONNECT';
}
