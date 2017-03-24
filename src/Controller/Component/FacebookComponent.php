<?php
/**
 * CakeSocial Facebook Component
 * 
 * @author Jasdy Syarman
 * @copyright (c) 2016 syarman.com/soft
 * @license MIT
 */
namespace CakeSocialMedia\Controller\Component;

use Cake\Controller\Component;
use Cake\Controller\ComponentRegistry;
use Cake\Core\Configure;
use Cake\Event\Event;
use Cake\ORM\TableRegistry;
use Cake\Routing\Router;
use Facebook\FacebookSession;
use Facebook\FacebookRedirectLoginHelper;
use Facebook\FacebookRequest;
use Facebook\FacebookResponse;
use Facebook\GraphUser;
use Facebook\FacebookSDKException;
use Facebook\FacebookRequestException;
use Facebook\FacebookAuthorizationException;

/**
 * Facebook component
 */
class FacebookComponent extends Component {

	
	protected $_config = null;
	protected $Controller = null;
	protected $version = 'v2.4';
	
	public function initialize(array $config) {	
		if (!Configure::check('CakeSocial')) {
			Configure::load('CakeSocial');
            	}
		if(Configure::read('CakeSocial')) {
			$this->_config = $this->config();
			$this->_config = Configure::read('CakeSocial');
			
			parent::initialize($this->_config);
			$this->Controller = $this->_registry->getController();
			$this->Controller->helpers = [
				'CakeSocialMedia.Facebook' => [
					
					'enable' => $this->_config['facebook']['enable'],
					'app_id' => $this->_config['facebook']['app_id'],
					'app_secret' => $this->_config['facebook']['app_secret'],
					'callback' => $this->_config['facebook']['callback'],
					'default_graph_version' => $this->version
					]
				
			];
		}
	}
   
	public function login() {
		$returnConfig = $this->getConfig();
		
		$Facebook = new \Facebook\Facebook([
			'app_id' => $returnConfig['facebook']['app_id'],
			'app_secret' => $returnConfig['facebook']['app_secret'],
			'default_graph_version' => $this->version
		]);
		
		$helper = $Facebook->getRedirectLoginHelper();

		$permissions = ['email']; // Optional permissions
		$loginUrl = $helper->getLoginUrl($returnConfig['facebook']['callback'], $permissions);
		
		return $loginUrl;
	}
	
	public function getConfig() {
	   $this->convertUrl();
	  
	   return $this->_config;
	}
	
	public function isLogin() {
		$returnConfig = $this->getConfig();
		
		if(!session_id()) {
			session_start();
		}
		
		/**
		 * Copied from facebook
		 */
		
		$Facebook = new \Facebook\Facebook([
			'app_id' => $returnConfig['facebook']['app_id'],
			'app_secret' => $returnConfig['facebook']['app_secret'],
			'default_graph_version' => $this->version
		]);
		
		$helper = $Facebook->getRedirectLoginHelper();
        if (isset($_GET['state'])) {
            $helper->getPersistentDataHandler()->set('state', $_GET['state']);
            
            $_SESSION['state'] = $_GET['state'];
        }
        
		try {
		  $accessToken = $helper->getAccessToken();
		} catch(Facebook\Exceptions\FacebookResponseException $e) {
		  // When Graph returns an error
		  echo 'Graph returned an error: ' . $e->getMessage();
		  exit;
		} catch(Facebook\Exceptions\FacebookSDKException $e) {
		  // When validation fails or other local issues
		  echo 'Facebook SDK returned an error: ' . $e->getMessage();
		  exit;
		}

		if (! isset($accessToken)) {
		  if ($helper->getError()) {
			header('HTTP/1.0 401 Unauthorized');
			echo "Error: " . $helper->getError() . "\n";
			echo "Error Code: " . $helper->getErrorCode() . "\n";
			echo "Error Reason: " . $helper->getErrorReason() . "\n";
			echo "Error Description: " . $helper->getErrorDescription() . "\n";
		  } else {
			header('HTTP/1.0 400 Bad Request');
			echo 'Bad request';
		  }
		  return false;
		}
		else {
			
			$oAuth2Client = $Facebook->getOAuth2Client();

			// Get the access token metadata from /debug_token
			$tokenMetadata = $oAuth2Client->debugToken($accessToken);
			
			// Validation (these will throw FacebookSDKException's when they fail)
			$tokenMetadata->validateAppId($returnConfig['facebook']['app_id']); 
			
			// If you know the user ID this access token belongs to, you can validate it here
			//$tokenMetadata->validateUserId('123');
			$tokenMetadata->validateExpiration();

			if (! $accessToken->isLongLived()) {
			  //Exchanges a short-lived access token for a long-lived one
			  try {
				$accessToken = $oAuth2Client->getLongLivedAccessToken($accessToken);
			  } catch (Facebook\Exceptions\FacebookSDKException $e) {
				echo "<p>Error getting long-lived access token: " . $helper->getMessage() . "</p>\n\n";
				exit;
			  }

			}

			$_SESSION['fb_access_token'] = (string) $accessToken;
			return true;
		}
		
	}
	
	private function convertUrl() {
		$this->_config['facebook']['callback'] = Router::url($this->_config['facebook']['callback'],true);
		Configure::write('facebook.callback',$this->_config['facebook']['callback']);
	}
    
    public function getUserDetail() {
        if(!session_id()) {
			session_start();
		}
        $returnConfig = $this->getConfig();
        
        $Facebook = new \Facebook\Facebook([
		 	'app_id' => $returnConfig['facebook']['app_id'],
			'app_secret' => $returnConfig['facebook']['app_secret'],
			'default_graph_version' => $this->version,
		]);
        $helper = $Facebook->getRedirectLoginHelper();
        
        try {
          // Returns a `Facebook\FacebookResponse` object
          $response = $Facebook->get('/me?fields=id,name,email', $_SESSION['fb_access_token']);
          
        } catch(Facebook\Exceptions\FacebookResponseException $e) {
          echo 'Graph returned an error: ' . $e->getMessage();
          exit;
        } catch(Facebook\Exceptions\FacebookSDKException $e) {
          echo 'Facebook SDK returned an error: ' . $e->getMessage();
          exit;
        } 

        $user = $response->getGraphUser();
       
        return $user;
    }

}
