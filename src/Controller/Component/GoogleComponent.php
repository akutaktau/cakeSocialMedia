<?php
/**
 * CakeSocial GooglePlus Component
 * 
 * @author Jasdy Syarman
 * @copyright (c) 2017 syarman.com/soft
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
		$session = $this->request->session();
        $returnConfig = $this->getConfig();
		
        if (!$session->check('google.token')) {
			$this->getConfig();
		}

		$this->gClient = new \Google_Client();
        $this->gClient->setApplicationName('RakanMET');
        $this->gClient->setClientId($returnConfig['google']['app_id']);
        $this->gClient->setClientSecret($returnConfig['google']['app_secret']);
        $this->gClient->setRedirectUri($returnConfig['google']['callback']);
        $this->gClient->addScope(\Google_Service_Plus::PLUS_LOGIN);
        $this->gClient->addScope(\Google_Service_Plus::PLUS_ME);
        $this->gClient->addScope('email');
        
        $loginUrl = $this->gClient->createAuthUrl();
                
        return $loginUrl;
        
	}
	
	public function getConfig() {
	   $this->convertUrl();
	  
	   return $this->_config;
	}
	
	public function isLogin() {
		$returnConfig = $this->getConfig();
		$session = $this->request->session();
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
        
        $this->gClient->addScope(\Google_Service_Plus::PLUS_LOGIN);
        $this->gClient->addScope(\Google_Service_Plus::PLUS_ME);
        $this->gClient->addScope('email');
        $this->gClient->addScope(\Google_Service_Plus::USERINFO_PROFILE);
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
        $session = $this->request->session();
        $returnConfig = $this->getConfig();
        
        $this->gClient = new \Google_Client();
        $this->gClient->setApplicationName('RakanMET');
        $this->gClient->setClientId($returnConfig['google']['app_id']);
        $this->gClient->setClientSecret($returnConfig['google']['app_secret']);
        $this->gClient->setRedirectUri($returnConfig['google']['callback']);
        
        $this->gClient->addScope(\Google_Service_Plus::PLUS_LOGIN);
        $this->gClient->addScope(\Google_Service_Plus::PLUS_ME);
        $this->gClient->addScope('email');
        $this->gClient->addScope(\Google_Service_Plus::USERINFO_PROFILE);
        $plus = new \Google_Service_Plus($this->gClient);
        
        if(isset($_GET['code'])){
            $this->gClient->authenticate($_GET['code']);
            //$session->write('google.token',$this->gClient->getAccessToken());
            
        }

        if ($session->check('google.token')) {
            $this->gClient->setAccessToken($session->read('google.token'));
         
        }
        
        if ($this->gClient->getAccessToken()) {
            
            $me = $plus->people->get('me');
            
            $PlusPersonName = $me->getName();
           
            $PlusPersonImage = $me->getImage();
            $imagePath = $PlusPersonImage->getUrl();
            
            $userId = $me->id;
            
            $displayName = $me->displayName;
            
          
            $PlusPersonEMails = $me->getEmails();
            
            $i = 0;
            foreach ($PlusPersonEMails as $emails) {
                $email[$i] = array(
                    'email' => $emails->value,
                    'type' => $emails->type
                );
                $i++;
            }
            
            $user = [
                'id' => $userId,
                'displayName' => $displayName,
                'image' => $imagePath,
                'personName' => array(
                    'givenName' => $PlusPersonName->getGivenName(), 
                    'familyName' => $PlusPersonName->getFamilyName()
                ),
                'email' => $email,
            ];
		}
        else {
            
            $user = [];
        }
        
        return $user;
    }

}
