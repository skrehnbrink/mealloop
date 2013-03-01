
<?php

Zend_Loader::loadClass('Zend_Db_Table_Abstract');

class User extends Zend_Db_Table_Abstract
{
    protected $_name = 'user';
    
    protected $_dependentTables = array('Options', 'AutoMenu', 'MealGenHistory', 'Recipe');
}

?>