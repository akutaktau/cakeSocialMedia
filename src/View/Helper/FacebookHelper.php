<?php
namespace CakeSocial\View\Helper;

use Cake\View\Helper;
use Cake\View\View;
use Cake\Core\Configure;
use Cake\Routing\Router;
use Facebook\FacebookSession;
use Facebook\FacebookRedirectLoginHelper;
use Facebook\FacebookRequest;
use Facebook\FacebookResponse;
use Facebook\FacebookSDKException;
use Facebook\FacebookRequestException;
use Facebook\FacebookAuthorizationException;
use Facebook\GraphObject;
use Facebook\GraphUser;
use Facebook\GraphSessionInfo;

/**
 * Graph helper
 */
class FacebookHelper extends Helper {

    public $helpers = ['Html'];
    public $appId = null;
    public $redirectUrl = null;
    public $appScope = null;
    protected $_configs = null;

    /**
     * Default configuration.
     *
     * @var array
     */
    protected $_defaultConfig = [
	'app_id' => '',
	'callback' => '',
	'app_secret' => ''
    ];

    public function __construct(View $view, $config = [])
    {
		parent::__construct($view, $config);
		
		$this->_configs = $this->config();
		$this->appId = $this->_configs['app_id'];
		$this->appSecret = $this->_configs['app_secret'];
		$this->redirectUrl = $this->_configs['callback'];
		$this->appScope = 'email';//$this->_configs['app_scope'];
    }

    /**
     * Create a Facebook Login Link
     * 
     * @param type $options
     * @return type
     */
    public function loginLink($options = [])
    {
		$id = (isset($options['id']) ? $options['id'] : 'FB-login-button');
		$class = (isset($options['class']) ? $options['class'] : 'FB-login-button');
		$title = (isset($options['title']) ? $options['title'] : 'Login with Facebook');
		$style = (isset($options['style']) ? $options['style'] : '');
		$text = (isset($options['text']) ? $options['text'] : 'FB Login');
	
		$Facebook = new \Facebook\Facebook([
			'app_id' => $this->appId,
			'app_secret' => $this->appSecret,
			'default_graph_version' => 'v2.2'
		]);
		
		$helper = $Facebook->getRedirectLoginHelper();

		$permissions = ['email']; // Optional permissions
		$callback = $this->convertUrl($this->redirectUrl);
		$loginUrl = $helper->getLoginUrl($callback, $permissions);
		
		return '<a id="' . $id . '" class="' . $class . '" href="' . htmlspecialchars($loginUrl) . '" title="' . $title . '" style="' . $style . '">'.$text.'</a>';
    }
    
    /**
     * Creates Facebook native button
     * 
     * @param type $options
     * @return type
     */
    public function loginButton($options = []){	
	$options = array_merge([
	    'auto-logout-link' => false,
	    'max-rows' => 1,
	    'onlogin' => null,
	    'scope' => $this->appScope,
	    'size' => 'small',
	    'show-faces' => false,
	    'default-audience' => 'friends'
	], $options);
	
	return <<<EOT
	<div class="fb-login-button" 
	    data-auto-logout-link="{$options['auto-logout-link']}" 
		data-max-rows="{$options['max-rows']}" 
		onlogin="{$options['onlogin']}" 
		data-scope="{$options['scope']}" 
		data-size="{$options['size']}" 
		data-show-faces="{$options['show-faces']}" 
		data-default-audience="{$options['default-audience']}"></div>
EOT;
    }

    public function initJs()
    {
	return <<<EOT
	<div id="fb-root"></div>
        <script>
      window.fbAsyncInit = function() {
        FB.init({
          appId      : '$this->appId',
          xfbml      : true,
          version    : 'v2.5'
        });
      };

      (function(d, s, id){
         var js, fjs = d.getElementsByTagName(s)[0];
         if (d.getElementById(id)) {return;}
         js = d.createElement(s); js.id = id;
         js.src = "//connect.facebook.net/en_US/sdk.js";
         fjs.parentNode.insertBefore(js, fjs);
       }(document, 'script', 'facebook-jssdk'));
    </script>
EOT;
    }

    private function convertUrl($url) {
		
		return $this->redirectUrl = Router::url($url,true);
	}

}
