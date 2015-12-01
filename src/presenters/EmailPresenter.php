<?php

namespace App\AdminModule\Presenters;

use App\AdminModule\Form,
 App\Grid\Column\Column;

/**
 * Description of EmailPresenter
 *
 * @author Vsek
 */
class EmailPresenter extends BasePresenter{
    /** @var \App\Model\Email @inject */
    public $model;
    
    /**
     *
     * @var \Nette\Database\Table\ActiveRow
     */
    private $row = null;
    
    /** @var \App\Model\EmailLog @inject */
    public $emailLogs;
    
    public function actionPreview($id){
        $this->exist($id);
        $message = new \App\Mail($this);
        $message->setHtmlBody($this->row['text']);
        echo $message->getText();
        $this->terminate();
    }
    
    public function actionDetail($id){
        $email = $this->emailLogs->get($id);
        if(!$email){
            $this->flashMessage($this->translator->translate('admin.email.notExist'), 'error');
            $this->redirect('log');
        }
        echo $email['text'];
        $this->terminate();
    }
    
    protected function createComponentGridLog($name){
        $grid = new \App\Grid\GridEmailLog($this, $name);
        
        $grid->setModel($this->emailLogs->getAll());
        $grid->addColumn(new Column('adress', $this->translator->translate('admin.email.address')));
        $grid->addColumn(new \App\Grid\Column\Date('created', $this->translator->translate('admin.text.date')));
        $grid->addColumn(new Column('subject', $this->translator->translate('admin.email.subject')));
        $grid->addColumn(new Column('error', $this->translator->translate('admin.text.error')));
        $grid->addColumn(new Column('id', $this->translator->translate('admin.grid.id')));
        
        $grid->addMenu(new \App\Grid\Menu\JavascriptWindow('detail', $this->translator->translate('admin.email.detail')));
        
        $grid->setTemplateDir(dirname(__FILE__) . '/../templates/Email');
        $grid->setTemplateFile('gridLog.latte');
        
        $grid->setOrder('created');
        $grid->setOrderDir('DESC');
        
        return $grid;
    }
    
    public function submitFormEdit(Form $form){
        $values = $form->getValues();
       
        $data = array(
            'name' => $values->name,
            'system_name' => $values->system_name,
            'text' => $values->text,
            'subject' => $values->subject,
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
        
        $form->addText('name', $this->translator->translate('admin.form.name'))
                ->addRule(Form::FILLED, $this->translator->translate('admin.form.isRequired'));
        $form->addText('system_name', $this->translator->translate('admin.form.systemName'))
                ->addRule(Form::FILLED, $this->translator->translate('admin.form.isRequired'));
        $form->addTextArea('subject', $this->translator->translate('admin.email.subject'))
                ->addRule(Form::FILLED, $this->translator->translate('admin.form.isRequired'));
        $form->addSpawEditor('text', $this->translator->translate('admin.form.text'));
        $form->addTextArea('modifier', $this->translator->translate('admin.form.modifier'))->setDisabled();
        
        $form->addSubmit('send', $this->translator->translate('admin.form.edit'));
        
        $form->onSuccess[] = $this->submitFormEdit;
        
        $form->setDefaults(array(
            'name' => $this->row->name,
            'text' => $this->row->text,
            'system_name' => $this->row->system_name,
            'subject' => $this->row->subject,
            'modifier' => $this->row->modifier,
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
        
        $challenge = $this->model->insert(array(
            'name' => $values->name,
            'system_name' => $values->system_name,
            'text' => $values->text,
            'modifier' => $values->modifier == '' ? null : $values->modifier,
            'subject' => $values->subject,
        ));
        
        $this->flashMessage($this->translator->translate('admin.text.inserted'));
        $this->redirect('default');
    }
    
    protected function createComponentFormNew($name){
        $form = new Form($this, $name);
        
        $form->addText('name', $this->translator->translate('admin.form.name'))
                ->addRule(Form::FILLED, $this->translator->translate('admin.form.isRequired'));
        $form->addText('system_name', $this->translator->translate('admin.form.systemName'))
                ->addRule(Form::FILLED, $this->translator->translate('admin.form.isRequired'));
        $form->addTextArea('subject', $this->translator->translate('admin.email.subject'))
                ->addRule(Form::FILLED, $this->translator->translate('admin.form.isRequired'));
        $form->addSpawEditor('text', $this->translator->translate('admin.form.text'));
        $form->addTextArea('modifier', $this->translator->translate('admin.form.modifier'));
        
        $form->addSubmit('send', $this->translator->translate('admin.form.create'));
        
        $form->onSuccess[] = $this->submitFormNew;
        
        return $form;
    }
    
    protected function createComponentGrid(){
        $grid = new \App\Grid\Grid();
        
        $grid->setModel($this->model->getAll());
        $grid->addColumn(new Column('name', $this->translator->translate('admin.form.name')));
        $grid->addColumn(new Column('subject', $this->translator->translate('admin.email.subject')));
        $grid->addColumn(new Column('system_name', $this->translator->translate('admin.form.systemName')));
        $grid->addColumn(new Column('id', $this->translator->translate('admin.grid.id')));
        
        $grid->addMenu(new \App\Grid\Menu\Update('edit', $this->translator->translate('admin.form.edit')));
        $grid->addMenu(new \App\Grid\Menu\JavascriptWindow('preview', $this->translator->translate('admin.email.preview')));
        $grid->addMenu(new \App\Grid\Menu\Delete('delete', $this->translator->translate('admin.grid.delete')));
        
        return $grid;
    }
}