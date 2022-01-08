<?php

namespace SismaFramework\Core\Interfaces\Models;

interface UserModelInterface
{

    public function testUniqueUsername(string $username);

    public function testUniqueEmail(string $email);

    public function getEntityByUsername(string $username);
}
