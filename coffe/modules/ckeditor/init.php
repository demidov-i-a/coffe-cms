<?php

if (!defined('COFFE_MODE'))
	die('access denied');

if (COFFE_MODE == 'BE' || Coffe::getConfig('enable_rte_fe', false)){
	require('inc.php');
}






