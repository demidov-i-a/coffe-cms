<?php


class Main_Php_Component extends Coffe_Component
{

	  public function main()
	  {
		  ob_start();
		  eval($this->data['content']);
		  return ob_get_clean();
	  }

}