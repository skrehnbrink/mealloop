
<?php

Zend_Loader::loadClass('Zend_Db_Table_Abstract');

class AutoMenu extends Zend_Db_Table_Abstract
{
    protected $_name = 'auto_menu';
    
    protected $_referenceMap    = array(
    	'User' => array(
            'columns'           => array('user_id'),
            'refTableClass'     => 'User',
            'refColumns'        => array('user_id')
        ),
        
        'Recipe' => array(
            'columns'           => array('recipe_id'),
            'refTableClass'     => 'Recipe',
            'refColumns'        => array('recipe_id')
        )
    );
}

?>