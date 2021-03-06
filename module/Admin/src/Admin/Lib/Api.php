<?php

namespace Admin\Lib;

use Application\Module;
use Application\Lib\Log;
use Application\Lib\Auth;

/**
 * Functions to call API
 *
 * @package Lib
 * @created 2015-09-05
 * @version 1.0
 * @author thailh
 * @copyright YouGO INC
 */
class Api {
    
    public static $errors = array();
       
   /**
     * Call API
     *  
     * @author thailh   
     * @return mixed Return API result
     */
	public static function call($url, $param = array()) {
        $config = Module::getConfig('api');
        $method = 'post';
        if (isset($config[$url])) {
            if (is_array($config[$url])) {
                list($url, $method) = $config[$url];        
            } else {
                $url = $config[$url];
            }
        }
        try {
            $auth = new Auth();
            if ($auth->hasIdentity()) {    
                $AppUI = $auth->getIdentity();           
                $param['login_id'] = $AppUI->id;               
                $param['access_token'] = $AppUI->access_token;  
                if (empty($param['website_id'])) {
                    $param['website_id'] = $AppUI->website_id;    
                }
            }      
            if ($_FILES) {                
                foreach ($_FILES as $name => $file) {
                    if (!empty($file['name'])) {
                        $param[$name] = new \CurlFile($file['tmp_name'], $file['type'], $file['name']);
                    }
                }
            }       
            $headers = array("Content-Type:multipart/form-data"); 
            foreach ($param as $name => $value) {
                if (is_array($value)) {
                    foreach ($value as $k => $v) {
                        if (is_scalar($v)) {
                            $param["{$name}[{$k}]"] = $v;
                        } elseif (is_array($v)) {
                            foreach ($v as $k2 => $v2) {
                                $param["{$name}[{$k}][{$k2}]"] = $v2;
                            }
                        }
                    }
                }                
            }
            
            $url = $config['base_uri'] . $url;
            $ch = curl_init();
            $options = array(
                CURLOPT_URL => $url,
                CURLOPT_HEADER => false,
                CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_POSTFIELDS => $param,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SAFE_UPLOAD => false,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_TIMEOUT => $config['timeout'],
            );
            curl_setopt_array($ch, $options);
            $response = curl_exec($ch);
            Log::info($url);
            if (isset($param['debug'])) { 
                Log::info($response);
            }
            $errno = curl_errno($ch);
            if (empty($errno)) {
                curl_close($ch);
                $result = json_decode($response, true);
                switch ($result['status']) {
                    case 'OK';
                        return $result['results']; 
                        break;
                    case 'ERROR_VALIDATION':
                        Log::error("ERROR_VALIDATION", $result);
                        static::$errors = $result['results'];                        
                        return false;
                    case 'ERROR':
                        Log::error("ERROR", $result['results']);
                }
            }
            if (!empty($ch)) {
                @curl_close($ch);
            }
            throw new \Exception('System error');
        } catch (\Exception $e) {         
            self::logEx($e);          
        }
        return false;
    }
    
    public static function error() {
        return static::$errors;
    }
    
    public static function logEx($e) {
        Log::error(sprintf("Exception\n"
            . " - Message : %s\n"
            . " - Code : %s\n"
            . " - File : %s\n"
            . " - Line : %d\n"
            . " - Stack trace : \n"
            . "%s",
            $e->getMessage(),
            $e->getCode(),
            $e->getFile(),
            $e->getLine(),
            $e->getTraceAsString())
        );
    }
}

