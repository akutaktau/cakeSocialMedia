<?php
/**
 * CakeSocial Google Component
 * 
 * @author Jasdy Syarman
 * @copyright (c) 2020 syarman.com/soft
 * @license MIT
 */
namespace CakeSocialMedia\Controller\Component;
 
use Cake\Controller\Component;
use Cake\Controller\ComponentRegistry;
use Cake\Core\Configure;
use Cake\Event\Event;
use Cake\ORM\TableRegistry;
use Cake\Routing\Router;

 require_once ROOT . DS . 'vendor'.DS.'autoload.php';
/**
 * Google component
 */
class GoogleComponent extends Component {

	
	protected $_config = null;
	protected $Controller = null;
	protected $google_oauthV2 = null;
	protected $gclient = null;
	
	public function initialize(array $config) {		
       
        if (!Configure::check('CakeSocial')) {
			Configure::load('CakeSocial');
            	}
		if(Configure::read('CakeSocial')) {
			$this->_config = $this->config();
			$this->_config = Configure::read('CakeSocial');
			
			parent::initialize($this->_config);
			$this->Controller = $this->_registry->getController();
			
		}
	}
   
	public function login() {
		$session = $this->Controller->getRequest()->getSession();
        $returnConfig = $this->getConfig();
		
        if (!$session->check('google.token')) {
			$this->getConfig();
		}

		$this->gClient = new \Google_Client();
        $this->gClient->setApplicationName('RakanMET');
        $this->gClient->setClientId($returnConfig['google']['app_id']);
        $this->gClient->setClientSecret($returnConfig['google']['app_secret']);
        $this->gClient->setRedirectUri($returnConfig['google']['callback']);
       // $this->gClient->addScope(\Google_Service_Plus::PLUS_LOGIN);
       // $this->gClient->addScope(\Google_Service_Plus::PLUS_ME);
        $this->gClient->addScope('email');
        $this->gClient->addScope('profile');
        
        $loginUrl = $this->gClient->createAuthUrl();
        echo $loginUrl;exit;        
        return $loginUrl;
        
	}
	
	public function getConfig($key = NULL, $default = NULL) {
	   $this->convertUrl();
	  
	   return $this->_config;
	}
	
	public function isLogin() {
		$returnConfig = $this->getConfig();
		$session = $this->Controller->getRequest()->getSession();
		if(!session_id()) {
			session_start();
		}
        
        if(isset($_GET['code'])){
            //$returnConfig = $this->getConfig();
        
            $this->gClient = new \Google_Client();
            $this->gClient->setApplicationName('RakanMET');
            $this->gClient->setClientId($returnConfig['google']['app_id']);
            $this->gClient->setClientSecret($returnConfig['google']['app_secret']);
            $this->gClient->setRedirectUri($returnConfig['google']['callback']);
            
            //$this->gClient->addScope(\Google_Service_Plus::PLUS_LOGIN);
            //$this->gClient->addScope(\Google_Service_Plus::PLUS_ME);
            $this->gClient->addScope('email');
            $this->gClient->addScope('profile');
            //$this->gClient->addScope(\Google_Service_Plus::USERINFO_PROFILE);
            //$plus = new \Google_Service_Plus($this->gClient);
            $this->gClient->authenticate($_GET['code']);
            $session->write('google.token',$this->gClient->getAccessToken());
            
        }
		
        if ($session->check('google.token'))       
            return true;
        else      
            return false;
        
	}
	
	private function convertUrl() {
		$this->_config['google']['callback'] = Router::url($this->_config['google']['callback'],true);
		Configure::write('google.callback',$this->_config['google']['callback']);
	}
    
    public function getUserDetail() {
        if(!session_id()) {
			session_start();
		}
        $session = $this->Controller->getRequest()->getSession();
        $returnConfig = $this->getConfig();
        
        $this->gClient = new \Google_Client();
        $this->gClient->setApplicationName('RakanMET');
        $this->gClient->setClientId($returnConfig['google']['app_id']);
        $this->gClient->setClientSecret($returnConfig['google']['app_secret']);
        $this->gClient->setRedirectUri($returnConfig['google']['callback']);
       
        $this->gClient->addScope('email');
        $this->gClient->addScope('profile');
        
        $google_oauthV2 = new \Google_Service_Oauth2($this->gClient);
        if(isset($token['error'])) {
           echo  $token['error']; exit;
        }
        if(isset($_GET['code'])){
            $this->gClient->fetchAccessTokenWithAuthCode($_GET['code']);
        
        }

        if ($session->check('google.token')) {
            $this->gClient->setAccessToken($session->read('google.token'));
         
        }
        
        if ($this->gClient->getAccessToken()) {
            
            $me = $google_oauthV2->userinfo->get();
                        
            $user = [
                'id' => $me['id'],
                'displayName' => $me['name'],
                'image' => $me['picture'],
                'personName' => array(
                    'givenName' => $me['given_name'], 
                    'familyName' => $me['family_name']
                ),
                'email' => $me['email'],
            ];
		}
        else {
            
            $user = [];
        }
        
        return $user;
    }

}
