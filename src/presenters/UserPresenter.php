<?php

namespace App\AdminModule\Presenters;

use App\AdminModule\Form,
 App\Grid\Column\HasOne,
 App\Grid\Column\Column;

/**
 * Description of UserPresenter
 *
 * @author Vsek
 */
class UserPresenter extends BasePresenter{
    /** @var \App\Model\User @inject */
    public $model;
    
    /** @var \App\Model\Role @inject */
    public $roles;
    
    /**
     *
     * @var \Nette\Database\Table\ActiveRow
     */
    private $row = null;
    
    public function submitFormChangePassword(Form $form){
        $values = $form->getValues();
        $user = $this->model->get($this->getUser()->getIdentity()->data['id']);
        $user->update(array('password' => md5($values['password'])));
        $this->flashMessage($this->translator->translate('admin.user.passwordChanged'));
        $this->redirect('this');
    }
    
    protected function createComponentFormChangePassword($name){
        $form = new Form($this, $name);
        
        $form->addPassword('password', $this->translator->translate('admin.sign.newPassword'))
                ->addRule(Form::FILLED, $this->translator->translate('admin.form.newPasswordIsRequired'));
        $form->addPassword('password1', $this->translator->translate('admin.form.newPasswordRepeat'))
                ->addRule(Form::FILLED, $this->translator->translate('admin.form.newPasswordRepeatIsRequired'))
                ->addRule(Form::EQUAL, $this->translator->translate('admin.form.bothPasswordMustBeSame'), $form['password']);
        $form->addSubmit('send', $this->translator->translate('admin.form.change'));
        
        $form->onSuccess[] = $this->submitFormChangePassword;
                    
        
        return $form;
    }
    
    public function submitFormEdit(Form $form){
        $values = $form->getValues();
        
        $data = array(
            'name' => $values->name == '' ? null : $values->name,
            'surname' => $values->surname == '' ? null : $values->surname,
            'email' => $values->email,
            'role_id' => $values->role_id,
        );
        $this->row->update($data);
        
        $this->flashMessage($this->translator->translate('admin.form.editSuccess'));
        $this->redirect('edit', $this->row->id);
    }
    
    private function exist($id){
        $this->row = $this->model->get($id);
        if(!$this->row){
            $this->flashMessage($this->translator->translate('admin.text.itemNotExist'), 'error');
            $this->redirect('default');
        }
    }
    
    protected function createComponentFormEdit($name){
        $form = new Form($this, $name);
        
        $form->addGroup();
        if($this->getUser()->isInRole('super_admin')){
            $roles = $this->roles->order('name')->fetchPairs('id', 'name');
        }else{
            $roles = $this->roles->order('name')->where('NOT system_name', 'super_admin')->fetchPairs('id', 'name');
        }
        $form->addSelect('role_id', $this->translator->translate('admin.user.role'), $roles);
        
        $form->addText('name', $this->translator->translate('admin.user.name'))
                    ->addRule(Form::FILLED, $this->translator->translate('admin.form.isRequired'));
        $form->addText('surname', $this->translator->translate('admin.user.surname'))
                    ->addRule(Form::FILLED, $this->translator->translate('admin.form.isRequired'));
        $form->addText('email', $this->translator->translate('admin.form.email'))
                ->addRule(Form::FILLED, $this->translator->translate('admin.form.isRequired'))
                ->addRule(Form::EMAIL, $this->translator->translate('admin.form.mustBeValidEmail'));
        
        $form->addGroup();
        $form->addSubmit('send', $this->translator->translate('admin.form.edit'));
        
        $form->onSuccess[] = $this->submitFormEdit;
        
        $form->setDefaults(array(
            'name' => $this->row->name,
            'surname' => $this->row->surname,
            'email' => $this->row->email,
            'role_id' => $this->row->role_id,
        ));
        
        return $form;
    }
    
    public function actionEdit($id){
        $this->exist($id);
    }
    
    public function actionDelete($id){
        $this->exist($id);
        $this->row->delete();
        $this->flashMessage($this->translator->translate('admin.text.itemDeleted'));
        $this->redirect('default');
    }
    
    public function submitFormNew(Form $form){
        $values = $form->getValues();
        
        $this->model->insert(array(
            'name' => $values->name == '' ? null : $values->name,
            'surname' => $values->surname == '' ? null : $values->surname,
            'email' => $values->email,
            'password' => md5($values->password),
            'created' => new \DateTime,
            'role_id' => $values->role_id,
        ));
        
        $this->flashMessage($this->translator->translate('admin.text.inserted'));
        $this->redirect('default');
    }
    
    protected function createComponentFormNew($name){
        $form = new Form($this, $name);
        
        if($this->getUser()->isInRole('super_admin')){
            $roles = $this->roles->order('name')->fetchPairs('id', 'name');
        }else{
            $roles = $this->roles->order('name')->where('NOT system_name', 'super_admin')->fetchPairs('id', 'name');
        }
        
        $form->addSelect('role_id', $this->translator->translate('admin.user.role'), $roles);        
        $form->addText('name', $this->translator->translate('admin.user.name'))
                    ->addRule(Form::FILLED, $this->translator->translate('admin.form.isRequired'));
        $form->addText('surname', $this->translator->translate('admin.user.surname'))
                    ->addRule(Form::FILLED, $this->translator->translate('admin.form.isRequired'));
        $form->addText('email', $this->translator->translate('admin.form.email'))
                ->addRule(Form::FILLED, $this->translator->translate('admin.form.isRequired'))
                ->addRule(Form::EMAIL, $this->translator->translate('admin.form.mustBeValidEmail'));
        $form->addPassword('password', $this->translator->translate('admin.form.password'))
                ->addRule(Form::FILLED, $this->translator->translate('admin.form.isRequired'));
        $form->addPassword('password1', $this->translator->translate('admin.form.passwordRepead'))
                ->addRule(Form::FILLED, $this->translator->translate('admin.form.isRequired'))
                ->addRule(Form::EQUAL, $this->translator->translate('admin.form.passwordMustBeSame'), $form['password']);
        
        $form->addSubmit('send', $this->translator->translate('admin.form.create'));
        
        $form->onSuccess[] = $this->submitFormNew;
        
        return $form;
    }
    
    protected function createComponentGrid(){
        $grid = new \App\Grid\Grid();

        $query = $this->model->getAll();
        if(!$this->getUser()->isInRole('super_admin')){
            $query->where('NOT role_id', 2);
        }
        
        $grid->setModel($query);
        $grid->addColumn(new Column('email', $this->translator->translate('admin.form.email')));
        $grid->addColumn(new Column('name', $this->translator->translate('admin.user.name')));
        $grid->addColumn(new Column('surname', $this->translator->translate('admin.user.surname')));
        $grid->addColumn(new HasOne('name', $this->translator->translate('admin.user.role'), 'role'));
        $grid->addColumn(new Column('id', $this->translator->translate('admin.grid.id')));
        
        $grid->addMenu(new \App\Grid\Menu\Update('edit', $this->translator->translate('admin.form.edit')));
        $grid->addMenu(new \App\Grid\Menu\Delete('delete', $this->translator->translate('admin.grid.delete')));
        
        $grid->setOrder('role_id, name');
        
        return $grid;
    }
}
