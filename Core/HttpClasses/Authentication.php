<?php

namespace Sisma\Core\HttpClasses;

use Sisma\Core\Enumerators\RequestType;
use Sisma\Core\HelperClasses\Encryptor;
use Sisma\Core\HelperClasses\Filter;
use Sisma\Core\HelperClasses\Session;
use Sisma\Core\HttpClasses\Request;
use Sisma\Core\Interfaces\Entities\PasswordInterface;
use Sisma\Core\Interfaces\Entities\UserInterface;
use Sisma\Core\Interfaces\Models\PasswordModelInterface;
use Sisma\Core\Interfaces\Models\UserModelInterface;

class Authentication
{
    use \Sisma\Core\Traits\Submitted;

    private Request $request;
    private PasswordModelInterface $passwordModelInterface;
    private UserModelInterface $userModelInterface;
    private array $filterErrors = [
        "usernameError" => false,
        "passwordError" => false,
    ];

    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->requestType = new RequestType($request->server['REQUEST_METHOD']);
    }
    
    public function setUserModel(UserModelInterface $userModelInterface)
    {
        $this->userModelInterface = $userModelInterface;
    }
    
    public function setPasswordModel(PasswordModelInterface $passwordModelInterface)
    {
        $this->passwordModelInterface = $passwordModelInterface;
    }

    public function connectUser()
    {
        if (Filter::isString($this->request->request['username'])) {
            $userInterface = $this->userModelInterface->getEntityByUsername($this->request->request['username']);
            if (($userInterface instanceof UserInterface) && $this->testPassword($userInterface, 'password')) {
                $classNameParts = explode('\\', get_class($userInterface));
                $className = strtolower(end($classNameParts));
                Session::setItem($className.'.id', $userInterface->id);
                Session::setItem($className.'.username', $userInterface->username);
                return true;
            }else{
                $this->filterErrors['passwordError'] = true;
            }
        }
        $this->filterErrors['usernameError'] = true;
        return false;
    }

    public function testPassword(UserInterface $userInterface, string $passwordFieldName)
    {
        if (Filter::isString($this->request->request[$passwordFieldName])) {
            $passwordEntity = $this->passwordModelInterface->getLastPasswordByUserInterface($userInterface);
            if (Encryptor::verifyBlowfishHash($this->request->request[$passwordFieldName], $passwordEntity->password)) {
                return true;
            }
        }
        return false;
    }

}
