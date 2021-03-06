<?php
date_default_timezone_set('Asia/Saigon');
if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}
if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
    define('OUTPUT_TO_NULL', '');
} else {
    define('OUTPUT_TO_NULL', '> /dev/null &');
}
//include ('../../vendor/facebook/php-sdk-v4/src/Facebook/autoload.php');
include ('../../vendor/autoload.php');
include ('../../functions.php');

$env = 'development'; // development, production
$config = [
    'development' => [
        'timeout' => 30*60,       
        'base_uri' => 'http://api.vuongquocbalo.dev',    
        
        'facebook_app_id' => '304999106503304',
        'facebook_app_secret' => '939d6213f1a40680aa55877b0ccd7931',
        
        'google_app_id' => '1027124832421-00lmm9qstsa4umk76bgr2hpcsfu2kgo2.apps.googleusercontent.com',
        'google_app_secret' => 'EDfKgxjtBo-I_guWfg6y85YU',
        'google_app_redirect_uri' => 'http://vuongquocbalo.dev/glogin2',
        
        'img_domain' => 'http://img.vuongquocbalo.dev',
    ],
    'production' => [
        'timeout' => 30*60,
        'base_uri' => 'http://api.vuongquocbalo.com',   
        
        //'facebook_app_id' => '1679604478968266',
        //'facebook_app_secret' => '53bbe4bab920c2dd3bb83855a4e63a94',
        
        'facebook_app_id' => '575135192674647',
        'facebook_app_secret' => 'fdeb4ccfb7f28c96365cc6d91bebb6a4',
        
        'google_app_id' => '692650781994-mv3unhde1gf92i26s3qajrrbrs9hbsae.apps.googleusercontent.com',
        'google_app_secret' => 'kEAA8MNKgFbmhL3ZDU9U4eTp',
        'google_app_redirect_uri' => 'http://vuongquocbalo.com/glogin2',
        
        'img_domain' => 'http://img.vuongquocbalo.com',
    ]
];
$config = $config[$env];
$imgDomain = $config['img_domain'];
$docRoot = dirname(dirname(getcwd()));
$imgDir = implode(DS, [$docRoot, 'data', 'vuongquocbalo', 'img']);
$websiteId = 1;

function call($url, $param = array(), &$errors = null) {
	global $config, $websiteId;
	$method = 'post';
	if (isset($config[$url])) {
		if (is_array($config[$url])) {
			list($url, $method) = $config[$url];        
		} else {
			$url = $config[$url];
		}
	}
	try {		
        $param['website_id'] = $websiteId;
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
                unset($param[$name]);
            }                
        } //p($param, 1);
		$headers = array("Content-Type:multipart/form-data");
		$url = $config['base_uri'] . $url;
		$ch = curl_init();
		$options = array(
			CURLOPT_URL => $url,
			CURLOPT_HEADER => false,
			CURLOPT_POST => true,
			CURLOPT_HTTPHEADER => $headers,
			CURLOPT_POSTFIELDS => $param,
			CURLOPT_RETURNTRANSFER => true,
			//CURLOPT_SAFE_UPLOAD => false,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_TIMEOUT => $config['timeout'],
		);
		curl_setopt_array($ch, $options);
		$jsonResponse = curl_exec($ch);
		$errno = curl_errno($ch);
		if (empty($errno)) {
			curl_close($ch);
			$result = json_decode($jsonResponse, true);
            switch ($result['status']) {
                case 'OK';
                    return $result['results'];                   
                case 'ERROR_VALIDATION':                                         
                case 'ERROR':
                    $errors = $result['results'];    
                    //p($errors);
                    return false;
            }
            return $result;
		}
		if (!empty($ch)) {
			@curl_close($ch);
		}
	} catch (\Exception $e) {         
		print_r($e->getMessage());
	}
	return false;
}

function get_all_files($path, &$result = array()) {    
    if (is_dir($path)) {
        $files = glob($path . DS . '*');
        foreach ($files as $file) {         
            $file = get_all_files($file, $result);
            if ($file) {
                $result[] = $file;
            }
        }      
    } elseif (is_file($path) && file_exists($path) && basename($path) != 'empty') {
        return $path;
    }
    return null;
}

function rand_post_time() {
    return rand(3*60, 6*60);
}

function batch_info($message = '') {
    echo PHP_EOL . '[' . date('Y-m-d H:i:s') . '] ' . $message;
}

$userIds = [
    '10206637393356602', // Thai Lai
    '129881887426347', // vuongquocbalo.com
    '835521976592060', // Ngoc Nguyen My
    '1723524741251993', // Duc Tin
    '126728971080640', // kenstore2016@gmail.com https://www.facebook.com/kinhdothoitrang.vn
    '107846242974801', // fb.huean@outlook.com
    '127283041026900', // fb.ngocai@gmail.com
    '116860312071059', // fb.hoaian@gmail.com
];
        
$groupIds = [
	'794951187227341', 	// Adm: fb.ngocai@gmail.com, https://www.facebook.com/groups/chosaletonghopbmt/	
	'487699031377171', 	// Adm: fb.huean@outlook.com, https://www.facebook.com/groups/muabansaigoncholon/
	'952553334783243', 	// Chợ online Khang Điền Q.9 https://www.facebook.com/groups/928701673904347/
	'928701673904347', 	// Adm: fb.huean@outlook.com, Chợ sinh viên giá rẻ https://www.facebook.com/groups/928701673904347/
	'1648395082048459', // Hội mua bán của các mẹ ở Gò vấp https://www.facebook.com/groups/1648395082048459/
	'297906577042130', 	// Hội những người mê kinh doanh online
	'519581824789114', 	// Adm: kenstore2016@gmail.com, https://www.facebook.com/groups/choraovatvaquangcao/, CHỢ RAO VẶT & QUẢNG CÁO ONLINE
	'209799659176359', 	// Rao vặt linh tinh
];

$tagIds = [
    '10206637393356602', // Thai Lai
    '125965971158216', // Ken Ken https://www.facebook.com/thaibaodat
    '129881887426347', // Balo Đẹp
    '835521976592060', // Ngoc Nguyen My
    '1723524741251993', // Duc Tin
    '490650357797276', // Nguyễn Huỳnh Liên
    '277249729330270', // Thảo GD
    '1021398451274096', // Thuỷ Gumiho
    '126728971080640', // https://www.facebook.com/kinhdothoitrang.vn
];

$fb = new \Facebook\Facebook([
    'app_id' => $config['facebook_app_id'],
    'app_secret' => $config['facebook_app_secret'],
    'default_graph_version' => 'v2.6',
    //'default_access_token' => $user['access_token'], // optional
]);

function uploadUnpublishedPhoto($data, $accessToken, &$errorMessage = '') {
    global $fb;
    try {
        $data['published'] = false;
        $response = $fb->post("/me/photos", $data, $accessToken);
        $graphNode = $response->getGraphNode();
        if (!empty($graphNode['id'])) {
            return $graphNode['id'];
        }
    } catch (\Facebook\Exceptions\FacebookResponseException $e) {
        $errorMessage = $e->getMessage(); 
		batch_info('1-' . $errorMessage);
		exit;         
    } catch (\Facebook\Exceptions\FacebookSDKException $e) {
        $errorMessage = $e->getMessage(); 
		batch_info('2-' . $errorMessage);	
		exit;   
    } catch (\Exception $e) {
        $errorMessage = $e->getMessage(); 
		batch_info('3-' . $errorMessage);
    }
    return false;
}  


function postToWall($data, $accessToken, &$errorMessage = '') {
    global $fb;
    try {
        $response = $fb->post("/me/feed", $data, $accessToken);
        $graphNode = $response->getGraphNode();
        if (!empty($graphNode['id'])) {                   
            return $graphNode['id'];
        }
    } catch (Facebook\Exceptions\FacebookResponseException $e) {
        $errorMessage = $e->getMessage(); 
		batch_info($errorMessage);	
		exit;
    } catch (Facebook\Exceptions\FacebookSDKException $e) {
        $errorMessage = $e->getMessage();
		batch_info($errorMessage);	
		exit;
    } catch (\Exception $e) {
        $errorMessage = $e->getMessage();
    }
    return false;
}

function postToGroup($groupId, $data, $accessToken, &$errorMessage = '') {
    global $fb;
    try {
        $response = $fb->post("/{$groupId}/feed", $data, $accessToken);
        $graphNode = $response->getGraphNode();
        if (!empty($graphNode['id'])) {                   
            return $graphNode['id'];
        }
    } catch (Facebook\Exceptions\FacebookResponseException $e) {
        $errorMessage = $e->getMessage(); 
		batch_info($groupId . ': ' . $errorMessage);	
		exit;
    } catch (Facebook\Exceptions\FacebookSDKException $e) {
        $errorMessage = $e->getMessage();
		batch_info($groupId . ': ' . $errorMessage);	
		exit;
    } catch (\Exception $e) {
        batch_info($groupId . ': ' . $errorMessage);	
    }
    return false;
}

function postToPage($pageId, $data, $accessToken, &$errorMessage = '') {
    global $fb;    
    try {
        $pageResponse = $fb->get('/' . $pageId . '?fields=access_token', $accessToken);
        if ($pageResponse) {   
            $graphPage = $pageResponse->getGraphPage();
            $pageAccessToken = $graphPage['access_token'];                
            $response = $fb->post("/{$pageId}/feed", $data, $pageAccessToken);
            $graphNode = $response->getGraphNode();
            if (!empty($graphNode['id'])) {
                return $graphNode['id'];
            }
        }        
    } catch (Facebook\Exceptions\FacebookResponseException $e) {
        $errorMessage = $e->getMessage(); 
		batch_info($pageId . ': ' . $errorMessage);
		exit;
    } catch (Facebook\Exceptions\FacebookSDKException $e) {
        $errorMessage = $e->getMessage();
		batch_info($pageId . ': ' . $errorMessage);
		exit;
    } catch (\Exception $e) {
        $errorMessage = $e->getMessage();
        batch_info($pageId . ': ' . $errorMessage);
    }
    return false;
}

function commentToPost($postId, $data, $accessToken, &$errorMessage = '', &$errorCode = '') {
    global $fb;
    try {
        if (empty($data['message'])) {
            $data['message'] = 'vuongquocbalo.com';
        }       
        $response = $fb->post("/{$postId}/comments", $data, $accessToken);
        $graphNode = $response->getGraphNode();
        if (!empty($graphNode['id'])) {                   
            return $graphNode['id'];
        }
    } catch (Facebook\Exceptions\FacebookResponseException $e) {
        $errorCode = $e->getCode(); 
        $errorMessage = $e->getMessage(); 
        batch_info("{$postId}:{$errorCode}:{$errorMessage}");
    } catch (Facebook\Exceptions\FacebookSDKException $e) {
        $errorCode = $e->getCode(); 
        $errorMessage = $e->getMessage();
		batch_info("{$postId}:{$errorCode}:{$errorMessage}");
    } catch (\Exception $e) {
        $errorCode = $e->getCode(); 
        $errorMessage = $e->getMessage();
        batch_info("{$postId}:{$errorCode}:{$errorMessage}");	
    }
    return false;
}

function meCreateAlbum($data, $accessToken, &$errorMessage = '') {
    global $fb;
    try {
        $response = $fb->post("/me/albums", $data, $accessToken);
        $graphNode = $response->getGraphNode();
        if (!empty($graphNode['id'])) {                   
            return $graphNode['id'];
        }
    } catch (Facebook\Exceptions\FacebookResponseException $e) {
        $errorMessage = $e->getMessage(); 
		batch_info($errorMessage);	
		exit;
    } catch (Facebook\Exceptions\FacebookSDKException $e) {
        $errorMessage = $e->getMessage();
		batch_info($errorMessage);	
		exit;
    } catch (\Exception $e) {
        $errorMessage = $e->getMessage();
    }
    return false;
}

function groupCreateAlbum($groupId, $data, $accessToken, &$errorMessage = '') {
    global $fb;
    try {
        $response = $fb->post("/{$groupId}/albums", $data, $accessToken);
        $graphNode = $response->getGraphNode();
        if (!empty($graphNode['id'])) {                   
            return $graphNode['id'];
        }
    } catch (Facebook\Exceptions\FacebookResponseException $e) {
        $errorMessage = $e->getMessage(); 
		batch_info($errorMessage);	
		exit;
    } catch (Facebook\Exceptions\FacebookSDKException $e) {
        $errorMessage = $e->getMessage();
		batch_info($errorMessage);	
		exit;
    } catch (\Exception $e) {
        $errorMessage = $e->getMessage();
    }
    return false;
}

function addPhotoToAlbum($albumId, $data, $accessToken, &$errorMessage = '') {
    global $fb;
    try {
        $response = $fb->post("/{$albumId}/photos", $data, $accessToken);
        $graphNode = $response->getGraphNode();
        if (!empty($graphNode['id'])) {                   
            return $graphNode['id'];
        }
    } catch (Facebook\Exceptions\FacebookResponseException $e) {
        $errorMessage = $e->getMessage(); 
		batch_info($errorMessage);	
		exit;
    } catch (Facebook\Exceptions\FacebookSDKException $e) {
        $errorMessage = $e->getMessage();
		batch_info($errorMessage);	
		exit;
    } catch (\Exception $e) {
        $errorMessage = $e->getMessage();
    }
    return false;
}

function postToBlog($blogId, $data, $accessToken, &$errorMessage = '') {     
    global $config;
    try {        
        $scopes = implode(' ' , [
            \Google_Service_Oauth2::USERINFO_EMAIL, 
            \Google_Service_Blogger::BLOGGER_READONLY,
            \Google_Service_Blogger::BLOGGER
        ]);
        $client = new \Google_Client();
        $client->setClientId($config['google_app_id']);
        $client->setClientSecret($config['google_app_secret']);
        $client->setRedirectUri($config['google_app_redirect_uri']);
        $client->addScope($scopes);    
        $client->setAccessToken($accessToken);
        $service = new \Google_Service_Blogger($client); 
        $bloggerPost = new \Google_Service_Blogger_Post();
        $bloggerPost->setTitle($data['name']);
        $bloggerPost->setContent($data['content']);            
        $bloggerPost->setLabels($data['labels']);                  
        $post = $service->posts->insert($blogId, $bloggerPost);
        if ($postId = $post->getId()) {
            return $postId;
        }            
    } catch (\Exception $e) {
        $errorMessage = $e->getMessage();
    }
    return false;
}