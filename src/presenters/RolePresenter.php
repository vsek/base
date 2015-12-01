<?php

namespace App\AdminModule\Presenters;

use App\Grid\Column\Column,
    \App\Grid\Column\HasManyPermission,
    App\AdminModule\Form;

/**
 * Description of RolePresenter
 *
 * @author Vsek
 */
class RolePresenter extends BasePresenter{
    /** @var \App\Model\Role @inject */
    public $model;
    
    /** @var \App\Model\Resource @inject */
    public $modelResource;
    
    /** @var \App\Model\Permission @inject */
    public $permissions;
    
    /**
     *
     * @var \Nette\Database\Table\ActiveRow
     */
    private $row = null;
    
    /**
     *
     * @var \Nette\Database\Table\ActiveRow
     */
    private $resource = null;
    
    /**
     *
     * @var int
     * @persistent
     */
    public $roleId = null;
    
     public function submitFormSet(Form $form){
        $values = $form->getValues();
        
        foreach($values as $key => $val){
            if(\Nette\Utils\Strings::startsWith($key, 'privilege_')){
                $id = explode('_', $key);
                if($val){
                    if(!$this->permissions->where('role_id = ?', $this->row['id'])->where('resource_id = ?', $this->resource['id'])->where('privilege_id = ?', $id[1])->fetch()){
                        $this->permissions->insert(array(
                            'role_id' => $this->row['id'],
                            'resource_id' => $this->resource['id'],
                            'privilege_id' => $id[1],
                        ));
                    }
                }else{
                    $this->permissions->where('role_id = ?', $this->row['id'])->where('resource_id = ?', $this->resource['id'])->where('privilege_id = ?', $id[1])->delete();
                }
            }
        }
        
        $this->flashMessage($this->translator->translate('admin.role.privilegeSet'));
        $this->redirect('permission', $this->row->id);
     }
    
    protected function createComponentFormSet($name){
        $form = new Form($this, $name);
        
        foreach($this->resource->related('resource_privilege') as $resourcePrivilege){
            $form->addCheckbox('privilege_' . $resourcePrivilege->privilege->id, $resourcePrivilege->privilege->name);
        }
        
        $form->addSubmit('send', $this->translator->translate('admin.form.set'));
        
        $form->onSuccess[] = $this->submitFormSet;
        
        $defaults = array();
        foreach($this->permissions->where('role_id = ?', $this->row['id'])->where('resource_id = ?', $this->resource['id']) as $permission){
            $defaults['privilege_' . $permission['privilege_id']] = true;
        }
        $form->setDefaults($defaults);
        
        return $form;
    }
    
    private function existResource($id){
        $this->resource = $this->modelResource->get($id);
        if(!$this->resource){
            $this->flashMessage($this->translator->translate('admin.role.resourceNotFound'), 'error');
            $this->redirect('default');
        }
    }
    
    private function exist($id){
        $this->row = $this->model->get($id);
        if(!$this->row){
            $this->flashMessage($this->translator->translate('admin.role.roleNotFound'), 'error');
            $this->redirect('default');
        }
    }
    
    public function actionSet($resourceId){
        $this->existResource($resourceId);
        $this->exist($this->roleId);
        $this->template->resource = $this->resource;
        $this->template->role = $this->row;
    }
    
    protected function createComponentGridResource(){
        $grid = new \App\Grid\Grid();

        $grid->setModel($this->modelResource->getAll());
        $grid->addColumn(new Column('name', $this->translator->translate('admin.form.name')));
        $grid->addColumn(new Column('system_name', $this->translator->translate('admin.form.systemName')));
        $grid->addColumn(new HasManyPermission('name', $this->translator->translate('admin.privilege.privileges'), $this->getParameter('roleId')));
        $grid->addColumn(new Column('id', $this->translator->translate('admin.grid.id')));
        
        $grid->addMenu(new \App\Grid\Menu\Update('set', $this->translator->translate('admin.form.set')));
        
        $grid->setOrder('name');
        
        return $grid;
    }
    
    public function actionPermission($id){
        $this->exist($id);
        $this->roleId = $this->row['id'];
        $this->template->role = $this->row;
    }
    
    public function actionDelete($id){
        $this->exist($id);
        $this->row->delete();
        $this->flashMessage($this->translator->translate('admin.text.itemDeleted'));
        $this->redirect('default');
    }
    
    public function submitFormEdit(Form $form){
        $values = $form->getValues();
        
        $this->row->update(array(
            'name' => $values->name,
            'system_name' => $values->system_name,
        ));
        
        $this->flashMessage($this->translator->translate('admin.form.editSuccess'));
        $this->redirect('edit', $this->row->id);
    }
    
    public function valideFormEditSystemName(\Nette\Forms\Controls\TextInput $input){
        $resource = $this->model->where('system_name = ? AND id <> ?', array($input->getValue(), $this->row->id));
        if(!$resource->fetch()){
            return true;
        }else{
            return false;
        }
    }
    
    protected function createComponentFormEdit($name){
        $form = new Form($this, $name);
        
        $form->addText('name', $this->translator->translate('admin.form.name'))
                ->addRule(Form::FILLED, $this->translator->translate('admin.form.isRequired'));
        $form->addText('system_name', $this->translator->translate('admin.form.systemName'))
                ->addRule(Form::FILLED, $this->translator->translate('admin.form.isRequired'))
                ->addRule($this->valideFormEditSystemName, $this->translator->translate('admin.form.systemNameExist'));
        
        $form->addSubmit('send', $this->translator->translate('admin.form.edit'));
        
        $form->onSuccess[] = $this->submitFormEdit;
        
        $form->setDefaults(array(
            'name' => $this->row->name,
            'system_name' => $this->row->system_name,
        ));
        
        return $form;
    }
    
    public function actionEdit($id){
        $this->exist($id);
    }
    
    public function valideFormNewSystemName(\Nette\Forms\Controls\TextInput $input){
        $resource = $this->model->where('system_name', $input->getValue());
        if(!$resource->fetch()){
            return true;
        }else{
            return false;
        }
    }
    
    public function submitFormNew(Form $form){
        $values = $form->getValues();
        
        $this->model->insert(array(
            'name' => $values->name,
            'system_name' => $values->system_name,
        ));
        
        $this->flashMessage($this->translator->translate('admin.text.inserted'));
        $this->redirect('default');
    }
    
    protected function createComponentFormNew($name){
        $form = new Form($this, $name);
        
        $form->addText('name', $this->translator->translate('admin.form.name'))
                ->addRule(Form::FILLED, $this->translator->translate('admin.form.isRequired'));
        $form->addText('system_name', $this->translator->translate('admin.form.systemName'))
                ->addRule(Form::FILLED, $this->translator->translate('admin.form.isRequired'))
                ->addRule($this->valideFormNewSystemName, $this->translator->translate('admin.form.systemNameExist'));
        
        $form->addSubmit('send', $this->translator->translate('admin.form.insert'));
        
        $form->onSuccess[] = $this->submitFormNew;
        
        return $form;
    }
    
    protected function createComponentGrid(){
        $grid = new \App\Grid\Grid();

        $grid->setModel($this->model->getAll());
        $grid->addColumn(new Column('name', $this->translator->translate('admin.form.name')));
        $grid->addColumn(new Column('system_name', $this->translator->translate('admin.form.systemName')));
        $grid->addColumn(new Column('id', $this->translator->translate('admin.grid.id')));
        
        $grid->addMenu(new \App\Grid\Menu\Update('edit', $this->translator->translate('admin.form.edit')));
        $grid->addMenu(new \App\Grid\Menu\Menu('permission', $this->translator->translate('admin.role.setPermission')));
        $grid->addMenu(new \App\Grid\Menu\Delete('delete', $this->translator->translate('admin.grid.delete')));
        
        $grid->setOrder('name');
        
        return $grid;
    }
}
