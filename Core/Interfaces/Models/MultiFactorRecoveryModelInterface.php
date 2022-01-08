<?php

namespace SismaFramework\Core\Interfaces\Models;

use SismaFramework\Core\Interfaces\Entities\MultiFactorInterface;
use SismaFramework\Core\Interfaces\Entities\MultiFactorRecoveryInterface;

interface MultiFactorRecoveryModelInterface
{

    public function getMultiFactorRecoveryInterfaceByParameters(MultiFactorInterface $multiFactorInterface, string $code): ?MultiFactorRecoveryInterface;
}
