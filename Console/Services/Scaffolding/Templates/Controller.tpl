<?php

namespace {{controllerNamespace}};

use SismaFramework\Core\BaseClasses\BaseController;
use SismaFramework\Core\HttpClasses\Request;
use SismaFramework\Core\HttpClasses\Response;
use SismaFramework\Core\HelperClasses\Render;
use SismaFramework\Core\HelperClasses\Router;
use {{entityNamespace}}\{{entityName}};
use {{formNamespace}}\{{entityName}}Form;
use {{modelNamespace}}\Models\{{entityName}}Model;

class {{entityName}}Controller extends BaseController
{
    
    public function index(): Response
    {
        $model = new {{entityName}}Model();
        ${{entityNameLower}}Collection = $model->getEntityCollection();
        $this->vars['{{entityNameLower}}Collection'] = ${{entityNameLower}}Collection;
        return Render::generateView('{{entityNameLower}}/index', $this->vars);
    }
    
    public function create(Request $request): Response
    {
        ${{entityNameLower}}Form = new {{entityName}}Form();
        ${{entityNameLower}}Form->handleRequest($request);
        if (${{entityNameLower}}Form->isSubmitted() && ${{entityNameLower}}Form->isValid()) {
            $this->dataMapper->save(${{entityNameLower}}Form->getEntity());
            return Router::redirect('{{controllerRoute}}/index');
        }
        $this->vars['{{entityNameLower}}'] = ${{entityNameLower}}Form->getEntityDataToStandardEntity();
        $this->vars['filterErrors'] = ${{entityNameLower}}Form->getFilterErrors();
        return Render::generateView('{{entityNameLower}}/create', $this->vars, ${{entityNameLower}}Form->getResponseType());
    }
    
    public function update(Request $request, {{entityName}} ${{entityNameLower}}): Response
    {
        ${{entityNameLower}}Form = new {{entityName}}Form(${{entityNameLower}});
        ${{entityNameLower}}Form->handleRequest($request);
        if (${{entityNameLower}}Form->isSubmitted() && ${{entityNameLower}}Form->isValid()) {
            $this->dataMapper->save(${{entityNameLower}}Form->getEntity());
            return Router::redirect('{{controllerRoute}}/index');
        }
        $this->vars['{{entityNameLower}}'] = ${{entityNameLower}}Form->isSubmitted() ? ${{entityNameLower}}Form->getEntityDataToStandardEntity() : ${{entityNameLower}};
        $this->vars['filterErrors'] = ${{entityNameLower}}Form->getFilterErrors();
        return Render::generateView('{{entityNameLower}}/update', $this->vars, ${{entityNameLower}}Form->getResponseType());
    }
    
    public function delete({{entityName}} ${{entityNameLower}}): Response
    {
        $this->dataMapper->delete(${{entityNameLower}});
        return Router::redirect('{{controllerRoute}}/index');
    }
}
