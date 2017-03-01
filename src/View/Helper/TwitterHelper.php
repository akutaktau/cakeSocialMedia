<?php
namespace CakeSocialMedia\View\Helper;

use Cake\View\Helper;
use Cake\View\View;

/**
 * Twitter helper
 */
class TwitterHelper extends Helper
{

	/**
	 * Default configuration.
	 *
	 * @var array
	 */
	protected $_defaultConfig = [
		'app_id' => '',
		'app_secret' => '',
		'callback' => '',
	];

}
