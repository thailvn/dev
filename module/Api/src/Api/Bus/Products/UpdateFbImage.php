<?php

namespace Api\Bus\Products;

use Api\Bus\AbstractBus;

/**
 * update categories
 *
 * @package 	Bus
 * @created 	2015-09-06
 * @version     1.0
 * @author      ThaiLai
 * @copyright   YouGo INC
 */
class UpdateFbImage extends AbstractBus {

    protected $_required = array(
        'website_id'
    );
    
    protected $_url_format = array(
        
    );
    
    public function operateDB($model, $param) {
        try {
            $this->_response = $model->updateFbImage($param);
            return $this->result($model->error());
        } catch (\Exception $e) {
            $this->_exception = $e;
        }
        return false;
    }

}
