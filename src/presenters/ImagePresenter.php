<?php

namespace App\AdminModule\Presenters;

use Nette\Utils\Image;

/**
 * Description of ImagePresenetr
 *
 * @author Vsek
 */
class ImagePresenter extends BasePresenterM{
    public function renderPreview($image, $width = null, $height = null, $sharpen = false){
        //nazev preview
        $previewName = explode('.', $image);
        $postfix = $previewName[count($previewName) - 1];
        unset($previewName[count($previewName) - 1]);
        $previewName = implode('.', $previewName) . '_' . $width . '_' . $height . '.' . $postfix;
        
        //slouzim routu do normalniho tvaru
        $prefixDir = substr($image, 0, 4);
        if(!is_dir($this->context->parameters['wwwDir'] . '/images/preview/' . $prefixDir)){
            mkdir($this->context->parameters['wwwDir'] . '/images/preview/' . $prefixDir);
            chmod($this->context->parameters['wwwDir'] . '/images/preview/' . $prefixDir, 0777);
        }
        
        if(file_exists($this->context->parameters['wwwDir'] . '/images/preview/' . $prefixDir . '/' . $previewName)){
            $image = Image::fromFile($this->context->parameters['wwwDir'] . '/images/preview/' . $prefixDir . '/' . $previewName);
        }else{
            $image = Image::fromFile($this->context->parameters['wwwDir'] . '/images/upload/' . $prefixDir . '/' . $image);
            $image->resize($width, $height);
            if($sharpen){
                $image->sharpen();
            }
            $image->save($this->context->parameters['wwwDir'] . '/images/preview/' . $prefixDir . '/' . $previewName);
        }
        $image->send(Image::PNG);
        $this->terminate();
    }
}
