<?php

namespace App\Grid;
use App\AdminModule\Form;

/**
 * Description of GridEmailLog
 *
 * @author Vsek
 */
class GridEmailLog extends Grid{
    /** @persistent */
    public $filter = array();
    
    /**
     * Nastavi model
     * @param Nette\Database\Table\Selection
     */
    public function setModel(\Nette\Database\Table\Selection $model){
        if(isset($this->filter['email']) && $this->filter['email'] != ''){
            $model->where('adress REGEXP ?', $this->filter['email']);
        }
        if(isset($this->filter['subject']) && $this->filter['subject'] != ''){
            $model->where('subject REGEXP ?', $this->filter['subject']);
        }
        $this->model = $model;
    }
    
    public function submitFormFilter(Form $form){
        $this->filter = $form->getValues();
        $this->getPresenter()->redirect('this');
    }
    
    protected function createComponentFormFilter($name){
        $form = new Form($this, $name);
        
        $form->addText('email', $this->getPresenter()->translator->translate('admin.form.email'));
        $form->addText('subject', $this->getPresenter()->translator->translate('admin.email.subject'));
        
        $form->addSubmit('send', $this->getPresenter()->translator->translate('admin.form.filtrate'));
        
        $form->setDefaults($this->filter);
        
        $form->onSuccess[] = $this->submitFormFilter;
        
        return $form;
    }
}
