<?php

namespace core\user\controller;

use core\base\controller\BaseController;
use core\user\model\APIModel;

class IndexController extends BaseController
{

	protected function inputData() {
        $apiClient = new APIModel();

        if($this->isPost()) {
            $apiClient->sendLeads($_POST);
        }
	}

    protected function outputData() {
        $args = func_get_arg(0);
        $vars = $args ? $args : [];

        $this->content = $this->render(TEMPLATE . 'index', $vars);

        return parent::outputData();

    }
}