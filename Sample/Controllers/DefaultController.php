<?php

namespace Sisma\Sample\Controllers;

use Sisma\Core\BaseClasses\BaseController;
use Sisma\Core\HttpClasses\Response;
use Sisma\Core\HelperClasses\Render;
use Sisma\Core\HelperClasses\Router;

class DefaultController extends BaseController
{

    public function index(): Response
    {
        return Render::generateView('default/index', $this->vars);
    }
    
    public function error($message): Response
    {
        $this->vars = [
            'message' => urldecode($message),
        ];
        return Render::generateView('default/error', $this->vars);
    }

    public function notify($message): Response
    {
        $this->vars = [
            'message' => urldecode($message),
        ];
        return Render::generateView('default/notify', $this->vars);
    }

    public function project(): Response
    {
        
        return Render::generateView('default/project', $this->vars);
    }

}
