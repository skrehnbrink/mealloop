
<?php

Zend_Loader::loadClass('Zend_Db_Table_Abstract');

class ManualMeal extends Zend_Db_Table_Abstract
{
    protected $_name = 'manual_meal';
    
    protected $_referenceMap    = array(
        'Options' => array(
            'columns'           => array('options_id'),
            'refTableClass'     => 'Options',
            'refColumns'        => array('options_id')
        ),
        'Recipe' => array(
            'columns'           => array('recipe_id'),
            'refTableClass'     => 'Recipe',
            'refColumns'        => array('recipe_id')
        )
    );
}

?>