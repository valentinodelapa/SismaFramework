<?php

namespace {{controllerNamespace}};

use SismaFramework\Core\BaseClasses\BaseController;
use SismaFramework\Core\HttpClasses\Request;
use SismaFramework\Core\HttpClasses\Response;
use SismaFramework\Core\HelperClasses\Render;
use SismaFramework\Core\HelperClasses\Router;
use {{entityNamespace}}\{{entityShortName}};
use {{formNamespace}}\{{entityShortName}}Form;
use {{modelNamespace}}\Models\{{entityShortName}}Model;

class {{entityShortName}}Controller extends BaseController
{
    
    public function index(): Response
    {
        $model = new {{entityShortName}}Model();
        ${{entityShortNameLower}}Collection = $model->getEntityCollection();
        $this->vars['{{entityShortNameLower}}Collection'] = ${{entityShortNameLower}}Collection;
        return Render::generateView('{{entityShortNameLower}}/index', $this->vars);
    }
    
    public function create(Request $request): Response
    {
        ${{entityShortNameLower}}Form = new {{entityShortName}}Form();
        ${{entityShortNameLower}}Form->handleRequest($request);
        if (${{entityShortNameLower}}Form->isSubmitted() && ${{entityShortNameLower}}Form->isValid()) {
            $this->dataMapper->save(${{entityShortNameLower}}Form->getEntity());
            return Router::redirect('{{controllerRoute}}/index');
        }
        $this->vars['{{entityShortNameLower}}'] = ${{entityShortNameLower}}Form->getEntityDataToStandardEntity();
        $this->vars['filterErrors'] = ${{entityShortNameLower}}Form->getFilterErrors();
        return Render::generateView('{{entityShortNameLower}}/create', $this->vars, ${{entityShortNameLower}}Form->getResponseType());
    }
    
    public function update(Request $request, {{entityShortName}} ${{entityShortNameLower}}): Response
    {
        ${{entityShortNameLower}}Form = new {{entityShortName}}Form(${{entityShortNameLower}});
        ${{entityShortNameLower}}Form->handleRequest($request);
        if (${{entityShortNameLower}}Form->isSubmitted() && ${{entityShortNameLower}}Form->isValid()) {
            $this->dataMapper->save(${{entityShortNameLower}}Form->getEntity());
            return Router::redirect('{{controllerRoute}}/index');
        }
        $this->vars['{{entityShortNameLower}}'] = ${{entityShortNameLower}}Form->isSubmitted() ? ${{entityShortNameLower}}Form->getEntityDataToStandardEntity() : ${{entityShortNameLower}};
        $this->vars['filterErrors'] = ${{entityShortNameLower}}Form->getFilterErrors();
        return Render::generateView('{{entityShortNameLower}}/update', $this->vars, ${{entityShortNameLower}}Form->getResponseType());
    }
    
    public function delete({{entityShortName}} ${{entityShortNameLower}}): Response
    {
        $this->dataMapper->delete(${{entityShortNameLower}});
        return Router::redirect('{{controllerRoute}}/index');
    }
}
