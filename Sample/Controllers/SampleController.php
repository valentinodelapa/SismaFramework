<?php

namespace SismaFramework\Sample\Controllers;

use SismaFramework\Core\BaseClasses\BaseController;
use SismaFramework\Core\HttpClasses\Response;
use SismaFramework\Core\HelperClasses\Render;
use SismaFramework\Core\HelperClasses\Router;

class SampleController extends BaseController
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
