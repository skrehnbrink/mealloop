
<?php

Zend_Loader::loadClass('Zend_Db_Table_Abstract');

class RecipexQuantityxIngredient extends Zend_Db_Table_Abstract
{
    protected $_name = 'recipexquantityxingredient';


    protected $_referenceMap    = array(
        'Recipe' => array(
            'columns'           => array('recipe_id'),
            'refTableClass'     => 'Recipe',
            'refColumns'        => array('recipe_id')
        ),
        'Quantity' => array(
            'columns'           => array('quantity_id'),
            'refTableClass'     => 'Quantity',
            'refColumns'        => array('quantity_id')
        ),
        'Ingredient' => array(
            'columns'           => array('ingredient_id'),
            'refTableClass'     => 'Ingredient',
            'refColumns'        => array('ingredient_id')
        )
    );
}

?>