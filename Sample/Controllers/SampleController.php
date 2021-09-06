<?php

namespace Sisma\Sample\Controllers;

use Sisma\Core\BaseClasses\BaseController;
use Sisma\Core\HttpClasses\Response;
use Sisma\Core\HelperClasses\Render;
use Sisma\Core\HelperClasses\Router;

class SampleController extends BaseController
{

    public function index(): Response
    {
        return Render::generateView('sample/index', $this->vars);
    }
    
    public function error($message): Response
    {
        $this->vars = [
            'message' => urldecode($message),
        ];
        return Render::generateView('sample/error', $this->vars);
    }

    public function notify($message): Response
    {
        $this->vars = [
            'message' => urldecode($message),
        ];
        return Render::generateView('sample/notify', $this->vars);
    }

    public function project(): Response
    {
        
        return Render::generateView('sample/project', $this->vars);
    }

}
