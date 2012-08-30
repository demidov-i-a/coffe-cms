<?php
/**
 * Редактирование табличных данных
 *
 * @package coffe_cms
 */
class Liveform_Module extends Coffe_Module
{

	/**
	 * ID backend - модуля
	 *
	 * @var string
	 */
	protected $module_id = '_liveform';

	/**
	 * Значение primary key таблицы
	 *
	 * @var null
	 */
	protected $primary_value = null;

	/**
	 * primary key таблицы
	 *
	 * @var null
	 */
	protected $primary = null;

	/**
	 * Редактируемая таблица
	 *
	 * @var null
	 */
	protected $table = null;

	/**
	 * Расширение таблицы
	 *
	 * @var null
	 */
	protected $table_ext = null;

	/**
	 * Массив данных по умолчанию для заполнения формы
	 *
	 * @var array
	 */
	protected $data = array();

	/**
	 * Форма
	 *
	 * @var Coffe_LiveForm
	 */
	protected $form = null;

	/**
	 * Url для возврата
	 *
	 * @var null
	 */
	protected $back_url = null;

	/**
	 * Редактируемая строка с данными
	 *
	 * @var null
	 */
	protected $row = null;

	/**
	 * Параметры сортировки при добавлении новой записи
	 *
	 * @var null
	 */
	protected $sorting = null;

	/**
	 * Поле сортировки записей
	 *
	 * @var null
	 */
	protected $sorting_field = null;

	/**
	 * Поле группировки записей для сортировки
	 *
	 * @var null
	 */
	protected $sorting_field_group = null;

	/**
	 * Флаг удаления записи
	 *
	 * @var null
	 */
	protected $remove_field = null;

	/**
	 * Флаг скрытия записи
	 *
	 * @var null
	 */
	protected $hidden_field = null;

	/**
	 * Текущая операция
	 *
	 * @var null
	 */
	protected $operation = null;

	/**
	 * Формируемый контент
	 *
	 * @var string
	 */
	protected $content = '';

	/**
	 * Массив js флагов
	 *
	 * @var array
	 */
	protected $flags_js = array();

	/**
	 * Инициализация основных параметров
	 */
	public function init()
	{
		$this->table = $this->_GET('table');
		$this->primary_value = $this->_GET('primary');
		$this->back_url = $this->_GET('back_url');
		$data = $this->_GET('data', array());
		if (is_array($data)){
			$this->data = $data;
		}
		$sorting = $this->_GET('sorting', array());
		if (is_array($sorting)){
			$this->sorting = $sorting;
		}
		$this->operation = $this->_GET('operation');
	}

	/**
	 * Точка входа
	 *
	 * @return bool
	 * @throws Coffe_Exception
	 */
	function run()
	{
		$this->init();
		$this->processOneRecord();
	}

	/**
	 * Обработка одной строки
	 *
	 * @return bool
	 */

	public function processOneRecord()
	{
		if (empty($this->table)){
			return $this->showError('The table name wasn\'t set');
		}
		$this->table_ext = Coffe_ModuleManager::getLiveForm($this->table);

		if (!isset($this->table_ext['primary'])){
			return $this->showError('Primary key of the table "'. $this->table .'" isn\'t found');
		}

		$this->primary = $this->table_ext['primary'];

		if (isset($this->table_ext['sorting_field'])){
			$this->sorting_field = trim($this->table_ext['sorting_field']);
		}

		if (isset($this->table_ext['sorting_field_group'])){
			$this->sorting_field_group = trim($this->table_ext['sorting_field_group']);
		}

		if (isset($this->table_ext['remove_field'])){
			$this->remove_field = trim($this->table_ext['remove_field']);
		}

		if (isset($this->table_ext['hidden_field'])){
			$this->hidden_field = trim($this->table_ext['hidden_field']);
		}

		//проверяем наличие редактируемой строки
		if (!empty($this->primary_value)){
			if (!($this->row = $this->getRowByPrimary($this->primary_value))){
				return $this->showError('The table row isn\'t found');
			}
		}

		if (!empty($this->operation)){
			$this->doRecordOperation();
		}
		else{
			$this->doEditOneRecord();
		}
	}

	/**
	 * Редактирование одной записи
	 *
	 * @return bool
	 */
	public function doEditOneRecord()
	{
		//получаем строку для редактирования
		if (!empty($this->primary_value)){
			$this->module_title = (isset($this->table_ext['record_title']))
				? $this->lang('edit_record_template', array('TITLE' => $this->table_ext['record_title']))
				: $this->lang('edit_record');
			$this->flags_js['mode'] = '\'edit\'';
		}
		else{
			$this->module_title = (isset($this->table_ext['record_title']))
				? $this->lang('add_record_template', array('TITLE' => $this->table_ext['record_title']))
				: $this->lang('add_record');
			$this->flags_js['mode'] = '\'add\'';
		}

		$this->flags_js['after_save'] = (intval($this->_GET('after_save',0)) > 0) ? 'true' : 'false';

		$this->form = new Coffe_LiveForm($this->table);

		$this->form->setMethod('post')->setAction('');

		//hook
		Coffe_Event::call('LiveForm.beforeBuild', array(
				$this->table,
				&$this->table_ext,
				$this->primary,
				&$this->primary_value,
				&$this->data,
				&$this->form,
				&$this->row,
				&$this->module_title,
			)
		);

		$this->form->build((array)$this->table_ext);

		//hook
		Coffe_Event::call('LiveForm.afterBuild', array(
				$this->table,
				&$this->table_ext,
				$this->primary,
				&$this->primary_value,
				&$this->data,
				&$this->form,
				&$this->row,
				&$this->module_title,
			)
		);

		$this->showJsFlags();
		$this->includeJs();
		$this->addAdditionalContent();

		if (empty($this->primary_value)){
			return $this->addOneRecord();
		}
		else{
			return $this->editOneRecord();
		}
	}

	/**
	 * Добавление элемента
	 *
	 * @return bool
	 */
	public function addOneRecord()
	{
		if ($this->isPost()){
			//отмена
			if ($this->_POST('cancel')){
				return $this->gotoBackIfNotEmpty();
			}

			$post_data = $this->_POST($this->form->getName());

			if ($this->form->isValid($post_data)){
				$data = $this->form->getValuesForDB($post_data);
				//формируем поле сортировки для записи
				$this->makeSortingField($data);
				$cancel = false;
				Coffe_Event::call('LiveForm.beforeAdd', array($this->table, $this->primary, &$data, &$cancel, &$this->flash));
				//отменили
				if ($cancel){ return $this->gotoBackIfNotEmpty(); }
				$this->primary_value = $this->db->insert($this->table, $data);
				//ошибка добавления
				if (!$this->primary_value){
					$this->flash->pushError($this->db->lastError());
					return $this->gotoBackIfNotEmpty();
				}
				//hook
				Coffe_Event::call('LiveForm.afterAdd', array($this->table, $this->primary, $this->primary_value, &$data, $cancel, &$this->flash));
				$this->resortBranch($data);

				if ($this->_POST('apply')){
					return $this->gotoEdit();
				}
				return $this->gotoBack();
			}

			$this->form->populate($post_data,false);
			return $this->renderContent($this->content . $this->renderForm($this->form, $this->table));
		}
		//заполняем значениями по умолчанию
		$this->form->populate($this->data, true);
		return $this->renderContent($this->content . $this->renderForm($this->form, $this->table));
	}

	/**
	 * Редактирование элемента
	 *
	 * @return bool
	 */
	public function editOneRecord()
	{
		if ($this->isPost()){
			//отмена
			if ($this->_POST('cancel')){
				return $this->gotoBack();
			}
			$post_data = $this->_POST($this->form->getName());

			if ($this->form->isValid($post_data)){
				$data = $this->form->getValuesForDB($this->_POST($this->form->getName()));
				$cancel = false;
				Coffe_Event::call('LiveForm.beforeUpdate', array($this->table, $this->primary, $this->primary_value,  &$data, &$cancel, &$this->flash));
				//отменили
				if ($cancel){ return $this->gotoBack(); }
				$res = $this->db->update($this->table, $data, $this->primary . ' = ' . $this->db->fullEscapeString($this->primary_value));
				//ошибка сохранения
				if (!$res){
					$this->flash->pushError($this->db->lastError());
					return $this->gotoBackIfNotEmpty();
				}
				Coffe_Event::call('LiveForm.afterUpdate', array($this->table, $this->primary, $this->primary_value,  &$data, $cancel, &$this->flash));
				if ($this->_POST('apply')){
					return $this->gotoEdit();
				}
				return $this->gotoBack();
			}

			$this->form->populate($post_data, false);

			return $this->renderContent($this->content . $this->renderForm($this->form, $this->table));
		}
		//заполняем значениями по умолчанию
		$total = array_replace($this->row, $this->data);
		$this->form->populate($total,true);
		return  $this->renderContent($this->content . $this->renderForm($this->form, $this->table));
	}


	/**
	 * Рисует одну форму
	 *
	 * @param Coffe_LiveForm $form
	 * @param string $table
	 */
	public function renderForm($form, $table)
	{
		$content = '<div class="liveform-'. $table .'">';
		$content .= $form->render();
		$content .= '</div';
		return $content;
	}

	/**
	 * Создает поле сортировки
	 *
	 * @param $data
	 */
	public function makeSortingField(&$data)
	{
		if ($this->sorting_field !== null){
			$data[$this->sorting_field] = -1;
			if (isset($this->sorting['target'])){
				if ($target = $this->getRowByPrimary($this->sorting['target'])){
					$after = (isset($this->sorting['after']) && intval($this->sorting['after'])) ? true : false;
					$data[$this->sorting_field] = ($after) ? ($target[$this->sorting_field] + 1) : $target[$this->sorting_field] - 1;
				}
			}
		}
	}


	/**
	 * Пересчитывает индексы сортировки
	 *
	 * @param $data
	 */
	public function resortBranch($data)
	{
		if ($this->sorting_field !== null){
			$where = ($this->sorting_field_group !== null && isset($data[$this->sorting_field_group]) && !empty($data[$this->sorting_field_group]))
				? ($this->sorting_field_group . ' = ' . $this->db->fullEscapeString($data[$this->sorting_field_group]))
				: '';

			$sorting = (empty($this->sorting_field_group))
				? ($this->sorting_field . ' ASC')
				: ($this->sorting_field_group . ' ASC, ' . $this->sorting_field . ' ASC');

			$rows = $this->db->fetchAll($this->db->select('*', $this->table, $where, '', $sorting));
			if (is_array($rows)){
				$sorting = 0;
				foreach($rows as $row){
					$this->db->update(
						$this->table,
						array($this->sorting_field => $sorting),
						$this->primary . ' = ' . $this->db->fullEscapeString($row[$this->primary])
					);
					$sorting += 2;
				}
			}
		}
	}




	/**
	 * Выполнить операцию
	 *
	 * @return bool
	 */
	public function doRecordOperation()
	{
		$cancel = false;
		Coffe_Event::call('LiveForm.beforeOperation',array($this->table, $this->primary, $this->primary_value, $this->operation, &$cancel, $this->flash));
		$done = false;
		if (!$cancel){
			switch($this->operation){
				case 'moveup': $done = $this->move(); break;
				case 'movedown': $done = $this->move(true); break;
				case 'remove': $done = $this->remove(); break;
				case 'hide': $done = $this->hide(); break;
				case 'unhide': $done = $this->hide(true); break;
			}
			if ($done){
				Coffe_Event::call('LiveForm.afterOperation',array($this->table, $this->primary, $this->primary_value, $this->operation, $cancel, $this->flash));
			}
		}
		$is_ajax = $this->isAjaxRequest();
		if (!$is_ajax){
			if ($cancel) $this->flash->pushInfo($this->lang('operation_cancel'));
			return $this->gotoBackIfNotEmpty();
		}
		//ajax
		echo ($cancel) ? ('cancel') : (($done) ? ('success') : ('fail'));
	}

	/**
	 * Возвращает url для возврата
	 *
	 * @return string
	 */
	protected function fixBackUrl()
	{
		if (!empty($this->back_url)){
			return urldecode(str_replace('_PRIMARY_',$this->primary_value, $this->back_url));
		}
	}

	/**
	 * Перемещение записи
	 *
	 * @param bool $down
	 * @return bool
	 */
	protected function move($down = false)
	{
		$target = $this->_GET('target');
		$res = false;
		if (!empty($this->sorting_field) && !empty($this->primary_value) && !empty($target)){
			$record = $this->getRowByPrimary($this->primary_value);
			$target = $this->getRowByPrimary($target);
			if ($record && $target){
				$sorting = ($down) ? ($target[$this->sorting_field] + 1) : ($target[$this->sorting_field] - 1);
				if ($res = $this->db->update($this->table, array($this->sorting_field => $sorting), $this->primary . ' = ' . ($record[$this->primary]))){
					$this->resortBranch($record);
				}
				else{
					$this->flash->pushError($this->db->lastError());
				}
			}
		}
		return $res;
	}

	/**
	 * Получение строки
	 *
	 * @param $primary
	 * @return array|bool
	 */
	public function getRowByPrimary($primary)
	{
		return $this->db->fetch($this->db->select('*', $this->table, $this->primary . ' = ' . $this->db->fullEscapeString($primary)));
	}

	/**
	 * Удаление записи
	 */
	public function remove()
	{
		$res = false;
		if (!empty($this->primary_value)){
			//если задан флаг удаления
			if (isset($this->remove_field)){
				$update = array($this->remove_field => 1);
				$res = $this->db->update($this->table, $update, $this->primary . ' = ' . $this->db->escapeString($this->primary_value));
			}
			else{
				$res  = $this->db->delete($this->table, $this->primary . ' = ' . $this->db->escapeString($this->primary_value));
			}
			if (!$res) $this->flash->pushError($this->db->lastError());
		}
		return $res;
	}

	/**
	 * Скрыть/отобразить запись
	 *
	 * @param bool $unhide
	 * @return bool
	 */
	public function hide($unhide = false)
	{
		if (!empty($this->hidden_field)){
			return $this->db->update($this->table, array($this->hidden_field => ($unhide ? 0 : 1)), $this->primary . ' = ' . $this->db->escapeString($this->primary_value));
		}
		return false;
	}

	/**
	 * Подключение js файлов
	 */
	public function includeJs()
	{
		if (isset($this->table_ext['js']) && is_array($this->table_ext['js'])){
			foreach($this->table_ext['js'] as $id => $file){
				Coffe::getHead()->addJsFile($id, Coffe::getUrlPrefix() . $file);
			}
		}
	}

	/**
	 * Добавляет js флаги в head
	 */
	public function showJsFlags()
	{
		if (count($this->flags_js)){
			$content = '<script type="text/javascript">' . PHP_EOL;
			$flags = array();
			foreach($this->flags_js as $name => $value){
				$flags[] = $name . ': ' . $value;
			}
			$content .= 'var flags = {' . implode(',', $flags) . '};' . PHP_EOL;
			$content .= '</script>' . PHP_EOL;
			Coffe::getHead()->addData('flags_js',$content);
		}
	}

	/**
	 * Добавление дополнительного контента
	 */
	public function addAdditionalContent()
	{
		if ($this->_GET('after_save')){
			$this->content .='<script type="text/javascript"></script>';
			if (isset($this->table_ext['update_nav_frame']) && $this->table_ext['update_nav_frame']){
				$this->content .= '<script type="text/javascript">parent.COFFE.updateNavFrame();</script>';
			}
		}
	}

	public function showError($error)
	{
		$this->flash->pushError($error);
		return $this->renderContent('');
	}

	/**
	 * Уходим назад или показываем пустую страницу
	 *
	 * @return bool
	 */
	public function gotoBackIfNotEmpty()
	{
		if (empty($this->back_url)){
			return $this->renderContent();
		}
		return $this->redirect($this->fixBackUrl());
	}

	/**
	 * Уходим на редактирование текущей записи
	 *
	 * @return bool
	 */
	public function gotoEdit()
	{
		return $this->redirectToModule(null, array(
			'table' => $this->table,
			'primary' => $this->primary_value,
			'data' => array('_group_' => $this->form->getActiveGroup()),
			'after_save' => '1',
			'back_url' => $this->back_url
		));
	}

	/**
	 * Уходим назад
	 *
	 * @return bool
	 */
	public function gotoBack()
	{
		if (empty($this->back_url)){
			return $this->gotoEdit();
		}
		else{
			return $this->redirect($this->fixBackUrl());
		}
	}
}