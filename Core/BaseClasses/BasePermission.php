<?php

namespace SismaFramework\Core\BaseClasses;

use SismaFramework\Core\BaseClasses\BaseModel;
use SismaFramework\Core\Exceptions\AccessDeniedException;
use SismaFramework\Core\HelperClasses\Session;
use SismaFramework\Core\Interfaces\Entities\UserInterface;

abstract class BasePermission
{

    private static BasePermission $instance;
    protected mixed $subject;
    protected string $attribute;
    protected ?UserInterface $user;
    protected bool $result = true;

    public function __construct(mixed $subject, string $attribute, ?UserInterface $user = null)
    {
        $this->subject = $subject;
        $this->attribute = $attribute;
        $this->user = $user;
        $this->result = ($this->isInstancePermitted() === false) ? false : $this->result;
        $this->checkResult();
        $this->result = ($this->isConstantPermitted() === false) ? false : $this->result;
        $this->checkResult();
        $this->result = ($this->checkPermmisions() === false) ? false : $this->result;
        $this->checkResult();
    }

    abstract protected function isInstancePermitted(): bool;

    private function isConstantPermitted(): bool
    {
        $class = get_called_class();
        $reflectionClass = new \ReflectionClass($class);
        $constantArray = $reflectionClass->getConstants();
        if (in_array($this->attribute, $constantArray)) {
            return true;
        } else {
            return false;
        }
    }

    abstract protected function checkPermmisions(): bool;

    protected function checkResult(): void
    {
        if ($this->result === false) {
            throw new AccessDeniedException();
        }
    }

    static public function isAllowed(mixed $subject, string $attribute, ?UserInterface $user = null): void
    {
        $class = get_called_class();
        self::$instance = new $class($subject, $attribute, $user);
    }

}
