
<?php

/** Zend_Controller_Action */
require_once 'Zend/Controller/Action.php';

class ErrorController extends Zend_Controller_Action
{
    public function errorAction()
    {
      $this->baseUrl = $this->getFrontController()->getBaseUrl().'/'.$this->getFrontController()->getDefaultControllerName();
	  $this->view->baseUrl = $this->baseUrl;

      //$this->_helper->viewRenderer->setNoRender(true);

      //$errors = $this->_getParam('error_handler');

      //$this->getResponse()->setHeader('Content-Type', 'text/xml');
      //$this->getResponse()->appendBody($errors);

      //$this->_redirect($this->getRequest()->getBaseUrl());
      
      $errors = $this->_getParam('error_handler');
      $this->view->errorMsg = $errors->exception;
    }
}

