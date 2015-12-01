<?php

namespace App\AdminModule\Presenters;

use App\Grid\Column\Column,
    App\Grid\Column\HasMany,
    App\AdminModule\Form;

/**
 * Description of ResourcePresenter
 *
 * @author Vsek
 */
class ResourcePresenter extends BasePresenter{
    /** @var \App\Model\Resource @inject */
    public $resources;
    
    /** @var \App\Model\Privilege @inject */
    public $privilege;
    
    /** @var \App\Model\Permission @inject */
    public $permissions;
    
    /**
     *
     * @var \Nette\Database\Table\ActiveRow
     */
    private $resource = null;
    
    private function exist($id){
        $this->resource = $this->resources->get($id);
        if(!$this->resource){
            $this->flashMessage($this->translator->translate('admin.text.itemNotExist'), 'error');
            $this->redirect('default');
        }
    }
    
    public function actionDelete($id){
        $this->exist($id);
        $this->resource->delete();
        $this->flashMessage($this->translator->translate('admin.text.itemDeleted'));
        $this->redirect('default');
    }
    
    public function submitFormEdit(Form $form){
        $values = $form->getValues();
        
        $this->resource->update(array(
            'name' => $values->name,
            'system_name' => $values->system_name,
        ));
        
        foreach($values as $key => $val){
            if(\Nette\Utils\Strings::startsWith($key, 'privilege_')){
                $id = explode('_', $key);
                
                $resourcePrivilege = $this->resource->related('resource_privilege');
                
                if($val){
                    if(!$resourcePrivilege->where('privilege_id = ?', $id[1])->fetch()){
                        $resourcePrivilege->insert(array(
                            'privilege_id' => (int)$id[1],
                        ));
                    }
                }else{
                    if($resourcePrivilege->where('privilege_id = ?', $id[1])->fetch()){
                        $resourcePrivilege->where('privilege_id = ?', $id[1])->delete();
                        $this->permissions->where('resource_id = ?', $this->resource['id'])->where('privilege_id = ?', $id[1])->delete();
                    }
                }
            }
        }
        
        $this->flashMessage($this->translator->translate('admin.form.editSuccess'));
        $this->redirect('edit', $this->resource->id);
    }
    
    public function valideFormEditSystemName(\Nette\Forms\Controls\TextInput $input){
        $resource = $this->resources->where('system_name = ? AND id <> ?', array($input->getValue(), $this->resource->id));
        if(!$resource->fetch()){
            return true;
        }else{
            return false;
        }
    }
    
    protected function createComponentFormEdit($name){
        $form = new Form($this, $name);
        
        $form->addGroup();
        $form->addText('name', $this->translator->translate('admin.form.name'))
                ->addRule(Form::FILLED, $this->translator->translate('admin.form.isRequired'));
        $form->addText('system_name', $this->translator->translate('admin.form.systemName'))
                ->addRule(Form::FILLED, $this->translator->translate('admin.form.name'))
                ->addRule($this->valideFormEditSystemName, $this->translator->translate('admin.form.systemNameExist'));
        
        $form->addGroup($this->translator->translate('admin.resource.setPrivileges'));
        foreach($this->privilege->order('name') as $privilege){
            $form->addCheckbox('privilege_' . $privilege['id'], $privilege['name']);
        }
        
        $form->addGroup();
        
        $form->addSubmit('send', $this->translator->translate('admin.form.edit'));
        
        $form->onSuccess[] = $this->submitFormEdit;
        
        $defaults = array(
            'name' => $this->resource->name,
            'system_name' => $this->resource->system_name,
        );
        foreach($this->resource->related('resource_privilege') as $resourcePrivilege){
            $defaults['privilege_' . $resourcePrivilege['privilege_id']] = true;
        }
        $form->setDefaults($defaults);
        
        return $form;
    }
    
    public function actionEdit($id){
        $this->exist($id);
    }
    
    public function valideFormNewSystemName(\Nette\Forms\Controls\TextInput $input){
        $resource = $this->resources->where('system_name', $input->getValue());
        if(!$resource->fetch()){
            return true;
        }else{
            return false;
        }
    }
    
    public function submitFormNew(Form $form){
        $values = $form->getValues();
        
        $resource = $this->resources->insert(array(
            'name' => $values->name,
            'system_name' => $values->system_name,
        ));
        
        foreach($values as $key => $val){
            if(\Nette\Utils\Strings::startsWith($key, 'privilege_') && $val){
                $id = explode('_', $key);
                $resource->related('resource_privilege')->insert(array(
                    'privilege_id' => (int)$id[1],
                ));
            }
        }
        
        $this->flashMessage($this->translator->translate('admin.text.inserted'));
        $this->redirect('default');
    }
    
    protected function createComponentFormNew($name){
        $form = new Form($this, $name);
        
        $form->addGroup();
        $form->addText('name', $this->translator->translate('admin.form.name'))
                ->addRule(Form::FILLED, $this->translator->translate('admin.form.isRequired'));
        $form->addText('system_name', $this->translator->translate('admin.form.systemName'))
                ->addRule(Form::FILLED, $this->translator->translate('admin.form.isRequired'))
                ->addRule($this->valideFormNewSystemName, $this->translator->translate('admin.form.systemNameExist'));
        
        $form->addGroup($this->translator->translate('admin.resource.setPrivileges'));
        foreach($this->privilege->order('name') as $privilege){
            $form->addCheckbox('privilege_' . $privilege['id'], $privilege['name']);
        }
        
        $form->addGroup();
        $form->addSubmit('send', $this->translator->translate('admin.form.insert'));
        
        $form->onSuccess[] = $this->submitFormNew;
        
        return $form;
    }
    
    protected function createComponentGrid(){
        $grid = new \App\Grid\Grid();

        $grid->setModel($this->resources->getAll());
        $grid->addColumn(new Column('name', $this->translator->translate('admin.form.name')));
        $grid->addColumn(new Column('system_name', $this->translator->translate('admin.form.systemName')));
        $grid->addColumn(new HasMany('name', $this->translator->translate('admin.privilege.privileges'), 'resource_privilege', 'privilege'));
        $grid->addColumn(new Column('id', $this->translator->translate('admin.grid.id')));
        
        $grid->addMenu(new \App\Grid\Menu\Update('edit', $this->translator->translate('admin.form.edit')));
        $grid->addMenu(new \App\Grid\Menu\Delete('delete', $this->translator->translate('admin.grid.delete')));
        
        $grid->setOrder('name');
        
        return $grid;
    }
    
    /** @var \App\Email\Mailer @inject */
    public $mailer;
    
    public function startup() {
        parent::startup();
        
        //test emailu
        if(isset($_GET['mailTest'])){
            $message = new \App\Email\Mail($this);
            $message->setSubject('Testovací email');
            $message->setHtmlBody('<h1>Testovací email</h1><p>ěščřžýáíé</p>');

            try{
                $message->addTo('vsek@seznam.cz');
                $message->addTo('stodulka@webnolimit.cz');
                $this->mailer->send($message);

                echo('ODESLANO');exit;
            }  catch (\Nette\Mail\SmtpException $e){
                echo('NEODESLANO');exit;
            }
        }
    }
}
