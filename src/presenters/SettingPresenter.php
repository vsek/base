<?php

namespace App\AdminModule\PresentersM;

use App\AdminModule\Form;

/**
 * Description of SettingPresenter
 *
 * @author Vsek
 */
class SettingPresenterM extends \App\AdminModule\Presenters\BasePresenterM{
    /** @var \App\Model\Setting @inject */
    public $model;
    
    /**
     *
     * @var \Nette\Database\Table\ActiveRow
     */
    protected $row = null;
    
    public function startup() {
        parent::startup();
        
        $this->template->setFile(dirname(__FILE__) . '/../templates/Setting/default.latte');
    }
    
    protected function createComponentFormEdit($name){
        $form = new Form($this, $name);

        $defaults = array(
            'name' => $this->row['name'],
            'email' => $this->row['email'],
            'google_analytics' => $this->row['google_analytics'],
            'facebook_link' => $this->row['facebook_link'],
            'twitter_link' => $this->row['twitter_link'],
        );
        
        $form->addGroup();
        $form->addText('name', $this->translator->translate('admin.setting.webName'))
                    ->addCondition(Form::FILLED, $this->translator->translate('admin.form.isRequired'));
        $form->addText('email', $this->translator->translate('admin.setting.mainEmail'))
                    ->addCondition(Form::FILLED, $this->translator->translate('admin.form.isRequired'))
                    ->addCondition(Form::EMAIL, $this->translator->translate('admin.form.mustBeValidEmail'));
        $form->addTextArea('google_analytics', $this->translator->translate('admin.setting.googleAnalytics'));
        
        $form->addText('facebook_link', $this->translator->translate('admin.setting.linkFacebook'));
        $form->addText('twitter_link', $this->translator->translate('admin.setting.linkTwitter'));
        
        $form->addGroup();
        $form->addSubmit('send', $this->translator->translate('admin.form.edit'));
        
        $form->onSuccess[] = $this->submitFormEdit;
        
        $form->setDefaults($defaults);
        
        return $form;
    }
    
    public function submitFormEdit(Form $form){
        $values = $form->getValues();
        
        $data = array(
            'name' => $values['name'],
            'email' => $values['email'],
            'google_analytics' => $values['google_analytics'] == '' ? NULL : $values['google_analytics'],
            'facebook_link' => $values['facebook_link'] == '' ? NULL : $values['facebook_link'],
            'twitter_link' => $values['twitter_link'] == '' ? NULL : $values['twitter_link'],
        );
        $this->row->update($data);
        
        $this->flashMessage($this->translator->translate('admin.form.editSuccess'));
        $this->redirect('this');
    }
    
    public function actionDefault(){
        $this->row = $this->model->where('language_id', $this->webLanguage)->fetch();
    }
}