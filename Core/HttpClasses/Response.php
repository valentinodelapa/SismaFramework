<?php

namespace SismaFramework\Core\HttpClasses;

use SismaFramework\Core\Enumerations\ResponseType;

class Response
{
    private ResponseType $responseType;
    
    public function __construct()
    {
        $this->responseType = ResponseType::from(intval(http_response_code()));
    }
}
