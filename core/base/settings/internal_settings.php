<?php
defined('VG_ACCESS') or die('Access denied');

//Template
const TEMPLATE = 'templates/default/';

//Path Scripts and Styles
const USER_CSS_JS = [
	'styles' => [
		'css/style.css',
	],
	'scripts' => [
		'js/script.js',
	]
];
//Autoload classes
use core\base\exceptions\RouteException;

function autoloadMainClasses($class_name) {
	$class_name = str_replace('\\', '/', $class_name);

	if(!@include_once $class_name.'.php') {
		throw new RouteException('Not corrected filename on include - '.$class_name.'.php');
	}
}

spl_autoload_register('autoloadMainClasses');