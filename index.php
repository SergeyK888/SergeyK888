<?php
/* ------------------ */
/* Created by Squizee */
/* ------------------ */

//Vision Ground Constant
define('VG_ACCESS', true);

//Basic Settings and Configs
header('Content-Type:text/html;charset=utf-8');
session_start();

require_once 'config.php';
require_once 'libraries/vendor/autoload.php';
include_once 'libraries/vendor/amocrm/oauth2-amocrm/src/AmoCRM.php';
require_once 'core/base/settings/internal_settings.php';



//Router and Exception (Entry Point)
use core\base\exceptions\RouteException;
use core\base\controller\RouteController;

try {
	RouteController::instance()->route();
} catch (RouteException $e) {
	exit($e->getMessage());
}