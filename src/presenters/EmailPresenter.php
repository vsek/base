<?php

namespace App\AdminModule\Presenters;

use App\AdminModule\Form,
    App\Grid\Column\Column;
use App\Email\Mail;
use App\Grid\Column\Date;
use App\Grid\Grid;
use App\Grid\GridEmailLog;
use App\Grid\Menu\Delete;
use App\Grid\Menu\JavascriptWindow;
use App\Grid\Menu\Update;
use Tracy\Debugger;

/**
 * Description of EmailPresenter
 *
 * @author Vsek
 */
class EmailPresenterM extends BasePresenterM{
    /** @var \App\Model\Email @inject */
    public $model;

    /**
     *
     * @var \Nette\Database\Table\ActiveRow
     */
    protected $row = null;

    /** @var \App\Model\EmailLog @inject */
    public $emailLogs;

    public function actionLog() {
        $this->template->setFile(dirname(__FILE__) . '/../templates/Email/log.latte');
    }

    public function actionNew() {
        $this->template->setFile(dirname(__FILE__) . '/../templates/Email/new.latte');
    }

    public function actionDefault() {
        $this->template->setFile(dirname(__FILE__) . '/../templates/Email/default.latte');
    }

    public function actionPreview($id){
        $this->exist($id);
        $message = new Mail($this);
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
        $grid = new GridEmailLog($this, $name);

        $grid->setModel($this->emailLogs->getAll());
        $grid->addColumn(new Column('adress', $this->translator->translate('admin.email.address')));
        $grid->addColumn(new Date('created', $this->translator->translate('admin.text.date')));
        $grid->addColumn(new Column('subject', $this->translator->translate('admin.email.subject')));
        $grid->addColumn(new Column('error', $this->translator->translate('admin.text.error')));
        $grid->addColumn(new Column('id', $this->translator->translate('admin.grid.id')));

        $grid->addMenu(new JavascriptWindow('detail', $this->translator->translate('admin.email.detail')));

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
            'text' => $values->text,
            'subject' => $values->subject,
        );
        if($this->getUser()->isInRole('super_admin')){
            $data['system_name'] = $values->system_name;
        }
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
        $form->addCkEditor('text', $this->translator->translate('admin.form.text'));
        $form->addTextArea('modifier', $this->translator->translate('admin.form.modifier'))->setDisabled();

        $form->addSubmit('send', $this->translator->translate('admin.form.edit'));

        $form->onSuccess[] = [$this, 'submitFormEdit'];

        if(!$this->getUser()->isInRole('super_admin')){
            $form['system_name']->setDisabled();
        }

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
        $this->template->setFile(dirname(__FILE__) . '/../templates/Email/edit.latte');
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
            'language_id' => $this->webLanguage,
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
        $form->addCkEditor('text', $this->translator->translate('admin.form.text'));
        $form->addTextArea('modifier', $this->translator->translate('admin.form.modifier'));

        $form->addSubmit('send', $this->translator->translate('admin.form.create'));

        $form->onSuccess[] = [$this, 'submitFormNew'];

        return $form;
    }

    protected function createComponentGrid($name){
        $grid = new Grid($this, $name);

        $grid->setModel($this->model->where('language_id', $this->webLanguage));
        $grid->addColumn(new Column('name', $this->translator->translate('admin.form.name')));
        $grid->addColumn(new Column('subject', $this->translator->translate('admin.email.subject')));
        $grid->addColumn(new Column('system_name', $this->translator->translate('admin.form.systemName')));
        $grid->addColumn(new Column('id', $this->translator->translate('admin.grid.id')));

        $grid->addMenu(new Update('edit', $this->translator->translate('admin.form.edit')));
        $grid->addMenu(new JavascriptWindow('preview', $this->translator->translate('admin.email.preview')));
        $grid->addMenu(new Delete('delete', $this->translator->translate('admin.grid.delete')));

        return $grid;
    }
}