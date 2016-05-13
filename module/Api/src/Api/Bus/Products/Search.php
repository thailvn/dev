<?php

namespace Api\Bus\Products;

use Api\Bus\AbstractBus;

/**
 * Search product
 *
 * @package 	Bus
 * @created 	2015-09-06
 * @version     1.0
 * @author      ThaiLai
 * @copyright   YouGo INC
 */
class Search extends AbstractBus {
    
    protected $_required = array(  
        'keyword'
    );
    
    protected $_number_format = array(
        'limit',
        'page'
    );
    
    public function operateDB($model, $param) {
        try {          
            $this->_response = $model->search($param);       
            return $this->result($model->error());
        } catch (\Exception $e) {
            $this->_exception = $e;
        }
        return false;
    }

}
