<?php

namespace Sisma\Core\Interfaces\Wrappers;

use Sisma\Core\Interfaces\Entities\MultiFactorInterface;

interface MultiFactorWrapperInterface
{

    public function testCodeForLogin(MultiFactorInterface $multiFactorInterface, string $code): bool;
}
