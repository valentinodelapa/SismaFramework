<?php

namespace SismaFramework\Sample\Controllers;

use SismaFramework\Core\BaseClasses\BaseController;
use SismaFramework\Core\Enumerations\ResponseType;
use SismaFramework\Core\HttpClasses\Response;
use SismaFramework\Core\HelperClasses\Render;
use SismaFramework\Core\HelperClasses\Router;
use SismaFramework\Core\Interfaces\Controllers\DefaultControllerInterface;

class SampleController extends BaseController implements DefaultControllerInterface
{
    
    public function __construct()
    {
        parent::__construct();
        $this->vars['metaUrl'] = Router::getMetaUrl();
    }

    public function index(): Response
    {
        return Render::generateView('sample/index', $this->vars);
    }
    
    public function error(string $message, ResponseType $responseType): Response
    {
        $this->vars['message'] = urldecode($message);
        return Render::generateView('sample/error', $this->vars, $responseType);
    }

    public function notify(string $message): Response
    {
        $this->vars['message'] = urldecode($message);
        return Render::generateView('sample/notify', $this->vars);
    }

    public function project(): Response
    {
        
        return Render::generateView('sample/project', $this->vars);
    }

}
