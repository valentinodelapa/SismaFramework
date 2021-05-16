<?php

namespace Sisma\Core\BaseClasses;

use Sisma\Core\BaseClasses\BaseModel;
use Sisma\Core\Exceptions\AccessDeniedException;
use Sisma\Core\HelperClasses\Session;
use Sisma\Core\Interfaces\Entities\UserInterface;

abstract class BasePermission
{

    private static BasePermission $instance;
    protected ?UserInterface $user;
    protected bool $result = true;

    public function __construct($subject, string $attribute, ?UserInterface $user = null)
    {
        $this->user = $user;
        $this->result = ($this->isInstancePermitted($subject) === false) ? false : $this->result;
        $this->result = ($this->isConstantPermitted($attribute) === false) ? false : $this->result;
        $this->result = ($this->checkPermmisions($subject, $attribute) === false) ? false : $this->result;
        $this->checkResult();
    }

    abstract protected function isInstancePermitted($subject): bool;

    private function isConstantPermitted(string $attribute): bool
    {
        $class = get_called_class();
        $reflectionClass = new \ReflectionClass($class);
        $constantArray = $reflectionClass->getConstants();
        if (in_array($attribute, $constantArray)) {
            return true;
        } else {
            return false;
        }
    }

    abstract protected function checkPermmisions($subject, string $attribute): bool;

    protected function checkResult(): void
    {
        if ($this->result === false) {
            throw new AccessDeniedException();
        }
    }

    static public function isAllowed($subject, string $attribute, ?UserInterface $user = null): void
    {
        $class = get_called_class();
        self::$instance = new $class($subject, $attribute, $user);
    }

}
