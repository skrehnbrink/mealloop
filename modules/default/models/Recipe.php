
<?php

Zend_Loader::loadClass('Zend_Db_Table_Abstract');

class Recipe extends Zend_Db_Table_Abstract
{
    protected $_name = 'recipe';
    protected $_dependentTables = array('RecipexQuantityxIngredient', 'ManualMeal', 'AutoMenu');
    
    protected $_referenceMap    = array(
        'User' => array(
            'columns'           => array('user_id'),
            'refTableClass'     => 'User',
            'refColumns'        => array('user_id')
        )
    );
}

?>