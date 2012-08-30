<?php

require(dirname(__FILE__) . '/includes/init.inc.php');

define('COFFE_MODE', 'BE');

Coffe::initUser();

Coffe_ModuleManager::initModules();

Coffe::setUrlPrefix(Coffe_Functions::getAbsPrefixUrl());

header('Location: ' . Coffe_ModuleManager::getBackendModuleUrl('_login'));