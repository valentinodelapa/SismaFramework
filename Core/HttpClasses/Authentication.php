<?php

namespace Sisma\Core\HttpClasses;

use Sisma\Core\Enumerators\RequestType;
use Sisma\Core\HelperClasses\Encryptor;
use Sisma\Core\HelperClasses\Filter;
use Sisma\Core\HttpClasses\Request;
use Sisma\Core\Interfaces\Entities\MultiFactorInterface;
use Sisma\Core\Interfaces\Entities\MultiFactorRecoveryInterface;
use Sisma\Core\Interfaces\Entities\PasswordInterface;
use Sisma\Core\Interfaces\Entities\UserInterface;
use Sisma\Core\Interfaces\Models\MultiFactorModelInterface;
use Sisma\Core\Interfaces\Models\MultiFactorRecoveryModelInterface;
use Sisma\Core\Interfaces\Models\PasswordModelInterface;
use Sisma\Core\Interfaces\Models\UserModelInterface;
use Sisma\Core\Interfaces\Wrappers\MultiFactorWrapperInterface;

class Authentication
{

    use \Sisma\Core\Traits\Submitted;

    private MultiFactorModelInterface $multiFactorModelInterface;
    private MultiFactorRecoveryModelInterface $multiFactorRecoveryModelInterface;
    private MultiFactorWrapperInterface $multiFactorWrapperInterface;
    private Request $request;
    private PasswordModelInterface $passwordModelInterface;
    private ?UserInterface $userInterface;
    private UserModelInterface $userModelInterface;
    private array $filterErrors = [
        "usernameError" => false,
        "passwordError" => false,
        "codeError" => false,
    ];

    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->requestType = new RequestType($request->server['REQUEST_METHOD']);
    }

    public function setUserModel(UserModelInterface $userModelInterface): void
    {
        $this->userModelInterface = $userModelInterface;
    }

    public function setPasswordModel(PasswordModelInterface $passwordModelInterface): void
    {
        $this->passwordModelInterface = $passwordModelInterface;
    }

    public function setMultiFactorModel(MultiFactorModelInterface $multiFactorModelInterface): void
    {
        $this->multiFactorModelInterface = $multiFactorModelInterface;
    }

    public function setMultiFactorRecoveryModel(MultiFactorRecoveryModelInterface $multiFactorRecoveryModelInterface): void
    {
        $this->multiFactorRecoveryModelInterface = $multiFactorRecoveryModelInterface;
    }

    public function setMultiFactorWrapper(MultiFactorWrapperInterface $multiFactorWrapperInterface): void
    {
        $this->multiFactorWrapperInterface = $multiFactorWrapperInterface;
    }

    public function checkUser(): bool
    {
        if (Filter::isString($this->request->request['username'])) {
            $this->userInterface = $this->userModelInterface->getEntityByUsername($this->request->request['username']);
            if (($this->userInterface instanceof UserInterface) && $this->checkPassword($this->userInterface)) {
                return true;
            } else {
                $this->filterErrors['passwordError'] = true;
            }
        }
        $this->filterErrors['usernameError'] = true;
        return false;
    }

    public function checkPassword(UserInterface $userInterface): bool
    {
        if (Filter::isString($this->request->request['password'])) {
            $passwordInterface = $this->passwordModelInterface->getLastPasswordByUserInterface($userInterface);
            return Encryptor::verifyBlowfishHash($this->request->request['password'], $passwordInterface->password);
        }
        return false;
    }

    public function checkMultiFactor(UserInterface $userInterface): bool
    {
        if (Filter::isString($this->request->request['code'])) {
            $multiFactorInterface = $this->multiFactorModelInterface->getLastActiveMultiFactorByUserIterface($userInterface);
            if ($this->multiFactorWrapperInterface->testCodeForLogin($multiFactorInterface, $this->request->request['code'])){
                return true;
            }elseif ($this->checkMultiFactorBackup($multiFactorInterface, $this->request->request['code'])) {
                return true;
            } else {
                $this->filterErrors['codeError'] = true;
            }
        }
        $this->filterErrors['codeError'] = true;
        return false;
    }

    public function checkMultiFactorBackup(MultiFactorInterface $multiFactorInterface, string $code): bool
    {
        $multiFactorRecoveryInterface = $this->multiFactorRecoveryModelInterface->getMultiFactorRecoveryInterfaceByParameters($multiFactorInterface, $code);
        if($multiFactorRecoveryInterface instanceof MultiFactorRecoveryInterface){
            $multiFactorRecoveryInterface->delete();
            return true;
        }else{
            return false;
        }
    }

    public function getUserInterface(): UserInterface
    {
        return $this->userInterface;
    }

}
