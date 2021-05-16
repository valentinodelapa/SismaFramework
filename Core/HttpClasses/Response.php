<?php

namespace Sisma\Core\HttpClasses;

use Sisma\Core\Enumerators\ResponseType;

class Response
{
    private ResponseType $responseType;
    
    public function __construct()
    {
        $this->responseType = new ResponseType(intval(http_response_code()));
    }
}
