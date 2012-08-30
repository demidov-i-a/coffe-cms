<?php

/**
 * ������� Textarea ��� LiveForm
 *
 * @package coffe_cms
 */
class Coffe_LiveForm_Element_Textarea extends Coffe_LiveForm_Element_Abstract
{

	/**
	 * ������� cols
	 *
	 * @var int
	 */
	protected $cols = 60;

	/**
	 * ������� rows
	 *
	 * @var int
	 */
	protected $rows = 8;

	/**
	 * ����� ��������
	 *
	 * @return string
	 */
	public function render()
	{
		$attributes = array(
			'name' => $this->getFullName(),
			'id' => $this->getID(),
			'cols' => $this->cols,
			'rows' => $this->rows,
		);
		return '<textarea ' . $this->getAttributes($attributes) . '>' .$this->_value . '</textarea>';
	}

	/**
	 * ��������� �������� $rows
	 *
	 * @param $value
	 * @return Coffe_LiveForm_Element_Textarea
	 */
	public function setRows($value)
	{
		$this->rows = intval($value);
		return $this;
	}

	/**
	 * ��������� �������� rows
	 *
	 * @return int
	 */
	public function getRows()
	{
		return $this->rows;
	}

	/**
	 * ��������� �������� cols
	 *
	 * @param $value
	 * @return Coffe_LiveForm_Element_Textarea
	 */
	public function setCols($value)
	{
		$this->cols = intval($value);
		return $this;
	}

	/**
	 * ��������� �������� cols
	 *
	 * @return int
	 */
	public function getCols()
	{
		return $this->cols;
	}

}