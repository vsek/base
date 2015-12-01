<?php

namespace App\AdminModule\Presenters;

use App\Grid\Column\Column,
    App\AdminModule\Form;

/**
 * Description of PrivilegePresenter
 *
 * @author Vsek
 */
class PrivilegePresenter extends BasePresenter{
    
    /** @var \App\Model\Privilege @inject */
    public $privileges;
    
    /**
     *
     * @var \Nette\Database\Table\ActiveRow
     */
    private $privilege = null;
    
    private function exist($id){
        $this->privilege = $this->privileges->get($id);
        if(!$this->privilege){
            $this->flashMessage($this->translator->translate('admin.privilege.thisPrivilegeNotExist'), 'error');
            $this->redirect('default');
        }
    }
    
    public function actionDelete($id){
        $this->exist($id);
        $this->privilege->delete();
        $this->flashMessage($this->translator->translate('admin.privilege.privilegeDelete'));
        $this->redirect('default');
    }
    
    public function submitFormEdit(Form $form){
        $values = $form->getValues();
        
        $this->privilege->update(array(
            'name' => $values->name,
            'system_name' => $values->system_name,
        ));
        
        $this->flashMessage($this->translator->translate('admin.form.editSuccess'));
        $this->redirect('edit', $this->privilege->id);
    }
    
    public function valideFormEditSystemName(\Nette\Forms\Controls\TextInput $input){
        $privilege = $this->privileges->where('system_name = ? AND id <> ?', array($input->getValue(), $this->privilege->id));
        if(!$privilege->fetch()){
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
            'name' => $this->privilege->name,
            'system_name' => $this->privilege->system_name,
        ));
        
        return $form;
    }
    
    public function actionEdit($id){
        $this->exist($id);
    }
    
    public function valideFormNewSystemName(\Nette\Forms\Controls\TextInput $input){
        $privilege = $this->privileges->where('system_name', $input->getValue());
        if(!$privilege->fetch()){
            return true;
        }else{
            return false;
        }
    }
    
    public function submitFormNew(Form $form){
        $values = $form->getValues();
        
        $this->privileges->insert(array(
            'name' => $values->name,
            'system_name' => $values->system_name,
        ));
        
        $this->flashMessage($this->translator->translate('admin.privilege.privilegeInserted'));
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

        $grid->setModel($this->privileges->getAll());
        $grid->addColumn(new Column('name', $this->translator->translate('admin.form.name')));
        $grid->addColumn(new Column('system_name', $this->translator->translate('admin.form.systemName')));
        $grid->addColumn(new Column('id', $this->translator->translate('admin.grid.id')));
        
        $grid->addMenu(new \App\Grid\Menu\Update('edit', $this->translator->translate('admin.form.edit')));
        $grid->addMenu(new \App\Grid\Menu\Delete('delete', $this->translator->translate('admin.grid.delete')));
        
        $grid->setOrder('name');
        
        return $grid;
    }
}
