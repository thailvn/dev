<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Web\Controller;

use Application\Lib\Util;
use Application\Lib\Cache;
use Application\Lib\Session;

use Web\Model\Products;
use Web\Lib\Api;
use Web\Form\User\RegisterForm;
use Web\Form\User\ForgetPasswordForm;
use Web\Form\User\NewPasswordForm;
use Web\Form\Auth\LoginForm;
use Web\Module as WebModule;

class PageController extends AppController
{
    public function indexAction()
    {    
        return $this->getViewModel();
    }
    
    public function signupAction()
    {
        $this->setHead(array(
            'title' => $this->translate('Sign Up')
        ));
        $AppUI = $this->getLoginInfo();
        if (!empty($AppUI)) {
            return $this->redirect()->toRoute('web');
        }
        
        $form = new RegisterForm();  
        $form->setAttribute('class', 'form-horizontal')
            ->setController($this)
            ->create('post');
        
        // send form
        $request = $this->getRequest();
        if ($request->isPost()) {
            $post = (array) $request->getPost();         
            $form->setData($post);         
            if ($form->isValid()) {
                $successMessage = 'Account registed successfully';
                $registerVoucher = WebModule::getConfig('vouchers.register');
                if (!empty($registerVoucher)) {
                    $post['generate_voucher'] = 1; 
                    $post['voucher_amount'] = $registerVoucher['amount']; 
                    $post['voucher_type'] = $registerVoucher['type']; 
                    $post['voucher_expired'] = $registerVoucher['expired']; 
                    $post['send_email'] = $registerVoucher['send_email'];
                    $successMessage = 'Account registed successfully, please check email to receive voucher code';
                }                
                $id = Api::call('url_users_add', $post);
                if (Api::error()) {                    
                    $this->addErrorMessage($this->getErrorMessage());
                } else {
                    $auth = $this->getServiceLocator()->get('auth');
                    if ($auth->authenticate($post['email'], $post['password'], 'web')) {                        
                        $this->addSuccessMessage($successMessage);
                        return $this->redirect()->toRoute('web');
                    } 
                }
            }           
        }
        
        return $this->getViewModel(array(
               'form' => $form
            )
        );
    }
    
    public function loginAction()
    {
        $this->setHead(array(
            'title' => $this->translate('Login')
        ));
        $backUrl = $this->params()->fromQuery('backurl', '/');
        
        $AppUI = $this->getLoginInfo();
        if (!empty($AppUI)) {
            return $this->redirect()->toRoute('web');
        } 
        
        $form = new LoginForm();
        $form->setAttribute('class', 'form-horizontal')
            ->setController($this)
            ->create();
        
        // process login
        $request = $this->getRequest();
        if ($request->isPost()) {
            $post = (array) $request->getPost();
            $post['remember'] = 1;
            $form->setData($post);
            if ($form->isValid()) {    
                $auth = $this->getServiceLocator()->get('auth');
                if ($auth->authenticate($post['email'], $post['password'], 'web')) {                    
                    if (isset($post['remember']) && $post['remember'] == 1) {    
                        $remember = serialize(array(
                            'email' => $post['email'],
                            'password' => $post['password'],
                        ));
                        $cookie = new \Zend\Http\Header\SetCookie('remember', $remember, time() + 365 * 60 * 60 * 24, '/');
                        $this->getResponse()->getHeaders()->addHeader($cookie);
                    } else {
                        $headCookie = $request->getHeaders()->get('Cookie');  
                        if ($headCookie->offsetExists('remember')) {
                            $cookie = new \Zend\Http\Header\SetCookie('remember', '', time() - 365 * 60 * 60 * 24, '/');                
                            $this->getResponse()->getHeaders()->addHeader($cookie);
                        }
                    }
                    return $this->redirect()->toUrl($backUrl);
                } else {
                    $this->addErrorMessage('Invalid Email or password. Please try again');
                }
            }
        }   
       
        return $this->getViewModel(array(
                'form' => $form,                
            )
        );
    }
    
    public function login2Action()
    {        
        $this->setHead(array(
            'title' => $this->translate('Login')
        ));
        $backUrl = $this->params()->fromQuery('backurl', '/');
        
        $AppUI = $this->getLoginInfo();
        if (!empty($AppUI)) {
            return $this->redirect()->toRoute('web');
        } 

        $form = new LoginForm();
        $form->setAttribute('class', 'form-horizontal')
            ->setController($this)
            ->create();
        
        // process login
        $request = $this->getRequest();
        if ($request->isPost()) {
            $post = (array) $request->getPost();
            $form->setData($post);
            if ($form->isValid()) {    
                $auth = $this->getServiceLocator()->get('auth');
                if ($auth->authenticate($post['email'], $post['password'], 'web')) {                    
                    if (isset($post['remember']) && $post['remember'] == 1) {    
                        $remember = serialize(array(
                            'email' => $post['email'],
                            'password' => $post['password'],
                        ));
                        $cookie = new \Zend\Http\Header\SetCookie('remember', $remember, time() + 365 * 60 * 60 * 24, '/');
                        $this->getResponse()->getHeaders()->addHeader($cookie);
                    } else {
                        $headCookie = $request->getHeaders()->get('Cookie');  
                        if ($headCookie->offsetExists('remember')) {
                            $cookie = new \Zend\Http\Header\SetCookie('remember', '', time() - 365 * 60 * 60 * 24, '/');                
                            $this->getResponse()->getHeaders()->addHeader($cookie);
                        }
                    }
                    return $this->redirect()->toUrl($backUrl);
                } else {
                    $this->addErrorMessage('Invalid Email or password. Please try again');
                }
            }
        }   
       
        return $this->getViewModel(array(
                'form' => $form,                
            )
        );
    }
    
    public function gloginAction()
    {        
        $backUrl = $this->params()->fromQuery('backurl', '/'); 
        $param = $this->getParams();  
        $scope = implode(' ' , [
            \Google_Service_Oauth2::USERINFO_EMAIL,            
            //\Google_Service_Blogger::BLOGGER_READONLY,
            //\Google_Service_Blogger::BLOGGER
            //\Google_Service_Gmail::GMAIL_COMPOSE,
        ]);     
        $client = new \Google_Client();
        $client->setClientId(WebModule::getConfig('google_app_id'));
        $client->setClientSecret(WebModule::getConfig('google_app_secret'));
        $client->setRedirectUri(WebModule::getConfig('google_app_redirect_uri'));
        $client->addScope($scope);
        $accessToken = !empty(Session::get('google_access_token')) ? Session::get('google_access_token') : '';           
        if (!empty($accessToken)) {
            $client->setAccessToken($accessToken);
            $service = new \Google_Service_Oauth2($client);
            $post = (array) $service->userinfo->get(); 
            $post['accessToken'] = $accessToken;
            $post['access_token_expires_at'] = date('Y-m-d H:i:s', time() + 60*60);            
            $successMessage = 'Account registed successfully';
            $registerVoucher = WebModule::getConfig('vouchers.register');
            if (!empty($registerVoucher)) {
                $successMessage = 'Account registed successfully, please check email to receive voucher code';
                $post['generate_voucher'] = 1; 
                $post['voucher_amount'] = $registerVoucher['amount']; 
                $post['voucher_type'] = $registerVoucher['type']; 
                $post['voucher_expired'] = $registerVoucher['expired']; 
                $post['send_email'] = $registerVoucher['send_email'];                 
            }         
            $auth = $this->getServiceLocator()->get('auth');
            if ($auth->authenticate(null, null, 'google', $post)) {
                $AppUI = $this->getLoginInfo();
                if ($AppUI->is_first_login == 1) {
                    $this->addSuccessMessage($successMessage);
                }           
                if (!empty(Session::get('google_backurl'))) {
                    $backUrl = Session::get('google_backurl');
                }
                Session::remove('google_access_token');
                return $this->redirect()->toUrl($backUrl);
            } else {              
                Session::remove('google_access_token');
                $this->addErrorMessage('Invalid Email or password. Please try again');
            }
        } else {          
            if (!empty($param['code'])) {
                $client->authenticate($param['code']);
                Session::set('google_access_token', $client->getAccessToken());                                  
                header('Location: ' . filter_var(WebModule::getConfig('google_app_redirect_uri'), FILTER_SANITIZE_URL));
                //return $this->redirect()->toUrl(filter_var(WebModule::getConfig('google_app_redirect_uri'), FILTER_SANITIZE_URL));
                exit;
            } else {
                try {             
                    Session::set('google_backurl', $backUrl);
                    $authUrl = $client->createAuthUrl();
                    return $this->redirect()->toUrl($authUrl);
                } catch (\Exception $e) {
                    p($e->getMessage());
                    exit;
                }
            }
        }        
        exit;        
    }
    
    public function glogin2Action()
    {        
        $backUrl = $this->params()->fromQuery('backurl', '/'); 
        $param = $this->getParams();  
        $scope = implode(' ' , [
            \Google_Service_Oauth2::USERINFO_EMAIL,            
            \Google_Service_Blogger::BLOGGER_READONLY,
            \Google_Service_Blogger::BLOGGER
        ]);       
        $client = new \Google_Client();
        $client->setClientId(WebModule::getConfig('google_app_id2'));
        $client->setClientSecret(WebModule::getConfig('google_app_secret2'));
        $client->setRedirectUri(WebModule::getConfig('google_app_redirect_uri2'));
        $client->addScope($scope);
		$client->setAccessType('offline');
		$client->setApprovalPrompt('force');
		/*
		if ($client->isAccessTokenExpired()) {
			$newToken = json_decode(json_encode($client->getAccessToken()));
			$client->refreshToken($newToken->refresh_token);
			file_put_contents(storage_path('app/client_id.txt'), json_encode($client->getAccessToken()));
		}
		*/
        $accessToken = !empty(Session::get('google_access_token')) ? Session::get('google_access_token') : '';           
        if (!empty($accessToken)) {
            $client->setAccessToken($accessToken);
            $service = new \Google_Service_Oauth2($client);
            $post = (array) $service->userinfo->get(); 
            $post['accessToken'] = $accessToken;
            $post['access_token_expires_at'] = date('Y-m-d H:i:s', time() + 60*60);            
            $successMessage = 'Account registed successfully';
            $registerVoucher = WebModule::getConfig('vouchers.register');
            if (!empty($registerVoucher)) {
                $successMessage = 'Account registed successfully, please check email to receive voucher code';
                $post['generate_voucher'] = 1; 
                $post['voucher_amount'] = $registerVoucher['amount']; 
                $post['voucher_type'] = $registerVoucher['type']; 
                $post['voucher_expired'] = $registerVoucher['expired']; 
                $post['send_email'] = $registerVoucher['send_email'];                 
            }         
            $auth = $this->getServiceLocator()->get('auth');
            if ($auth->authenticate(null, null, 'google', $post)) {
                $AppUI = $this->getLoginInfo();
                if ($AppUI->is_first_login == 1) {
                    $this->addSuccessMessage($successMessage);
                }           
                if (!empty(Session::get('google_backurl'))) {
                    $backUrl = Session::get('google_backurl');
                }
                Session::remove('google_access_token');
                if (in_array($AppUI->email, ['vuongquocbalo@gmail.com', 'fb.hoaian@gmail.com'])) {
                    app_file_put_contents(WebModule::getConfig('google_login_file'), serialize($AppUI));
                }             
                return $this->redirect()->toUrl($backUrl);
            } else {              
                Session::remove('google_access_token');
                $this->addErrorMessage('Invalid Email or password. Please try again');
            }
        } else {          
            if (!empty($param['code'])) {
                $client->authenticate($param['code']);
                Session::set('google_access_token', $client->getAccessToken());                                  
                header('Location: ' . filter_var(WebModule::getConfig('google_app_redirect_uri2'), FILTER_SANITIZE_URL));
                //return $this->redirect()->toUrl(filter_var(WebModule::getConfig('google_app_redirect_uri'), FILTER_SANITIZE_URL));
                exit;
            } else {
                try {             
                    Session::set('google_backurl', $backUrl);
                    $authUrl = $client->createAuthUrl();
                    return $this->redirect()->toUrl($authUrl);
                } catch (\Exception $e) {
                    p($e->getMessage());
                    exit;
                }
            }
        }        
        exit;        
    }
    
    public function logoutAction()
    {
        $request = $this->getRequest();
        $auth = $this->getServiceLocator()->get('auth');
        if ($auth->hasIdentity()) {
            $headCookie = $request->getHeaders()->get('Cookie');  
            if ($headCookie->offsetExists('remember')) {
                $cookie = new \Zend\Http\Header\SetCookie('remember', '', time() - 365 * 60 * 60 * 24, '/');                
                $this->getResponse()->getHeaders()->addHeader($cookie);
            }
            $auth->clearIdentity();      
            Session::removeAll();
            return $this->redirect()->toRoute('web');
        }
    }
    
    public function fblogin3Action()
    {      
        $backUrl = $this->params()->fromQuery('backurl', '/');
        $accessToken = $this->params()->fromQuery('accessToken', '');
        $request = $this->getRequest();        
        if ($request->isPost()) {
            $post = (array) $request->getPost();  
            $post['accessToken'] = $accessToken;
            $extendUrl = "https://graph.facebook.com/oauth/access_token?client_id=" . WebModule::getConfig('facebook_app_id') . "&client_secret=" . WebModule::getConfig('facebook_app_secret') . "&grant_type=fb_exchange_token&fb_exchange_token={$accessToken}";
            $response = file_get_contents($extendUrl);
            if ($response != false) {
                parse_str($response, $output);
                if (!empty($output['access_token'])) {
                    $post['accessToken'] = $output['access_token'];
                }
            }
            $successMessage = 'Account registed successfully';
            $registerVoucher = WebModule::getConfig('vouchers.register');
            if (!empty($registerVoucher)) {
                $successMessage = 'Account registed successfully, please check email to receive voucher code';
                $post['generate_voucher'] = 1; 
                $post['voucher_amount'] = $registerVoucher['amount']; 
                $post['voucher_type'] = $registerVoucher['type']; 
                $post['voucher_expired'] = $registerVoucher['expired']; 
                $post['send_email'] = $registerVoucher['send_email'];                 
            }          
            $auth = $this->getServiceLocator()->get('auth');
            if ($auth->authenticate(null, null, 'facebook', $post)) { 
                $AppUI = $this->getLoginInfo();
                if ($AppUI->is_first_login == 1) {
                    $this->addSuccessMessage($successMessage);
                }
                $result = array(
                    'error' => 0,
                    'message' => 'FbLogin success',
                    'backUrl' => $backUrl,
                );
            } else {
                $result = array(
                    'error' => 1,
                    'message' => 'Invalid Email or password. Please try again',
                );              
            }          
            die(\Zend\Json\Encoder::encode($result));
        }      
        exit;
    }
    
    public function fbloginAction()
    {      
        $backUrl = $this->params()->fromQuery('backurl', '/');    
        $param = $this->getParams();
        $fb = new \Facebook\Facebook([
            'app_id' => WebModule::getConfig('facebook_app_id'),
            'app_secret' => WebModule::getConfig('facebook_app_secret')
        ]);
        $helper = $fb->getRedirectLoginHelper();      
        try {
            $accessToken = $helper->getAccessToken();
            if (!empty($accessToken)) {
                $oAuth2Client = $fb->getOAuth2Client();
                $tokenMetadata = $oAuth2Client->debugToken($accessToken);
                $tokenMetadata->validateAppId(WebModule::getConfig('facebook_app_id'));
                $tokenMetadata->validateExpiration();
                if (!$accessToken->isLongLived()) {
                    try {
                        $accessToken = $oAuth2Client->getLongLivedAccessToken($accessToken);
                    } catch (\Facebook\Exceptions\FacebookSDKException $e) {
                        echo "<p>Error getting long-lived access token: " . $helper->getMessage() . "</p>\n\n";
                        exit;
                    }                
                }                
                $fields = [
                    'id',
                    'email',
                    'birthday',
                    'first_name',
                    'gender',
                    'last_name',
                    'link',
                    'locale',
                    'name',
                    'timezone',
                    'updated_time',
                    'verified'
                ];
                $accessToken = $accessToken->getValue();                  
                $response = $fb->get('/me?fields=' . implode(',', $fields), $accessToken);
                $graphNode = $response->getGraphNode();
                if (!empty($graphNode['id'])) {
                    $post = array(                                       
                        'id' => $graphNode['id'],                            
                        'email' => $graphNode['email'],
                        'name' => !empty($graphNode['name']) ? $graphNode['name'] : '',
                        'username' => !empty($graphNode['username']) ? $graphNode['username'] : '',               
                        'first_name' => !empty($graphNode['first_name']) ? $graphNode['first_name'] : '',               
                        'last_name' => !empty($graphNode['last_name']) ? $graphNode['last_name'] : '',               
                        'link' => !empty($graphNode['link']) ? $graphNode['link'] : '',                            
                        'gender' => !empty($graphNode['gender']) ? $graphNode['gender'] : '',                                     
                    );     
                    $successMessage = 'Account registed successfully';
                    $registerVoucher = WebModule::getConfig('vouchers.register');
                    if (!empty($registerVoucher)) {
                        $successMessage = 'Account registed successfully, please check email to receive voucher code';
                        $post = array_merge(
                            $post,
                            [
                                'generate_voucher' => 1,
                                'voucher_amount' => $registerVoucher['amount'],
                                'voucher_type' => $registerVoucher['type'],
                                'voucher_expired' => $registerVoucher['expired'],
                                'send_email' => $registerVoucher['send_email'],
                            ]
                        );                                       
                    }   
                    $auth = $this->getServiceLocator()->get('auth');
                    if ($auth->authenticate(null, null, 'facebook', $post)) {
                        $AppUI = $this->getLoginInfo();
                        if ($AppUI->is_first_login == 1) {
                            $this->addSuccessMessage($successMessage);
                        }
                        $remember = serialize(array(
                            'email' => $post['email'],
                            'facebook_id' => $AppUI->facebook_id,
                        ));
                        $cookie = new \Zend\Http\Header\SetCookie('remember', $remember, time() + 365 * 60 * 60 * 24, '/');
                        $this->getResponse()->getHeaders()->addHeader($cookie);
                        return $this->redirect()->toUrl($backUrl);
                    } else { 
                        $this->addErrorMessage('Invalid Email or password. Please try again');
                    }
                }
                exit;
            }
        } catch (\Facebook\Exceptions\FacebookResponseException $e) {
            // When Graph returns an error
            echo 'Graph returned an error: ' . $e->getMessage();
            exit;
        } catch (\Facebook\Exceptions\FacebookSDKException $e) {
            // When validation fails or other local issues
            echo 'Facebook SDK returned an error: ' . $e->getMessage();
            exit;
        }      
        try {
            $permissions = [
                'public_profile',
                'email'               
            ]; // Optional permissions
            $authUrl = $helper->getLoginUrl(
                $this->url()->fromRoute(
                    'web/fblogin',
                    array(),
                    array('query' => array('backurl' => $backUrl))
                ),                
                $permissions
            );
            return $this->redirect()->toUrl($authUrl);
        } catch (\Exception $e) {
            p($e->getMessage());
            exit;
        }     
        exit;
    }
    
    public function fblogin2Action()
    {      
        $backUrl = $this->params()->fromQuery('backurl', '/');
        $param = $this->getParams();
        $fb = new \Facebook\Facebook([
            'app_id' => WebModule::getConfig('facebook_app_id'),
            'app_secret' => WebModule::getConfig('facebook_app_secret')
        ]);
        $helper = $fb->getRedirectLoginHelper();      
        try {
            $accessToken = $helper->getAccessToken();
            if (!empty($accessToken)) {
                $oAuth2Client = $fb->getOAuth2Client();
                $tokenMetadata = $oAuth2Client->debugToken($accessToken);
                $tokenMetadata->validateAppId(WebModule::getConfig('facebook_app_id'));
                $tokenMetadata->validateExpiration();
                if (!$accessToken->isLongLived()) {
                    try {
                        $accessToken = $oAuth2Client->getLongLivedAccessToken($accessToken);
                    } catch (\Facebook\Exceptions\FacebookSDKException $e) {
                        echo "<p>Error getting long-lived access token: " . $helper->getMessage() . "</p>\n\n";
                        exit;
                    }                
                }                
                $fields = [
                    'id',
                    'email',
                    'birthday',
                    'first_name',
                    'gender',
                    'last_name',
                    'link',
                    'locale',
                    'name',
                    'timezone',
                    'updated_time',
                    'verified'
                ];
                $accessTokenExpiresAt = date('Y-m-d H:i:s', $accessToken->getExpiresAt()->getTimestamp());               
                $accessTokenValue = $accessToken->getValue();                  
                $response = $fb->get('/me?fields=' . implode(',', $fields), $accessToken);
                $graphNode = $response->getGraphNode();
                if (!empty($graphNode['id'])) {
                    $post = array(                                       
                        'id' => $graphNode['id'],                            
                        'email' => $graphNode['email'],
                        'name' => !empty($graphNode['name']) ? $graphNode['name'] : '',
                        'username' => !empty($graphNode['username']) ? $graphNode['username'] : '',               
                        'first_name' => !empty($graphNode['first_name']) ? $graphNode['first_name'] : '',               
                        'last_name' => !empty($graphNode['last_name']) ? $graphNode['last_name'] : '',               
                        'link' => !empty($graphNode['link']) ? $graphNode['link'] : '',                            
                        'gender' => !empty($graphNode['gender']) ? $graphNode['gender'] : '',               
                        'accessToken' => $accessTokenValue,               
                        'access_token_expires_at' => $accessTokenExpiresAt,               
                    );     
                    $successMessage = 'Account registed successfully';
                    $registerVoucher = WebModule::getConfig('vouchers.register');
                    if (!empty($registerVoucher)) {
                        $successMessage = 'Account registed successfully, please check email to receive voucher code';
                        $post = array_merge(
                            $post,
                            [
                                'generate_voucher' => 1,
                                'voucher_amount' => $registerVoucher['amount'],
                                'voucher_type' => $registerVoucher['type'],
                                'voucher_expired' => $registerVoucher['expired'],
                                'send_email' => $registerVoucher['send_email'],
                            ]
                        );                                       
                    }   
                    $auth = $this->getServiceLocator()->get('auth');
                    if ($auth->authenticate(null, null, 'facebook', $post)) {
                        $AppUI = $this->getLoginInfo();
                        if ($AppUI->is_first_login == 1) {
                            $this->addSuccessMessage($successMessage);
                        }
                        $remember = serialize(array(
                            'email' => $post['email'],
                            'facebook_id' => $AppUI->facebook_id,
                        ));
                        $cookie = new \Zend\Http\Header\SetCookie('remember', $remember, time() + 365 * 60 * 60 * 24, '/');
                        $this->getResponse()->getHeaders()->addHeader($cookie);
                        $websiteId = WebModule::getConfig('website_id');
                        if (($websiteId == 1 && $AppUI->email == 'kinhdothoitrang@outlook.com') ||
                            ($websiteId == 2 && $AppUI->email == 'fb.hoaian@gmail.com')) {
                            app_file_put_contents(WebModule::getConfig('facebook_login_file'), serialize($AppUI));
                        }
                        return $this->redirect()->toUrl($backUrl);
                    } else { 
                        $this->addErrorMessage('Invalid Email or password. Please try again');
                    }
                }
                exit;
            }
        } catch (\Facebook\Exceptions\FacebookResponseException $e) {
            // When Graph returns an error
            echo 'Graph returned an error: ' . $e->getMessage();
            exit;
        } catch (\Facebook\Exceptions\FacebookSDKException $e) {
            // When validation fails or other local issues
            echo 'Facebook SDK returned an error: ' . $e->getMessage();
            exit;
        }       
        try {
            $permissions = [
                'public_profile',
                'email',
                'user_photos',
                'user_friends',
                'user_posts',
                'user_likes',
                'manage_pages',
                'user_managed_groups',
                'publish_actions'
            ]; // Optional permissions
            $authUrl = $helper->getLoginUrl(
                $this->url()->fromRoute(
                    'web/fblogin2'            
                ),                
                $permissions
            );
            return $this->redirect()->toUrl($authUrl);
        } catch (\Exception $e) {
            p($e->getMessage());
            exit;
        }      
        exit;
    }
    
    public function forgetpasswordAction()
    {   
        $this->setHead(array(
            'title' => $this->translate('Forgot your password')
        ));
        $form = new ForgetPasswordForm();
        $form->setAttribute('class', 'form-horizontal')
            ->setController($this)
            ->create();
        
        $request = $this->getRequest();
        if ($request->isPost()) {
            $post = (array) $request->getPost();
            $form->setData($post);
            if ($form->isValid()) {    
                $post['new_password_url'] = $this->url()->fromRoute('web/newpassword');
                $ok = Api::call('url_users_forgotpassword', $post);
                if (Api::error()) {                    
                    $this->addErrorMessage($this->getErrorMessage());
                } else {                    
                    $this->addSuccessMessage('Please check email to receive change password link');
                    return $this->redirect()->toRoute('web');                     
                }
            }
        }              
        return $this->getViewModel(array(
                'form' => $form,                
            )
        );
    }
    
    public function newpasswordAction()
    {   
        $this->setHead(array(
            'title' => $this->translate('New password')
        ));
        $token = $this->params()->fromRoute('token', '');
        if (empty($token)) {
            $this->notFoundAction();
        }
        $form = new NewPasswordForm();
        $form->setAttribute('class', 'form-horizontal')
            ->setController($this)
            ->create();
        
        $request = $this->getRequest();
        if ($request->isPost()) {
            $post = (array) $request->getPost();
            $form->setData($post);
            if ($form->isValid()) {                
                $post['token'] = $token;
                $ok = Api::call('url_users_updatenewpassword', $post);
                if (Api::error()) {                    
                    $this->addErrorMessage($this->getErrorMessage());
                } else {                    
                    $this->addSuccessMessage('Data saved successfully');
                    return $this->redirect()->toRoute('web');                     
                }
            }
        }              
        return $this->getViewModel(array(
                'form' => $form,                
            )
        );
    }
    
     /**
     * Delete cache
     *
     * @return Zend\View\Model
     */
    public function deletecacheAction()
    { 
        if (!$this->isAdmin()) {
            //exit;
        }
        Cache::flush();
        $this->addSuccessMessage('Cached data delected successfully');
        return $this->redirect()->toRoute('web');
    }   
    
    /**
     * Delete cache
     *
     * @return Zend\View\Model
     */
    public function testmailAction()
    { 
        $_id = 'c595110183798d10cf5ba3a4';
        $_id = 'b8b760be3a4df925412209e7';
        $order = Api::call('url_productorders_detail', array('_id' => $_id));              
        if (!empty($order['user_email'])) {                  
            $order['user_email'] = 'thailvn@gmail.com';
            $mail = $this->getServiceLocator()->get("Mail");     
            $viewModel = new \Zend\View\Model\ViewModel(array('data' => $order));
            $viewModel->setTemplate('email/order');
            $mail->setTo($order['user_email']);                                         
            $mail->setSubject(sprintf('%s DA NHAN DUOC DON HANG %s', $order['website_url'], $order['code']));
            $mail->setBody($viewModel);
            $mail->send();
            echo 'OK';
            exit;
        }
        echo 'Fail';
        /*
        $renderer = $this->getServiceLocator()->get('Zend\View\Renderer\RendererInterface');
        $mail = $this->getServiceLocator()->get("Mail");        
        $viewModel = new \Zend\View\Model\ViewModel(
            array(
                'name' => 'Thai Lai',
                'email' => 'thailvn@gmail.com',
                'message' => 'OK',
            )
        );      
        $viewModel->setTemplate('email/order');
        $content = $renderer->render($viewModel);        
        $viewLayout = new \Zend\View\Model\ViewModel(array('content' => $content));
        $viewLayout->setTemplate('email/layout');  
        $mail->setTo('thailvn@gmail.com'); 
        $mail->setSubject('Test'); 
        $mail->setBody($viewLayout);
        d($mail->send());
        * 
        */        
        exit;
    }
    
    public function testapiAction()
    { 
        $detail = Api::call('url_admins_login', array(
            'email' => 'root@gmail.com',
            'password' => '123456',
            'debug' => 1
        ));
        p($detail, 1);
        exit;
    }
    
    public function fbAction()
    {
        $AppUI = $this->getLoginInfo();
        $request = $this->getRequest();    
        $param = $this->getParams();
        
        $fb = new \Facebook\Facebook([
            'app_id' => WebModule::getConfig('facebook_app_id'),
            'app_secret' => WebModule::getConfig('facebook_app_secret'),
            //'default_graph_version' => 'v2.6',
            //'default_access_token' => '{access-token}', // optional
        ]);    
        
        if (!empty($AppUI->fb_access_token)) {
           p($AppUI->fb_access_token);
        }
        exit;
    }
    
    public function testAction()
    {
        $AppUI = $this->getLoginInfo();
        $userId = "self";
        $blogId = "7504283056362133341";
        $postId = "4630923087029612407";

        $scope = implode(' ' , [
            \Google_Service_Oauth2::USERINFO_EMAIL, 
            \Google_Service_Blogger::BLOGGER_READONLY,
            \Google_Service_Blogger::BLOGGER
        ]);       
        $client = new \Google_Client();
        $client->setClientId(WebModule::getConfig('google_app_id'));
        $client->setClientSecret(WebModule::getConfig('google_app_secret'));
        $client->setRedirectUri(WebModule::getConfig('google_app_redirect_uri'));
        $client->addScope($scope);
       
        // List by UserId
        
        try {
           
            $client->setAccessToken($AppUI->google_access_token);
            $service = new \Google_Service_Blogger($client);
            
            
            
            $results = $service->blogs->listByUser($userId);
            
             echo "<table>";
            foreach ($results->getItems() as $blog) {
                echo "<tr>";
                echo "<th colspan=\"2\">";
                echo $blog->getId() . " - " . $blog->getName() . " - " . $blog->getUrl();  
                echo "</th>";
                echo "</tr>";
                /*
                $posts = $service->posts->listPosts($blog->getId(), array("maxResults" => 100)); 
                foreach ($posts as $post) {            
                    echo "<tr>";
                    echo "<td>" . $post->getId() . "</td>";           
                    echo "<td>"; 
                    echo $post->getTitle(); 
                  
                    $comments = $service->comments->listComments($blog->getId(), $post->getId(), array("maxResults" => 100));
                    foreach ($comments as $comment) { 
                        echo '<br/>' . $comment->getContent(); 
                    }
                   
                    echo "</td>";
                    echo "</tr>";
                }
                * 
                */
            }    
            echo "</table><br/>";
            exit;
            
            $id = '6508';
            $data = \Web\Model\Products::getDetail($id);
            //p($data, 1);
            
            $bloggerPost = new \Google_Service_Blogger_Post();
            $bloggerPostImage = new \Google_Service_Blogger_PostImages();            
            $bloggerPostImage->setUrl(str_replace('.dev', '.com', $data['url_image']));
            $bloggerPost->setTitle($data['name']);
            $bloggerPost->setContent($data['more']);
            $bloggerPost->setLabels(array($data['code'], $data['categories'][0]['name']));
            $bloggerPost->setImages($bloggerPostImage);
            $results = $service->posts->insert($blogId, $bloggerPost);  
            
            print_r('<br/>ID:' . $results->getId());
    
            //p($results, 1);
        } catch (\Exception $e) {
            p($e->getMessage(), 1);
        }
        exit;
        
//        $url = 'http://a4vn.com/media/catalog/product/cache/all/thumbnail/700x817/7b8fef0172c2eb72dd8fd366c999954c/5/_/5_36_120.jpg';
//        $result = Util::uploadImageFromUrl($url, 600, 600, 'abc');
//        p($result, 1);
        
    }
    
    public function sendoAction()
    {     
        $request = $this->getRequest();       
        $id = $this->params()->fromQuery('id', 0);
        $backUrl = $this->params()->fromQuery('u', '/');
        $data = Products::getDetail($id);
        if (empty($data)) {
            return $this->redirect()->toUrl($backUrl);
        }        
        if (array_intersect([15], $data['category_id'])) {
            $data['name'] = str_replace(['BL '], ['Balo Teen - Học sinh - Laptop '], $data['name']);
            $data['og_description'] = 'Mua ' . $data['name'] . ' chất lượng tại shop BALO HỌC SINH - TEEN trên sendo.vn, giao hàng tận nơi.';               
        } elseif (array_intersect([16], $data['category_id'])) {            
            $data['og_description'] = 'Mua ' . $data['name'] . ' chất lượng tại shop BALO HỌC SINH - TEEN trên sendo.vn, giao hàng tận nơi.';
        } else {
            $data['og_description'] = 'Mua ' . $data['name'] . ' chất lượng tại shop THỜI TRANG ZANADO trên sendo.vn, giao hàng tận nơi.';
        }               
        if (empty($data['meta_keyword'])) {
            $data['meta_keyword'] = implode(', ', array_merge(array($data['name']), $metaArea));
        }
        if (!empty($data['code'])) {
            $data['meta_keyword'] = $data['meta_keyword'] . ', ' . $data['code'];                
        }
        if (!empty($data['code_src'])) {
            $data['meta_keyword'] = $data['meta_keyword'] . ', ' . $data['code_src'];
        }
        if (empty($data['meta_description'])) {
            $data['meta_description'] = implode(PHP_EOL, array(
                'Mua ' . $data['name'] . ' chính hãng chất lượng tại ' . $_SERVER['SERVER_NAME'] . ', giao hàng tận nơi, với nhiều chương trình khuyến mãi...',                                        
                $data['short'],                    
            ));              
        }       
        $data['meta_title'] = array(
            $data['name']                
        );      
        $data['meta_title'] = implode(' - ', $data['meta_title']);
        $data['meta_title'] = preg_replace('!\s+!', ' ', $data['meta_title']);
        $data['meta_description'] = preg_replace('!\s+!', ' ', $data['meta_description']);
        $data['meta_image'] = !empty($data['url_image']) ? $data['url_image'] : '';
        if (!empty($data['image_facebook'])) {
            $data['meta_image'] = $data['image_facebook'];
        }        
        $this->setHead(array(
            'title' => $data['meta_title'],
            'meta_name' => array(
                'description' => $data['meta_description'],
                'keywords' => $data['meta_keyword'],              
                'classification' => !empty($data['categories'][0]['name']) ? $data['categories'][0]['name'] : '',
            ),
            'meta_property' => array(
                'og:title' => $data['meta_title'],
                'og:description' => $data['og_description'],
                'og:image' => $data['meta_image'],
                'og:image:width' => '200',
                'og:image:height' => '200',                
            ),               
        ));            
        return $this->getViewModel(array(
                'backUrl' => $backUrl,                
            )
        );
    }
}
