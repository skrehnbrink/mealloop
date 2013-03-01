
<?php

Zend_Loader::loadClass('Zend_Db_Table_Abstract');

class Ingredient extends Zend_Db_Table_Abstract
{
    protected $_name = 'ingredient';
    protected $_dependentTables = array('RecipexQuantityxIngredient');
}

?>