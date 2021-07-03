<?php

namespace Sisma\Core\Interfaces\Models;

use Sisma\Core\Interfaces\Entities\MultiFactorInterface;
use Sisma\Core\Interfaces\Entities\MultiFactorRecoveryInterface;

interface MultiFactorRecoveryModelInterface
{

    public function getMultiFactorRecoveryInterfaceByParameters(MultiFactorInterface $multiFactorInterface, string $code): ?MultiFactorRecoveryInterface;
}
