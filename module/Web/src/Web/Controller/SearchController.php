<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Web\Controller;

use Web\Lib\Api;

class SearchController extends AppController
{    
    /**
     * construct
     * 
     */
    public function __construct()
    {        
        parent::__construct();        
    }
    
     /**
     * Search products
     *
     * @return Zend\View\Model
     */
    public function indexAction()
    {
        $param = $this->getParams(array(            
            'page' => 1,
            'limit' => 20, 
            'keyword' => $this->params()->fromRoute('q', '')
        ));        
        $result = Api::call('url_products_search', $param);         
        $id = 'web_index_index';
        $page = $this->getServiceLocator()->get('web_navigation')->findBy('id', $id);
        if (!empty($page)) {
            $page->setLabel($this->translate('Search result by keyword') . ' <strong>' . $param['keyword'] . '</strong> (<strong>' . $result['count'] . '</strong> ' . $this->translate('result') . ')');
            $page->setActive(true);            
        }
        $this->setHead(array(
            'title' => $this->translate('Search result by keyword') . ' ' . $param['keyword']           
        ));
        return $this->getViewModel(array(
                'params' => $this->params()->fromQuery(),
                'result' => $result,                
            )
        ); 
    }
    
}