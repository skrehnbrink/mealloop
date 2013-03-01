
<?php

Zend_Loader::loadClass('Zend_Db_Table_Abstract');

class Quantity extends Zend_Db_Table_Abstract
{
    protected $_name = 'quantity';
    protected $_dependentTables = array('RecipexQuantityxIngredient');
}

?>