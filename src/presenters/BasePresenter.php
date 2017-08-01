<?php
namespace App\AdminModule\Presenters;

use App\AdminModule\Components\Authorizator;
use Nette;

/**
 * Description of BasePresenter
 *
 * @author Vsek
 */
abstract class BasePresenterM extends BasePresenter{
    
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
    
    /** @var \App\Model\Module\Language @inject */
    public $languages;
    
    /**
     * Moduly zobrazujici se v levem menu
     * @var array
     */
    protected $menuModules;
    
    /**
     * Jazyk webu
     * @persistent
     */
    public $webLanguage = 1;
    
    public function formatLayoutTemplateFiles(){
        $list = parent::formatLayoutTemplateFiles();
        $list[] = dirname(__FILE__) . '/../templates/@layout.latte';
        return $list;
    }
    
    public function startup() {
        parent::startup();

        if($this->getName() != 'Admin:Sign' && !$this->user->isLoggedIn()){
            $this->redirect('Sign:default');
        }
        //nastavim prava
        $role = $this->roles->where('NOT system_name', 'super_admin')->limit(1)->fetch();
        if(!$this->acl->hasRole($role['system_name'])){
            foreach ($this->roles->where('NOT system_name', 'super_admin') as $role) {
                $this->acl->addRole($role['system_name']);
            }
            foreach ($this->resources->getAll() as $resource) {
                $this->acl->addResource($resource['system_name']);
            }
            foreach ($this->permissions->getAll() as $permission) {
                $this->acl->allow($permission->role->system_name, $permission->resource->system_name, $permission->privilege->system_name);
            }
        }
        $this->acl->addRole('super_admin');
        $this->acl->allow('super_admin');
        
        //homepage a sign maji pristup vsichni
        $this->acl->addResource('homepage');
        $this->acl->allow(Authorizator::ALL, 'homepage');
        $this->acl->addResource('sign');
        $this->acl->allow(Authorizator::ALL, 'sign');
        
        //vychozi role
        $this->acl->addRole('guest');

        //kontrola prav
        if($this->getName() != 'Admin:Image' && $this->getAction() != 'ordering' && $this->getAction() != 'orderingCategory' && $this->getAction() != 'deleteImage' && $this->getAction() != 'changePassword' && $this->getAction() != 'getCity' && $this->getAction() != 'download'){
            if(!$this->getUser()->isAllowed($this->getNameSimple(), $this->getAction())){
                $this->flashMessage($this->translator->translate('admin.login.noAccess'), 'error');
                $this->redirect('Homepage:default');
            }
        }
        
        //projedu vsek moduly a pokusim se najit presentery
        $presenters = array();
        $vsekDir = dirname(__FILE__) . '/../../../';
        $ch = opendir($vsekDir);
        while (($file = readdir($ch)) !== false) {
            if(!in_array($file, array('.', '..'))){
                if(file_exists($vsekDir . $file . '/src/setting.xml')){
                    $xml = simplexml_load_file($vsekDir . $file . '/src/setting.xml');
                    if(isset($xml->presenter)){
                        $this->menuModules[] = array('name' => (string)$xml->presenter->name, 'resource' => (string)$xml->presenter->resource);
                    }
                }
            }
        }
        closedir($ch);
    }
    
    public function beforeRender() {
        parent::beforeRender();
        $this->template->setTranslator($this->translator);
        $this->template->menuPresenters = $this->menuModules;
        $this->template->languages = $this->languages->getAll();
        $this->template->webLanguage = $this->webLanguage;
    }
    
    public function getNameSimple(){
        $name = str_replace('Admin:', '', $this->getName());
        return \Nette\Utils\Strings::lower(\Nette\Utils\Strings::substring($name, 0, 1)) . \Nette\Utils\Strings::substring($name, 1);
    }
}
