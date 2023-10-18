<?php

namespace core\base\settings;

use core\base\controller\Singleton;

class Settings 
{
	use Singleton;

	private $routes = [
		'settings' => [
			'path' => 'core/base/settings/'
		],
		'plugins' => [
			'path' => 'core/plugins/',
			'hrUrl' => false,
			'dir' => false,
		],
		'user' => [
			'path' => 'core/user/controller/',
			'hrUrl' => true,
			'routes' => [
				
			]
		],
		'default' => [
			'controller' => 'IndexController',
			'inputMethod' => 'inputData',
			'outputMethod' => 'outputData'
		]
	];

	static public function get($property) {
		return self::instance()->$property;
	}
}