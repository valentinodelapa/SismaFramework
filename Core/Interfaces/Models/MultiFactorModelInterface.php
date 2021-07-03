<?php

namespace Sisma\Core\Interfaces\Models;

use Sisma\Core\Interfaces\Entities\UserInterface;
use Sisma\Core\Interfaces\Entities\MultiFactorInterface;

interface MultiFactorModelInterface
{

    public function getLastActiveMultiFactorByUserIterface(UserInterface $userInterface): ?MultiFactorInterface;
}
