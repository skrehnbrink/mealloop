
<?php

Zend_Loader::loadClass('Zend_Db_Table_Abstract');

class MealGenHistory extends Zend_Db_Table_Abstract
{
    protected $_name = 'mealgen_history';
    
    protected $_referenceMap    = array(
        'User' => array(
            'columns'           => array('user_id'),
            'refTableClass'     => 'User',
            'refColumns'        => array('user_id')
        )
    );
}

?>