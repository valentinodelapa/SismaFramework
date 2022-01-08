<?php

namespace SismaFramework\Core\HttpClasses;

use SismaFramework\Core\Enumerations\RequestType;
use SismaFramework\Core\HelperClasses\Encryptor;
use SismaFramework\Core\HelperClasses\Filter;
use SismaFramework\Core\HttpClasses\Request;
use SismaFramework\Core\Interfaces\Entities\MultiFactorInterface;
use SismaFramework\Core\Interfaces\Entities\MultiFactorRecoveryInterface;
use SismaFramework\Core\Interfaces\Entities\PasswordInterface;
use SismaFramework\Core\Interfaces\Entities\UserInterface;
use SismaFramework\Core\Interfaces\Models\MultiFactorModelInterface;
use SismaFramework\Core\Interfaces\Models\MultiFactorRecoveryModelInterface;
use SismaFramework\Core\Interfaces\Models\PasswordModelInterface;
use SismaFramework\Core\Interfaces\Models\UserModelInterface;
use SismaFramework\Core\Interfaces\Wrappers\MultiFactorWrapperInterface;

class Authentication
{

    use \SismaFramework\Core\Traits\Submitted;

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
        $this->requestType = RequestType::from($request->server['REQUEST_METHOD']);
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
