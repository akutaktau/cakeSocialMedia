<?php
/**
 * CakeSocial Twitter Component
 * 
 * @author Hacrone Eppy & Jasdy Syarman
 * @copyright (c) 2016 syarman.com/soft
 * @license MIT
 */
namespace CakeSocial\Controller\Component;

use Cake\Controller\Component;
use Cake\Controller\ComponentRegistry;
use Cake\Core\Configure;
use Cake\Event\Event;
use Cake\ORM\TableRegistry;
use Cake\Routing\Router;
use Abraham\TwitterOAuth\TwitterOAuth;

/**
 * Twitter component
 */
class TwitterComponent extends Component
{

    /**
     * Default configuration.
     *
     * @var array
     */
    protected $_defaultConfig = [];
    protected $authUrl;
    public $User;

	public function initialize(array $config) {	
		$this->loadConfig();
	}

	public function loadConfig() {
		if (!Configure::check('CakeSocial')) {
			Configure::load('CakeSocial');
		}

		if(Configure::check('CakeSocial')) {
			$this->_config = $this->config();
			$this->_config = Configure::read('CakeSocial');
			
			parent::initialize($this->_config);
			$this->Controller = $this->_registry->getController();
			$this->Controller->helpers = [
				'CakeSocial.Twitter' => [
					'enable' => $this->_config['twitter']['enable'],
					'app_id' => $this->_config['twitter']['app_id'],
					'app_secret' => $this->_config['twitter']['app_secret'],
					'callback' => $this->_config['twitter']['callback']
				]
			];
		}

		$returnConfig = $this->getConfig();
		return $returnConfig;
	}

	public function getConfig() {
	   $this->convertUrl();
	   return $this->_config;
	}

	public function login() {
		$session = $this->request->session();
		if (!$session->check('twitter')) {
			$this->loadConfig();
		}

		if (!$session->check('twitter.access_token')) {
			$connect = new TwitterOAuth(
				$this->_config['twitter']['app_id'],
				$this->_config['twitter']['app_secret']
			);
			$request_token = $connect->oauth('oauth/request_token', ['oauth_callback' => $this->_config['twitter']['callback']]);

			if ($request_token && $request_token['oauth_callback_confirmed'] == true) {
				$session->write('twitter.oauth_token', $request_token['oauth_token']);
				$session->write('twitter.oauth_token_secret', $request_token['oauth_token_secret']);
				$loginUrl = $connect->url('oauth/authorize', array('oauth_token' => $request_token['oauth_token']));
				$this->authUrl = $loginUrl;
				return true; // return loginUrl used to redirect
			} else {
				// produce error page / anything related
			}
		} else {
			// already login
		}

		return false;
	}

	public function callback() {
		$session = $this->request->session();
		if ($this->request->query('oauth_verifier') && $this->request->query('oauth_token') && $this->request->query('oauth_token') == $session->read('twitter.oauth_token')) {
			$request_token = [];
			$request_token['oauth_token'] = $session->read('twitter.oauth_token');
			$request_token['oauth_token_secret'] = $session->read('twitter.oauth_token_secret');

			$connect = new TwitterOAuth($this->getAppId(), $this->getAppSecret(), $request_token['oauth_token'], $request_token['oauth_token_secret']);
			$access_token = $connect->oauth("oauth/access_token", array("oauth_verifier" => $this->request->query('oauth_verifier')));

			$session->write('twitter.access_token', $access_token);
			return true;
		} elseif ($this->request->query('denied')) {
			// user deny access
		}

		// not verified or token is invalid
		return false;
	}

	public function validate() {
		$session = $this->request->session();

		if ($session->check('twitter.access_token')) {
			$access_token = $session->read('twitter.access_token');
			$connect = new TwitterOAuth($this->getAppId(), $this->getAppSecret(), $access_token['oauth_token'], $access_token['oauth_token_secret']);
			// try to get user credentials
			$user = $connect->get("account/verify_credentials", ['include_email' => 'true']);
			// lack of on app verification (xsure nk verify ape) mcm ni je pun cukup kot..
			
			// if get err response
			if (!empty($user->errors)) {
				if ($user->errors[0]->code) {
					debug($user->errors[0]->code);
					debug($user->errors[0]->message);
				}
				return false;
			}

			$this->email = $user->email;
			return true;
		} else {
			// debug('no access token');
		}
		
		return false;
	}

	public function getUserDetail() {
		$session = $this->request->session();
		if ($session->check('twitter.access_token')) {
			$access_token = $session->read('twitter.access_token');

			if ($this->User) {
				return $this->User;
			}

			$connect = new TwitterOAuth($this->getAppId(), $this->getAppSecret(), $access_token['oauth_token'], $access_token['oauth_token_secret']);
			$user = $connect->get("account/verify_credentials", ['include_email' => 'true']);

			// if get err response
			if (!empty($user->errors)) {
				if ($user->errors[0]->code) {
					debug($user->errors[0]->code);
					debug($user->errors[0]->message);
				}
				return false;
			}

			$this->User = $user;
			return $this->User;
		}

		return null;
	}

	public function sendPost(array $args) {
		// general method for send status update
		$session = $this->request->session();
		$access_token = $session->read('twitter.access_token');
		if ($session->check('twitter.access_token')) {
			$connect = new TwitterOAuth($this->getAppId(), $this->getAppSecret(), $access_token['oauth_token'], $access_token['oauth_token_secret']);
			/**
			 * Parameters
			 * status                  (required)
			 * in_reply_to_status_id   (optional)
			 * possibly_sensitive      (optional)
			 * lat                     (optional)
			 * long                    (optional)
			 * place_id                (optional)
			 * display_coordinates     (optional)
			 * trim_user               (optional)
			 * in_reply_to_status_id   (optional)
			 * media_ids               (optional)
			 * 
			 *** Read more: https://dev.twitter.com/rest/reference/post/statuses/update
			 */
			if (array_key_exists('status', $args)) {
			 	$post = $connect->post("statuses/update", $args);
				if (!empty($post->created_at) && !empty($post->text) && $post->text == $args['status']) {
					return true;
				}
			} else {
				// status is required
				return false;
			}
		}

		return false;
	}

	public function tweetStatus($tweet) {
		// for normal tweet
		return $this->sendPost(['status' => $tweet]);
	}

	public function getAppId() {
		if (empty($this->_config['twitter']['app_id'])) {
			$this->loadConfig();
		}
		return $this->_config['twitter']['app_id'];
	}

	public function getAppSecret() {
		if (empty($this->_config['twitter']['app_secret'])) {
			$this->loadConfig();
		}
		return $this->_config['twitter']['app_secret'];
	}

	public function getConsumerKey() {
		// alias getAppId()
		return $this->getAppId();
	}

	public function getConsumerSecret() {
		// alias getAppSecret()
		return $this->getAppSecret();
	}

	private function convertUrl() {
		$this->_config['twitter']['callback'] = Router::url($this->_config['twitter']['callback'],true);
		Configure::write('twitter.callback', $this->_config['twitter']['callback']);
	}
}
