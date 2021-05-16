<?php

namespace Sisma\Core\Interfaces\Models;

use Sisma\Core\Interfaces\Entities\UserInterface;
use Sisma\Core\Interfaces\Entities\PasswordInterface;

interface PasswordModelInterface
{
    public function getLastPasswordByUserInterface(UserInterface $userInterface): ?PasswordInterface;
}
