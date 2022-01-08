<?php

namespace SismaFramework\Core\Interfaces\Models;

use SismaFramework\Core\Interfaces\Entities\UserInterface;
use SismaFramework\Core\Interfaces\Entities\MultiFactorInterface;

interface MultiFactorModelInterface
{

    public function getLastActiveMultiFactorByUserIterface(UserInterface $userInterface): ?MultiFactorInterface;
}
