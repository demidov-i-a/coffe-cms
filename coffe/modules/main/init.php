<?php

if (!defined('COFFE_MODE'))
	die('access denied');


if (COFFE_MODE == 'BE')
	require('be.inc.php');

if (COFFE_MODE == 'FE')
	require('fe.inc.php');








