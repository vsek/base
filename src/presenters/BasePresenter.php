<?php
namespace App\AdminModule\Presenters;

use Nette;

/**
 * Description of BasePresenter
 *
 * @author Vsek
 */
abstract class BasePresenter extends \App\Presenters\BasePresenter{
    
    /** @var \App\Model\Role @inject */
    public $roles;
    
    /** @var \App\Model\Resource @inject */
    public $resources;
    
    /** @var \App\Model\Permission @inject */
    public $permissions;
    
    /** @var Nette\Security\Permission @inject */
    public $acl;
    
    /** @var \Nette\Caching\IStorage @inject*/
    public $storage;
    
    public function startup() {
        parent::startup();
        
        if($this->getName() != 'Admin:Sign' && !$this->user->isLoggedIn()){
            $this->redirect('Sign:default');
        }
        //nastavim prava
        foreach($this->roles->getAll() as $role){
            $this->acl->addRole($role['system_name']);
        }
        foreach($this->resources->getAll() as $resource){
            $this->acl->addResource($resource['system_name']);
        }
        foreach($this->permissions->getAll() as $permission){
            $this->acl->allow($permission->role->system_name, $permission->resource->system_name, $permission->privilege->system_name);
        }
        $this->acl->addRole('super_admin');
        $this->acl->allow('super_admin');
        
        //homepage a sign maji pristup vsichni
        $this->acl->addResource('homepage');
        $this->acl->allow(\App\AdminModule\Components\Authorizator::ALL, 'homepage');
        $this->acl->addResource('sign');
        $this->acl->allow(\App\AdminModule\Components\Authorizator::ALL, 'sign');
        
        //vychozi role
        $this->acl->addRole('guest');

        //kontrola prav
        if($this->getName() != 'Admin:Image' && $this->getAction() != 'ordering' && $this->getAction() != 'orderingCategory' && $this->getAction() != 'deleteImage' && $this->getAction() != 'changePassword' && $this->getAction() != 'getCity' && $this->getAction() != 'download'){
            if(!$this->getUser()->isAllowed($this->getNameSimple(), $this->getAction())){
                $this->flashMessage($this->translator->translate('admin.login.noAccess'), 'error');
                $this->redirect('Homepage:default');
            }
        }
    }
    
    public function beforeRender() {
        parent::beforeRender();
        $this->template->setTranslator($this->translator);
    }
    
    public function getNameSimple(){
        $name = str_replace('Admin:', '', $this->getName());
        return \Nette\Utils\Strings::lower(\Nette\Utils\Strings::substring($name, 0, 1)) . \Nette\Utils\Strings::substring($name, 1);
    }
}
