<?php

namespace SismaFramework\Sample\Controllers;

use SismaFramework\Core\BaseClasses\BaseController;
use SismaFramework\Core\Enumerations\ResponseType;
use SismaFramework\Core\HttpClasses\Response;
use SismaFramework\Core\HelperClasses\Render;
use SismaFramework\Core\HelperClasses\Router;
use SismaFramework\Core\Interfaces\Controllers\DefaultControllerInterface;
use SismaFramework\Orm\HelperClasses\DataMapper;

class SampleController extends BaseController implements DefaultControllerInterface
{
    
    public function __construct(DataMapper $dataMapper = new DataMapper())
    {
        parent::__construct($dataMapper);
        $this->vars['metaUrl'] = Router::getMetaUrl();
    }
    
    public function index(): Response
    {
        return Render::generateView('sample/index', $this->vars);
    }
    
    #[\Override]
    public function error(string $message, ResponseType $responseType): Response
    {
        $this->vars['message'] = urldecode($message);
        return Render::generateView('sample/error', $this->vars, $responseType);
    }

    #[\Override]
    public function notify(string $message): Response
    {
        $this->vars['message'] = urldecode($message);
        return Render::generateView('sample/notify', $this->vars);
    }

}
