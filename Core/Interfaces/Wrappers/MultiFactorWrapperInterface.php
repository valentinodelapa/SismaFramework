<?php

namespace SismaFramework\Core\Interfaces\Wrappers;

use SismaFramework\Core\Interfaces\Entities\MultiFactorInterface;

interface MultiFactorWrapperInterface
{

    public function testCodeForLogin(MultiFactorInterface $multiFactorInterface, string $code): bool;
}
