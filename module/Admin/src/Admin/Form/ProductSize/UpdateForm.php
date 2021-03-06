<?php

namespace Admin\Form\ProductSize;

use Application\Form\AbstractForm;

/**
 * Category Update Form
 *
 * @package 	Admin\Form
 * @created 	2015-08-25
 * @version     1.0
 * @author      thailh
 * @copyright   YouGo INC
 */
class UpdateForm extends AbstractForm
{  
    /**
    * Form construct
    *
    * @param string $name Form name
    */
    public function __construct($name = null)
    {
        parent::__construct($name); 
    }
    
    /**
    * Element array to create form
    *
    * @return array Array to create elements for form
    */
    public function elements() {
        $elements = array(
            array(
                'name' => '_id',
                'attributes' => array(
                    'type' => 'hidden',
                ),
            )
        );
        $locales = \Application\Module::getConfig('general.locales');
        if (count($locales) == 1) { 
            $elements = array_merge(
                $elements,
                array(
                    array(
                        'name' => 'locale',
                        'attributes' => array(
                            'type' => 'hidden',
                        ),
                    ),               
                    array(            
                        'name' => 'name',
                        'attributes' => array(
                            'id' => 'name',
                            'type' => 'text',
                            'required' => true,
                            'class' => 'form-control'
                        ),
                        'options' => array(
                            'label' => 'Size name',
                        ),    
                        'validators' => \Admin\Module::getValidatorConfig('product_sizes.name')
                    ),
                    array(
                        'name' => 'short',
                        'attributes' => array(
                            'id' => 'short',
                            'type' => 'textarea',
                            'required' => false,
                            'class' => 'form-control',
                            'rows' => 4
                        ),
                        'options' => array(
                            'label' => 'Short',                            
                        ),                       
                    ),                    
                )
            );
        }
        $elements = array_merge(
            $elements,
            array(
                array(            
                    'name' => 'price',
                    'attributes' => array(
                        'id' => 'price',
                        'type' => 'text',
                        'class' => 'form-control price',
                        'data-a-sep' => ',',
                        'data-a-dec' => '.',
                        'data-v-max' => '99999999999',
                        'data-v-min' => '0'
                    ),
                    'options' => array(
                        'label' => 'Price',
                    ),
                    'validators' => \Admin\Module::getValidatorConfig('general.float')
                ),                
            )
        );
        $elements = array_merge(
            $elements,
            array(
                array(
                    'name' => 'saveAndBack',
                    'attributes' => array(
                        'type'  => 'submit',
                        'value' => 'Update And Back',
                        'id' => 'saveAndBackButton',
                        'class' => 'btn btn-primary'                    
                    ),
                ),
                array(
                    'name' => 'save',
                    'attributes' => array(
                        'type'  => 'submit',
                        'value' => 'Save',
                        'id' => 'submitbutton',
                        'class' => 'btn btn-primary'                    
                    ),
                ),
                array(
                    'name' => 'cancel',
                    'attributes' => array(
                        'type'  => 'button',
                        'value' => $this->translate('Cancel'),                  
                        'class' => 'btn',
                        'onclick' => "location.href='" . base64_decode($this->getController()->params()->fromQuery('backurl')) . "'"
                    ),
                )
            )
        );
        return $elements;
    }  
    
}