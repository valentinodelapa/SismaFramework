<?php

namespace SismaFramework\Core\Interfaces;

use SismaFramework\Core\HttpClasses\Request;
use SismaFramework\Core\HttpClasses\Response;

interface CrudInterface
{

    public function create(?Request $request): Response;

    public function view(): Response;

    public function update(): Response;

    public function delete(): Response;
}
