<?php

/**
 * ������ � �������������
 *
 * @package coffe_cms
 */
class Coffe_User
{

	/**
	 * ������� � ��������������
	 *
	 * @var string
	 */
	protected $table = 'user';


	/**
	 * ������� � ��������
	 *
	 * @var string
	 */
	protected $table_group = 'user_group';

	/**
	 * ������� � ������� ������ ������������
	 *
	 * @var string
	 */
	protected $table_session = 'session_data';


	/**
	 * ������� � ���� � id ������������
	 *
	 * @var string
	 */
	protected $id_column = 'uid';


	/**
	 * ������� � ���� � ����� ������
	 *
	 * @var string
	 */
	protected $login_column = 'username';

	/**
	 * ������� � ���� � ����� ����������� ������������
	 *
	 * @var string
	 */
	protected $remember_column = 'remember';

	/**
	 * ������� � ���� � ����� ������
	 *
	 * @var string
	 */
	protected $password_column = 'password';

	/**
	 * ��� ���� "��������� ����"
	 *
	 * @var string
	 */
	protected $remember_cookie_name = 'COFFE_USER_TOKEN';

	/**
	 * ����� ����� ���� "��������� ����"
	 *
	 * @var int
	 */
	protected $remember_live_time = 2592000;

	/**
	 * ���� � ������� _SESSION
	 *
	 * @var string
	 */
	protected $session_key = 'user';

	/**
	 * ������� �������������� ������������
	 *
	 * @var array|null
	 */
	protected $user = null;

	/**
	 * @var Coffe_User
	 */
	protected static $instance = null;

	/**
	 * ��������� ����������� � �����
	 *
	 * @var bool
	 */
	protected $allow_remember = true;

	/**
	 * �������������� ������� ��� ������� �������������
	 *
	 * @var string
	 */
	protected $add_where = ' AND NOT `disable`';


	/**
	 * ������������� ��������� ���������� ������ ������������ � ����
	 *
	 * @var bool
	 */
	protected $auto_save_permanent_data = true;

	/**
	 * ���� � ����������� ������
	 *
	 * @var array
	 */
	protected $errors = array(
		'1' => 'Login required',
		'2' => 'Password required',
		'3' => 'The user isn\'t found',
		'4' => 'Not a valid username and password',
	);

	/**
	 * @var Coffe_DB_Abstract
	 */
	private $db = null;


	/**
	 * ��������� ����������� ������������
	 */
	public function start()
	{
		//���� ������������, �������� ������������ �� ���� �� id
		if ($this->isLogin()){
			$res = $this->db->select('*', $this->table, $this->id_column . " = '"
				. $this->db->escapeString($_SESSION[$this->session_key][$this->id_column]). "' " . $this->add_where);
			if ($user = $this->db->fetch($res)){
				$this->user = $user;
			}
			//�� ����� ������������ � ����
			else{
				$this->clear();
			}
		}
		elseif($this->allow_remember){
			$this->checkRemember();
		}
	}

	/**
	 * ��������� ������ �������� �������������� ������������
	 *
	 * @return array|null
	 */
	public function getUser()
	{
		return $this->user;
	}

	/**
	 * ��������� ������ �������� �������������� ������������
	 *
	 * @static
	 * @return array|null
	 */
	public static function get()
	{
		return self::getInstance()->getUser();
	}

	/**
	 * ������� ������ ������������
	 */
	public function clear()
	{
		unset($this->user);
		unset($_SESSION[$this->session_key]);
		$this->forgot();
	}

	/**
	 * ����������� �� ������������
	 *
	 * @return bool
	 */
	public function isLogin()
	{
		return (isset($_SESSION[$this->session_key]) && is_array($_SESSION[$this->session_key]) && isset($_SESSION[$this->session_key][$this->id_column])) ? true : false;
	}

	/**
	 * ����������� ������������
	 *
	 * @param $login
	 * @param $password
	 * @param $error_code
	 * @return bool
	 */
	public function authorize($login, $password, &$error_code = null)
	{
		$this->clear();
		$login = trim($login); $password = trim($password);
		if (empty($login)) {$error_code = '1'; return false;}
		if (empty($password)) {$error_code = '2'; return false;}
		$res = $this->db->select('*', $this->table, 'LOWER(' . $this->login_column . ") = '" . $this->db->escapeString(strtolower($login)). "' " . $this->add_where);
		$user = $this->db->fetch($res);
		if (!$user){$error_code = '3'; return false;}
		//hook
		if (Coffe_Event::isRegister('User.authorize')){
			$result = Coffe_Event::callLast('User.authorize', array($user, $login, $password));
			if (!$result){
				$error_code = '4'; return false;
			}
		}
		else{
			if ($user[$this->password_column] != $password){
				$error_code = '4'; return false;
			}
		}
		$this->createSession($user);
		$this->recoverPermanentData();
		return true;
	}

	/**
	 * ��������� ���� ������
	 *
	 * @param $user
	 * @param $login
	 * @param $password
	 * @return mixed|string
	 */
	private function getPasswordHash($user, $login, $password)
	{
		//Hook
		if (Coffe_Event::isRegister('User.getPasswordHash')){
			$result = Coffe_Event::callLast('User.getPasswordHash',array($password, $login, $user));
			if (trim($result)) return $result;
		}
		return $password;
	}

	/**
	 * ����������� ������������ �� ID
	 *
	 * @param $uid
	 * @param $error_code
	 * @return bool
	 */
	public function authorizeById($uid, &$error_code)
	{
		$this->clear();
		$uid = trim($uid);
		$res = $this->db->select('*', $this->table, $this->id_column . " = '" . $this->db->escapeString($uid). "' " . $this->add_where);
		$user = $this->db->fetch($res);
		if (!$user){$error_code = '3'; return false;}
		$this->createSession($user);
		$this->recoverPermanentData();
		return true;
	}

	/**
	 * �������� ������
	 *
	 * @param array $user
	 */
	private function createSession(array $user)
	{
		$_SESSION[$this->session_key] = array();
		$_SESSION[$this->session_key]['uid'] = $user[$this->id_column];
		$this->user = $user;
		Coffe_Event::call('User.afterAuthorization',array(&$this));
	}

	/**
	 * ��������� �������� ��������������� ������������
	 *
	 * @return bool
	 */
	public function remember()
	{
		if ($this->allow_remember && $this->isLogin()){
			$token = $this->getRememberToken();
			setcookie($this->remember_cookie_name, $token, time() + $this->remember_live_time);
			$this->db->update($this->table, array($this->remember_column => $token),$this->id_column . " = '" . $this->db->escapeString($this->user[$this->id_column]). "'");
			return true;
		}
		return false;
	}

	/**
	 * ��������� ���� ��� ����������� ������������
	 *
	 * @return string
	 */
	protected function getRememberToken()
	{
		return md5($this->user[$this->id_column]. md5($this->user[$this->password_column]. Coffe_Func::getClientIp()));
	}

	/**
	 * ��������� ������ �������������
	 *
	 * @param bool $not_disable
	 * @param null $filter
	 * @param string $add_where
	 * @return array|bool
	 */
	public function getList($not_disable = true, $filter = null, $add_where = '')
	{
		$where = '1';
		$where .= $not_disable ? ' AND NOT disable' : '';
		$where .= $add_where ? (' AND ' . $add_where) : '';
		$res = $this->db->select('*', $this->table, $where);
		return $this->db->fetchAll($res);
	}

	/**
	 * ��������� ������������
	 *
	 * @return bool
	 */
	public function checkRemember()
	{
		if (!$this->isLogin()){
			if ($this->allow_remember && isset($_COOKIE[$this->remember_cookie_name])){
				$res = $this->db->select('*', $this->table, $this->remember_column . " = '" . $this->db->escapeString($_COOKIE[$this->remember_cookie_name]). "' ". $this->add_where);
				$user = $this->db->fetch($res);
				if ($user){
					$this->createSession($user);
					$this->recoverPermanentData();
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * ������ ������������
	 */
	public function forgot()
	{
		setcookie($this->remember_cookie_name, '', time() - 3600);
	}

	/**
	 * ��������� ������������� ���������� ������
	 *
	 * @static
	 * @return Coffe_User
	 */
	public static function getInstance()
	{
		if (null === self::$instance) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * ��������� ����������� ������
	 *
	 * @param $code
	 * @return mixed
	 */
	public function getError($code)
	{
		return $this->errors[$code];
	}


	protected function __construct()
	{
		Coffe::startSession();
		$this->db = $GLOBALS['COFFE_DB'];
	}


	/**
	 * ���������� ��������� ������
	 *
	 * @param $key
	 * @param $data
	 * @return Coffe_User
	 */
	public function setData($key, $data)
	{
		if ($this->isLogin()){
			$_SESSION[$this->session_key]['data'][$key] = $data;
		}
		return $this;
	}

	/**
	 * �������� ��������� ������ �� ������ ������������
	 *
	 * @param $key
	 * @return null
	 */
	public function getData($key)
	{
		if (!$this->isLogin()) return null;

		return ($key !== null)
			? (isset($_SESSION[$this->session_key]['data'][$key]) ? $_SESSION[$this->session_key]['data'][$key] : null)
			: ($_SESSION[$this->session_key]['data']);
	}

	/**
	 * ���������� �������������� ������
	 *
	 * @param $value
	 * @return Coffe_User
	 */
	public function setAutoSavePermanentData($value)
	{
		$this->auto_save_permanent_data = $value ? true : false;
		return $this;
	}

	/**
	 * ���������� ���������� ������
	 *
	 * @param $key
	 * @param $data
	 * @return Coffe_User
	 */
	public function setPermanentData($key, $data)
	{
		if ($this->isLogin()){
			$_SESSION[$this->session_key]['permanent_data'][$key] = $data;
			if ($this->auto_save_permanent_data)
				$this->savePermanentData();
		}
		return $this;
	}

	/**
	 * ��������� ���������� ������ � ����
	 *
	 * @return bool|int
	 */
	public function savePermanentData()
	{
		if ($this->isLogin()){
			if (isset($_SESSION[$this->session_key]['permanent_data']) && is_array($_SESSION[$this->session_key]['permanent_data'])){
				$res = $this->db->select('*', $this->table_session, 'user = ' . $this->db->fullEscapeString($this->getID()));
				$find = $this->db->fetch($res);
				if ($find){
					return $this->db->update($this->table_session,
						array('data' => serialize($_SESSION[$this->session_key]['permanent_data'])),
						'user =' . $this->db->fullEscapeString($this->getID())
					);
				}
				else{
					return $this->db->insert($this->table_session,
						array(
							'data' => serialize($_SESSION[$this->session_key]['permanent_data']),
							'user' => $this->getID()
						)
					);
				}
			}
		}
		return false;
	}

	/**
	 * ��������������� ���������� ������
	 */
	public function recoverPermanentData()
	{
		if ($this->isLogin()){
			$res = $this->db->select('*', $this->table_session, 'user = ' . $this->db->fullEscapeString($this->getID()));
			$data = $this->db->fetch($res);
			if ($data){
				$_SESSION[$this->session_key]['permanent_data'] = unserialize($data['data']);
			}
		}
	}

	/**
	 * �������� id �������� ������������
	 *
	 * @return null|int
	 */
	public function getID()
	{
		if ($this->isLogin()){
			return $_SESSION[$this->session_key][$this->id_column];
		}
		return null;
	}

	/**
	 * ��������� ���� ����� �������������
	 *
	 * @return array|bool
	 */
	public function getAllGroups()
	{
		return $this->db->fetchAll($this->db->select('*', $this->table_group));
	}

	/**
	 * �������� ���������� ������ �� ������ ������������
	 *
	 * @param $key
	 * @return null
	 */
	public function getPermanentData($key)
	{
		if (!$this->isLogin()) return null;

		return ($key !== null)
			? (isset($_SESSION[$this->session_key]['permanent_data'][$key]) ? $_SESSION[$this->session_key]['permanent_data'][$key] : null)
			: ($_SESSION[$this->session_key]['permanent_data']);
	}

}