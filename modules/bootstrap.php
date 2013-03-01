
<?php

  //TESTING PURPOSES ONLY 
  //error_reporting(E_ALL|E_STRICT);

  $root = '../mealloop';
  
  //add modules to the include path so we can dynamically load our classes
  $path = $root.'/modules/default/models/';
  set_include_path(get_include_path() . PATH_SEPARATOR . $path);


  //import the loader
  require_once $root.'/lib/Zend/Loader.php';

  //dynamically load necessary classes
  Zend_Loader::loadClass('Zend_Controller_Front');
  Zend_Loader::loadClass('Zend_Config_Ini');
  Zend_Loader::loadClass('Zend_Registry');
  Zend_Loader::loadClass('Zend_Db');
  Zend_Loader::loadClass('Zend_Db_Table');
  Zend_Loader::loadClass('Zend_View_Exception');
  Zend_Loader::loadClass('Zend_Controller_Plugin_ErrorHandler');


  // load configuration
  $config = new Zend_Config_Ini($root.'/modules/config.ini', 'general');
  //$registry = Zend_Registry::getInstance();
  //$registry->set('config', $config);  
  	
  // setup database
  $db = Zend_Db::factory($config->db->adapter, $config->db->config->toArray());
  Zend_Db_Table::setDefaultAdapter($db);


  $front = Zend_Controller_Front::getInstance();
  $front->setControllerDirectory($root.'/modules/default/controllers');
  $front->setDefaultControllerName('index');
  $front->setDefaultAction('community');
  
  $front->setParam('useDefaultControllerAlways', true);
  
  //return response so we can check for errors
  //$front->returnResponse(true);
  
  //display errors, debugging purposes
  //$front->throwExceptions(true);

		
  try{ 
    //run
    $response = $front->dispatch();
    
    /*
  	if ($response->isException()) {
    	echo "Caught exception: ". "\n";
    	echo "Message: ". "\n";
	} else {
    	$response->sendHeaders();
    	$response->outputBody();
	}
    */
  }
  catch(Exception $e){
    echo "Caught exception: " . get_class($e) . "\n";
    echo "Message: " . $e->getMessage() . "\n";
  }



  
  

  

?>