<?php
/**
 * CakeSocial Facebook Component
 * 
 * @author Jasdy Syarman
 * @copyright (c) 2016 syarman.com/soft
 * @license MIT
 */
namespace CakeSocial\Controller\Component;

use Cake\Controller\Component;
// use Cake\Controller\ComponentRegistry;
// use Cake\Core\Configure;
// use Cake\Event\Event;
// use Cake\ORM\TableRegistry;
// use Cake\Routing\Router;
// use Facebook\FacebookSession;
// use Facebook\FacebookRedirectLoginHelper;
// use Facebook\FacebookRequest;
// use Facebook\FacebookResponse;
// use Facebook\GraphUser;
// use Facebook\FacebookSDKException;
// use Facebook\FacebookRequestException;
// use Facebook\FacebookAuthorizationException;

/**
 * Facebook component
 */
class FacebookComponent extends Component {

   public function initialize() {
		
		parent::initialize();
		if(Configure::read('CakeSocial')) {
			$config = Configure::read('CakeSocial');
			
			pr($config);
		}
   }

}
