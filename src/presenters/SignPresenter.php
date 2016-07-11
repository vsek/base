<?php
namespace App\AdminModule\Presenters;

use Nette\Application\UI\Form,
 Nette\Security\AuthenticationException;

/**
 * Description of SignPresenter
 *
 * @author Vsek
 */
class SignPresenter extends BasePresenterM{
    
    /** @var \App\Model\User @inject */
    public $user;
    
    /** @var \App\Email\Mailer @inject */
    public $mailer;
    
    /**
     *
     * @var \Nette\Database\Table\ActiveRow
     */
    private $userRow = null;
    
    public function submitFormGeneratePassword(Form $form){
        $values = $form->getValues();
        $this->userRow->update(array('password' => md5($values['password']), 'hash' => NULL));
        $this->flashMessage($this->translator->translate('admin.sign.passwordChanged'));
        $this->redirect('default');
    }
    
    public function renderGeneratePassword(){
        $this->template->formGeneratePassword = $this->getComponent('formGeneratePassword');
    }
    
    protected function createComponentFormGeneratePassword($name){
        $form = new Form($this, $name);
        
        $form->addPassword('password', $this->translator->translate('admin.form.password'))
                ->addRule(Form::FILLED, $this->translator->translate('admin.form.isRequired'));
        $form->addPassword('password1', $this->translator->translate('admin.form.passwordRepead'))
                ->addRule(Form::FILLED, $this->translator->translate('admin.form.isRequired'))
                ->addRule(Form::EQUAL, $this->translator->translate('admin.form.passwordMustBeSame'), $form['password']);
                    
        
        $form->addSubmit('send', $this->translator->translate('admin.form.createPassword'));
        
        $form->onSubmit[] = [$this, 'submitFormGeneratePassword'];
        
        return $form;
    }
    
    public function actionGeneratePassword($hash){
        $this->userRow = $this->user->where('hash', $hash)->fetch();
        if(!$this->userRow){
            $this->flashMessage($this->translator->translate('admin.sign.badHash'), 'error');
            $this->redirect('forgotPassword');
        }
    }
    
    public function submitFormForgotPassword(Form $form){
        $values = $form->getValues();
        
        $user = $this->user->where('email', $values['email'])->fetch();
        if(!$user){
            $this->flashMessage($this->translator->translate('admin.sign.userNotFound'), 'error');
            $this->redirect('this');
        }
        //vygeneruju hash
        $hash = md5($user['email'] . rand());
        while($this->user->where('hash', $hash)->fetch()){
            $hash = md5($user->email . rand());
        }
        $this->user->where('email', $values['email'])->update(array('hash' => $hash));

        //odeslu email
        $template = $this->createTemplate();
        $template->setFile(dirname(__FILE__) . '/../templates/Sign/emailForgotPassword.latte');
        $template->setTranslator($this->translator);
        $template->host = $this->getHttpRequest()->getUrl()->getHost();
        $template->hash = $hash;
        $template->link = $this->link('//generatePassword', array('hash' => $hash));

        $message = new \App\Email\Mail($this);
        $message->addTo($user['email'], $user['name'] . ' ' . $user['surname']);
        $message->setSubject($this->translator->translate('admin.email.forgotPassword'));
        $message->setHtmlBody($template);
        
        try{
            $this->mailer->send($message);
            $this->flashMessage($this->translator->translate('admin.email.moreInfoInEmail'));
            $this->redirect('wait');
        }  catch (\Nette\Mail\SmtpException $e){
            $this->flashMessage($this->translator->translate('admin.email.notSend'), 'error');
        }
                
    }
    
    protected function createComponentFormForgotPassword($name){
        $form = new Form($this, $name);
        
        $form->addText('email', $this->translator->translate('admin.form.email'))
                ->addRule(Form::FILLED, $this->translator->translate('admin.form.isRequired'))
                ->addRule(Form::EMAIL, $this->translator->translate('admin.form.mustBeValidEmail'))
                ->setAttribute('placeholder', $this->translator->translate('admin.form.email'));
        
        $form->addSubmit('send', $this->translator->translate('admin.form.sendPassword'));
        
        $form->onSubmit[] = [$this, 'submitFormForgotPassword'];
        
        return $form;
    }
    
    public function renderForgotPassword(){
        $this->template->formForgotPassword = $this->getComponent('formForgotPassword');
    }
    
    public function renderDefault(){
        $this->template->formLogin = $this->getComponent('formLogin');
    }
    
    public function actionLogout(){
        $this->getUser()->logout(true);
        $this->redirect('default');
    }
    
    public function submitFormLogin(Form $form){
        $values = $form->getValues();
        try{
            $this->getUser()->login($values['email'], $values['password']);
            
            $this->redirect('Homepage:default');
        }  catch (AuthenticationException $e){
            $this->flashMessage($this->translator->translate('admin.sign.badLogin'), 'error');
        }
    }
    
    protected function createComponentFormLogin($name){
        $form = new Form($this, $name);
        
        $form->addText('email', $this->translator->translate('admin.form.email'))
                ->addRule(Form::FILLED, $this->translator->translate('admin.form.isRequired'))
                ->addRule(Form::EMAIL, $this->translator->translate('admin.form.mustBeValidEmail'))
                ->setAttribute('placeholder', $this->translator->translate('admin.form.email'));
        $form->addPassword('password', $this->translator->translate('admin.form.password'))
                ->addRule(Form::FILLED, $this->translator->translate('admin.form.isRequired'));
        
        $form->addSubmit('send', $this->translator->translate('admin.form.logIn'));
        
        $form->onSubmit[] = [$this, 'submitFormLogin'];
        
        return $form;
    }
}
