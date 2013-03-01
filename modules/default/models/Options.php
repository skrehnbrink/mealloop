
<?php

Zend_Loader::loadClass('Zend_Db_Table_Abstract');

class Options extends Zend_Db_Table_Abstract
{
    protected $_name = 'options';
    protected $_dependentTables = array('AutoMeal', 'ManualMeal');
    
    protected $_referenceMap    = array(
        'User' => array(
            'columns'           => array('user_id'),
            'refTableClass'     => 'User',
            'refColumns'        => array('user_id')
        )
    );
}

?>