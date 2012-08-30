<?php

class Main_Html_Component extends Coffe_Component
{

	  public function main()
	  {
		  $this->view->data = $this->data;
		  return $this->render('index.phtml');
	  }

}