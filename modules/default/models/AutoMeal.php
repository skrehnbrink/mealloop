
<?php

Zend_Loader::loadClass('Zend_Db_Table_Abstract');

class AutoMeal extends Zend_Db_Table_Abstract
{
    protected $_name = 'auto_meal';
    
    protected $_referenceMap    = array(
        'Options' => array(
            'columns'           => array('options_id'),
            'refTableClass'     => 'Options',
            'refColumns'        => array('options_id')
        )
    );
}

?>