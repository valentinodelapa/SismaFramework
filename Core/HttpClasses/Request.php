<?php

namespace Sisma\Core\HttpClasses;

use Sisma\Core\Enumerators\RequestType;

class Request
{
    public $query;
    public $request;
    public $cookie;
    public $files;
    public $server;
    public $headers;
    
    public function __construct()
    {
        $this->query = $_GET;
        $this->request = $_POST;
        $this->cookie = $_COOKIE;
        $this->files = $_FILES;
        $this->server = $_SERVER;
    }
}
