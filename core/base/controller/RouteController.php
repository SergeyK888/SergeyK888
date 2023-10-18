<?php
namespace core\base\controller;
use core\base\settings\Settings;
use core\base\settings\ShopSettings;
use Exception;

class RouteController extends BaseController
{
	use Singleton;
	
	protected $routes;

	private function __construct(){
		$address_str = $_SERVER['REQUEST_URI'];

		if(strrpos($address_str, '/') === strlen($address_str) - 1 && strrpos($address_str, '/') !== 0) {
			$this->redirect(rtrim($address_str, '/'), 301);
		}

		$path = substr($_SERVER['PHP_SELF'], 0, strrpos($_SERVER['PHP_SELF'], 'index.php'));

		if($path === PATH) {
			$this->routes = Settings::get('routes');

			if(!$this->routes) throw new RouteException('Technical works');

			$url = explode('/', substr($address_str, strlen(PATH)));

			$hrUrl = $this->routes['user']['hrUrl'];

			$this->controller = $this->routes['user']['path'];

			$route = 'user';

			$this->createRoute($route, $url);
			

			if($url[1]) {
				$count = count($url);
				$key = '';

				if(!$hrUrl) $i = 1;
				else $i = 2; $this->parameters['alias'] = $url[1];

				for (; $i < $count; $i++) { 
					if(!$key){
						$key = $url[$i];
						$this->parameters[$key] = '';
					}else {
						$this->parameters[$key] = $url[$i];
						$key = '';
					}
				}
			}
		}else{
			try{
				throw new Exception("Not corrected site path");
				
			}catch(Exception $e){
				exit($e->getMessage());
			}
		}
	}

	private function createRoute($var, $arr) {
		$route = [];

		if(!empty($arr[0])) {
			if($this->routes[$var]['routes'][$arr[0]]) {
				$route = explode('/', $this->routes[$var]['routes'][$arr[0]]);

				$this->controller .= '/' . ucfirst($route[0].'Controller');
			}else {
				$this->controller .= '/' . ucfirst($arr[0].'Controller');
			}
		}else{
			$this->controller .= '/' . $this->routes['default']['controller'];
		}

		$this->controller = str_replace('//', '/', $this->controller);

		$this->inputMethod = $route[1] ? $route[1] : $this->routes['default']['inputMethod'];
		$this->outputMethod = $route[2] ? $route[2] : $this->routes['default']['outputMethod'];
		
		return;
	}
}