<?php

namespace Api\Bus\FacebookWallShares;

use Api\Bus\AbstractBus;

/** 
 *
 * @package 	Bus
 * @created 	2015-09-06
 * @version     1.0
 * @author      ThaiLai
 * @copyright   YouGo INC
 */
class All extends AbstractBus {
    
    protected $_required = array( 
        'user_id',
        'website_id',
    );
    
    public function operateDB($model, $param) {
        try {
            $this->_response = $model->getAll($param);
            return $this->result($model->error());
        } catch (\Exception $e) {
            $this->_exception = $e;
        }
        return false;
    }

}
