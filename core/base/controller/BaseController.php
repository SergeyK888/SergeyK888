<?php
namespace core\base\controller;
use core\base\exceptions\RouteException;
use core\base\settings\Settings;

abstract class BaseController
{
	use \core\base\controller\BaseMethods;

    protected $header;
    protected $content;
    protected $footer;

    protected $page;

	protected $errors;

	protected $controller;
	protected $inputMethod;
	protected $outputMethod;
	protected $parameters;

    protected $template;
    protected $styles;
    protected $scripts;

    protected $initAmoCRM = false;

    public function route(): void
    {
		$controller = str_replace('/', '\\', $this->controller);

        if(strpos($controller, 'Favicon.ico') !== false) die;
        if(strpos($controller,'?') !== false) $controller = 'core\user\controller\IndexController';

		try {
			$object = new \ReflectionMethod($controller, 'request');

			$args = [
				'parameters' => $this->parameters,
				'inputMethod' => $this->inputMethod,
				'outputMethod' => $this->outputMethod
			];

			$object->invoke(new $controller, $args);
		}
		catch (\ReflectionException $e) {
			throw new RouteException($e->getMessage());
		}
	}

	public function request($args)
    {

		$this->parameters = $args['parameters'];

		$inputData = $args['inputMethod'];
		$outputData = $args['outputMethod'];

		$data = $this->$inputData();

		if(method_exists($this, $outputData)) {

			$page = $this->$outputData($data);
			if($page) $this->page = $page;

		} elseif ($data) {

			$this->page = $data;

		}

		if($this->errors) $this->writeLog();

		$this->getPage();
	}

    public function setPage($page)
    {
        $this->page = $page;
        return $this;
    }

    protected function render($path = '', $parameters = [])
    {

		extract($parameters);

		if(!$path) {
			$path = TEMPLATE . explode(
				'controller', strtolower(
					(new \ReflectionClass($this))->getShortName()
				)
			)[0];
		}

		ob_start();

		if(!@include_once $path . '.php') throw new RouteException('Template not found - ' . $path . '.php');

		return ob_get_clean();

	}

	protected function getPage(){
		if(is_array($this->page)) {
			foreach ($this->page as $block) echo $block;
		}else{
			echo $this->page;
		}

		exit();
	}

    protected function outputData() {
        $this->header = $this->render(TEMPLATE . 'header');
        $this->footer = $this->render(TEMPLATE . 'footer');

        return $this->render(TEMPLATE . 'templater');
    }
}