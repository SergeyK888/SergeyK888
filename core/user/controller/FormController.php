<?php

namespace core\user\controller;

use core\base\controller\BaseController;

class FormController extends BaseController
{
    protected function inputData() {

    }

    protected function outputData() {
        $args = func_get_arg(0);
        $vars = $args ? $args : [];

        $this->content = $this->render(TEMPLATE . 'form', $vars);

        return parent::outputData();

    }
}