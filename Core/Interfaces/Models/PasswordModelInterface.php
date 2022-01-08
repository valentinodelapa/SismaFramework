<?php

namespace SismaFramework\Core\Interfaces\Models;

use SismaFramework\Core\Interfaces\Entities\UserInterface;
use SismaFramework\Core\Interfaces\Entities\PasswordInterface;

interface PasswordModelInterface
{
    public function getLastPasswordByUserInterface(UserInterface $userInterface): ?PasswordInterface;
}
