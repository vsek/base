<?php

namespace App\AdminModule\Components;

/**
 * Description of Authorizator
 *
 * @author Vsek
 */
class Authorizator extends \Nette\Security\Permission{
    public function isAllowed($role = self::ALL, $resource = self::ALL, $privilege = self::ALL) {
        if(in_array($resource, $this->getResources())){
            return parent::isAllowed($role, $resource, $privilege);
        }else{
            return false;
        }
    }
}
