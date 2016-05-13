<?php

namespace Api\Bus\Blocks;

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
class Update extends AbstractBus {

    protected $_required = array(
        '_id',
    );
    
    protected $_url_format = array(
        'url',
    );
     
    public function operateDB($model, $param) {
        try {          
            $this->_response = $model->updateInfo($param);
            return $this->result($model->error());
        } catch (\Exception $e) {
            $this->_exception = $e;
        }
        return false;
    }

}
