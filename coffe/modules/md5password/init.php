<?php

if (!defined('COFFE_MODE'))
	die('access denied');


/**
 * Авторизация пользователя
 *
 * @param $user
 * @param $login
 * @param $password
 */
function md5password_authorize($user, $login, $password)
{
	return ($user['password'] == md5($password));
}

/**
 * Сохранение пароля при редактировании пользователя
 *
 * @param $password
 * @return string
 */
function md5password_userSavePassword($password)
{
	return md5(trim($password));
}

Coffe_Event::register('User.authorize','md5password_authorize');

if (COFFE_MODE == 'BE')
	Coffe_Event::register('userSavePassword','md5password_userSavePassword');





