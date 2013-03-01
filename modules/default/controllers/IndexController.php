
<?php

/** Zend_Controller_Action */
Zend_Loader::loadClass('Zend_Controller_Action');
Zend_Loader::loadClass('Zend_Filter_StripTags');
Zend_Loader::loadClass('Zend_Session_Namespace');
Zend_Loader::loadClass('Zend_Mail');
Zend_Loader::loadClass('Zend_Mail_Transport_Smtp');
Zend_Loader::loadClass('Zend_Measure_Abstract');
Zend_Loader::loadClass('Zend_Measure_Cooking_Volume');
Zend_Loader::loadClass('Zend_Measure_Cooking_Weight');
Zend_Loader::loadClass('Zend_Locale');
Zend_Loader::loadClass('Zend_Exception');
Zend_Loader::loadClass('Zend_Log_Writer_Stream');
Zend_Loader::loadClass('Zend_Log');

Zend_Loader::loadClass('User');
Zend_Loader::loadClass('Recipe');
Zend_Loader::loadClass('Quantity');
Zend_Loader::loadClass('Ingredient');
Zend_Loader::loadClass('RecipexQuantityxIngredient');
Zend_Loader::loadClass('Options');
Zend_Loader::loadClass('AutoMeal');
Zend_Loader::loadClass('ManualMeal');
Zend_Loader::loadClass('AutoMenu');
Zend_Loader::loadClass('MealGenHistory');


class IndexController extends Zend_Controller_Action
{
	//handle invalid actions
	public function __call($method, $args)
    {
        if ('Action' == substr($method, -6)) {
			$this->baseUrl = $this->getRequest()->getBaseUrl().'/'.$this->getRequest()->getControllerName();
            return $this->_redirect($this->baseUrl);
        }

        throw new Exception('Invalid method');
    }
    
    
    public function init()
    {
		
		$session = new Zend_Session_Namespace('Default');
		$this->session = $session;
		
		//setup log
		$writer = new Zend_Log_Writer_Stream('../logs/mealloop.log');
		$logger = new Zend_Log($writer);
		$this->logger = $logger;
		//example of how to use logger
		//$this->logger->info("info: _validateRecipe => breakfast: ".$breakfast);
		
		//set url
		$this->baseUrl = $this->getRequest()->getBaseUrl().'/'.$this->getRequest()->getControllerName();
		$this->view->baseUrl = $this->baseUrl;
		
		//if logged in, set the user view
		if($this->session->user_email != null){
			// set up an "empty" user
			$user = new User();
	
			$where = $user->getAdapter()->quoteInto('email = ?', $this->session->user_email);
			$row = $user->fetchRow($where);
	
			if($row){
				$data = array('email' => $row->email,
					    'email2' => $row->email,
						'pass' => $row->pass,
						'pass2' => $row->pass,
						'name' => $row->name,
						'url' => $row->url,
						'id' => $row->user_id
					);
			
	
				$this->view->user_data = $data;
			}	
			
		}
		else{
			
			$this->view->user_email = null;
	
			//if not logged in and not on index page or register page, redirect to the index page
			$action = $this->getRequest()->getActionName();
			if(strcmp($action, "community") != 0 && strcmp($action, "register") != 0 && strcmp($action, "about") != 0 && strcmp($action, "login") != 0 && strcmp($action, "help") != 0){
				$this->_redirect($this->baseUrl.'/login');
			}
		}
    }


    public function loginAction(){
    
		$this->view->action = 'login';
		// set up an "empty" user
		$user = new User();
	    
	
		//if already logged in, go to main page
		if($this->session->user_email != null){
			$this->_redirect($this->baseUrl.'/main');
			return;
		}
	
		//logging in
		if ($this->_request->isPost()) {
			$filter = new Zend_Filter_StripTags();
	
			$username = trim($filter->filter($this->_request->getPost('userTF')));
			$pass = trim($filter->filter($this->_request->getPost('passTF')));
			$email = trim($filter->filter($this->_request->getPost('emailTF')));
			
			$row = null;
			if($username != null && $pass != null){
				$where = array(
					$user->getAdapter()->quoteInto('email = ?', $username),
					$user->getAdapter()->quoteInto('pass = ?', $pass)
				);
		
				$row = $user->fetchRow($where);
				
				if($row != null){
					$this->session->user_email = $row->email;
					$this->session->user_id = $row->user_id;
					$this->_redirect($this->baseUrl.'/main');
				}
				else{
					$this->view->errorMsg = "Invalid User/Password";
					$this->view->user = $user;
					$this->view->password = $pass;
				}
			}
			else if($email != null){
				$where = $user->getAdapter()->quoteInto('email = ?', $email);
				$row = $user->fetchRow($where);
				
				if($row){
					$config = array('auth' => 'login',
	                'username' => 'admin@mealloop.com',
	                'password' => 'vwxyz9');
				
					$tr = new Zend_Mail_Transport_Smtp('mail.krehnbrink.com', $config);
					Zend_Mail::setDefaultTransport($tr);
					
					$mail = new Zend_Mail();
					$mail->setBodyText('Your MealLoop password is: '.$row->pass);
					$mail->setFrom('sam@krehnbrink.com', 'MealLoop Admin');
					$mail->addTo($email, $email);
					$mail->setSubject('MealLoop Reminder');
					$mail->send($tr);
					
					$this->view->infoMsg = "Password Sent";
				}
				else{
					$this->view->errorMsg = "Invalid Email Address";
				}
			}
			else{
				$this->view->errorMsg = "Invalid User/Password";
				$this->view->user = $user;
				$this->view->password = $pass;
			}
		}

    }

    public function communityAction(){

		$this->view->action = 'community';
		$this->view->contentTitle = 'Meals';
		
		// set up an "empty" recipe
		$recipe = new Recipe();
		// set up empty recipe_data array	
		$recipe_data = array();
	
		
		$filter = new Zend_Filter_StripTags();
		$id = trim($filter->filter($this->_request->getQuery('id')));
		$tag = trim($filter->filter($this->_request->getQuery('tag')));
		$user_id = trim($filter->filter($this->_request->getQuery('user')));
		
		//if searching for meals
		if ($this->_request->isPost()) {
			$meal_data = array();
			
			$filter = new Zend_Filter_StripTags();
			$searchTerm = trim($filter->filter($this->_request->getPost('searchTF')));
			$userView = trim($filter->filter($this->_request->getPost('user_view')));
		
			$where = $recipe->getAdapter()->quoteInto('tags like ?', '%'.$searchTerm.'%').
				$recipe->getAdapter()->quoteInto(' or title like ?', '%'.$searchTerm.'%').
				' and status is null and copied_from_recipe_id is null';
			
			if($userView != null && $userView == 'true') {
				$where = $where.$recipe->getAdapter()->quoteInto(' and user_id = ?', $this->session->user_id);
			}				
				
			$rows = $recipe->fetchAll($where);

			$meal_index = 0;
			foreach($rows as $row) {
				$meal_data[$meal_index] = array();
				$meal_data[$meal_index]['title'] = $row->title;
				$meal_data[$meal_index]['id'] = $row->recipe_id;
				$meal_data[$meal_index]['tags'] = explode(",", $row->tags);
				$meal_index++;
			}	
		
			$this->view->contentTitle = 'Meal';
			$this->view->meals = $meal_data;
		}
		//retrieve a meal with given id
		else if($id != null){
			
			// disable autorendering for this action only:
	        //$this->_helper->viewRenderer->setNoRender();

			$where = array(
				$recipe->getAdapter()->quoteInto('recipe_id = ?', $id)
			);
			
			$row = $recipe->fetchRow($where);
	
			$this->view->editable = false;
			if($this->session->user_id != null && $this->session->user_id == $row->user_id) {
				$this->view->editable = true; 
			}
			
			$this->view->contentTitle = 'Meal';
			$data = $this->_getRecipe($row);
			$this->view->recipe_data = $data;
			
			//$this->render('meal');
			//return;
		}
		//retrieve meals with given tag
		else if($tag != null) {

			$meal_data = array();
			
			$filter = new Zend_Filter_StripTags();
		
			$where = $recipe->getAdapter()->quoteInto('tags like ?', '%'.$tag.'%').' and status is null';
			
			if($user_id != null) {
				$where = $where.$recipe->getAdapter()->quoteInto(' and user_id = ?', $this->session->user_id);
			}
			else {
				$where = $where.' and copied_from_recipe_id is null';
			}
			
			$rows = $recipe->fetchAll($where);

			$meal_index = 0;
			foreach($rows as $row) {
				$meal_data[$meal_index] = array();
				$meal_data[$meal_index]['title'] = $row->title;
				$meal_data[$meal_index]['id'] = $row->recipe_id;
				$meal_data[$meal_index]['tags'] = explode(",", $row->tags);
				$meal_index++;
			}	
		
			$this->view->contentTitle = 'Meal';
			$this->view->meals = $meal_data;
		}
		//retrieve the last 25 meals
		else{

			
			$meal_data = array();
			
			$filter = new Zend_Filter_StripTags();

			$where = null;
			if($user_id != null){
				$where = $recipe->getAdapter()->quoteInto('user_id = ?', $this->session->user_id).' and status is null';
			}
			else{
				$where = 'status is null and copied_from_recipe_id is null';
			}
			
			$rows = $recipe->fetchAll($where, 'recipe_id DESC', 25);

			$meal_index = 0;
			foreach($rows as $row) {
				$meal_data[$meal_index] = array();
				$meal_data[$meal_index]['title'] = $row->title;
				$meal_data[$meal_index]['id'] = $row->recipe_id;
				$meal_data[$meal_index]['tags'] = explode(",", $row->tags);
				$meal_index++;
			}	
		
			$this->view->contentTitle = 'Newest Meals';
			$this->view->meals = $meal_data;
		}
		
		
		//retrieve recipes for a user_id
		if($user_id != null){ 
			$where = $recipe->getAdapter()->quoteInto('user_id = ?', $this->session->user_id).' and status is null';
			$rows = $recipe->fetchAll($where);
		}
		//retrieve all recipes
		else{
			$rows = $recipe->fetchAll('status is null and copied_from_recipe_id is null');
		}

		//array to hold tagless recipes
		$taglessArr = null;
		
		foreach($rows as $row){
			
			$tags = $row->tags;
			//split the string to get each tag
			$tags = explode(",", $tags);
			
			foreach($tags as $tag){
				$tag = strtolower(trim($tag));
				//if recipe has no tag
				if($tag == null || strlen($tag) < 1){
					if($taglessArr != null) array_push($taglessArr, array('title' => $row->title, 'id' => $row->recipe_id));
					else $taglessArr = array(array('title' => $row->title, 'id' => $row->recipe_id));
				}
				//if tag has already been added, append this recipe to the tags recipe array
				else if(array_key_exists($tag, $recipe_data)){
					array_push($recipe_data[$tag], array('title' => $row->title, 'id' => $row->recipe_id));
					sort($recipe_data[$tag]);
				}
				//add new tag with corresponding array to recipe_data array
				else{
					$recipe_data[$tag] = array(array('title' => $row->title, 'id' => $row->recipe_id));
				}			
			}
		}

		//if retrieving user tags
		if($user_id != null) {
			//add tagless if exists
			if($taglessArr != null){
				//sort tagless array
				sort($taglessArr);
				//add tagless array
				$recipe_data['TAGLESS'] = $taglessArr;
			}
			//sort array
			ksort($recipe_data);
			$this->view->tags = $recipe_data;
			$this->view->user_view = true;
			$this->view->tagTitle = "Your Tags";
		}
		//else, retrieving all tags
		else {
			$tags_by_size = array();
			
			$keys = array_keys($recipe_data);
			
			//retrieve top 25 tags
			foreach($keys as $key) { 
				
				$added = false;
				$len = count($recipe_data[$key]);
	
				for($i=0; $i<count($tags_by_size); $i++){
					//if the length of $key is greater than the length 
					//of $tags_by_size[$i] insert $key
					if($len > count($recipe_data[$tags_by_size[$i]])) {
						array_splice($tags_by_size, $i, 0, $key);
						$added=true;
						break;
					}
				}
				if(!$added){ array_push($tags_by_size, $key); }
			}
			
			$top25 = array();
			$size = count($tags_by_size) < 25 ? count($tags_by_size) : 25;
			
			for($i=0; $i<$size; $i++) {
				$top25[$tags_by_size[$i]] = $recipe_data[$tags_by_size[$i]];
			}
			
			$this->view->tags = $top25;
			$this->view->tagTitle = "Popular Tags";
		}
    }
    
	public function copyAction(){
		
		//retrieve meal id
		$filter = new Zend_Filter_StripTags();
		$id = trim($filter->filter($this->_request->getQuery('id')));
		
		//get recipe
		$recipe = new Recipe();
		$where = $recipe->getAdapter()->quoteInto('recipe_id = ?', $id);
		$recipe_row = $recipe->fetchRow($where);
		$data = $this->_getRecipe($recipe_row);
		
		//if user already owns recipe, return
		if($this->session->user_id == $data['recipe']['user_id']) {
			$this->_redirect($this->baseUrl.'/community?user='.$this->session->user_id);
			return;
		}
		
		//update num copies for copied meal
		$data['recipe']['num_copies'] = intval($data['recipe']['num_copies']) + 1;
		$where = $recipe->getAdapter()->quoteInto('recipe_id = ?', $id);
		$recipe->update($data['recipe'], $where);
		
		//update recipe info
		$data['recipe']['user_id'] = $this->session->user_id;
		$data['recipe']['recipe_id'] = null;
		$data['recipe']['copied_from_recipe_id'] = $id;
		$data['recipe']['num_copies'] = 0;
		
		//add recipe
		$recipe_id = $recipe->insert($data['recipe']);
		$recipe->recipe_id = $recipe_id;
				
		//add ingredients and quantities
		$this->_saveRecipexQuantityxIngredient($recipe_id, $data); 

		//if successful, redirect to meals page
	 	$this->_redirect($this->baseUrl.'/community?user='.$this->session->user_id.'&id='.$recipe_id);
		return;
		
	}
	
    public function addAction(){
    
		// set up an "empty" recipe and ingredients
		$recipe = new Recipe();
		
		//adding meal
		if ($this->_request->isPost()) {
	
			$data = $this->_validateRecipe();
			//if no errors validating fields add recipe
			if ($this->view->errorMsg == null) {
				//make sure recipe with this name does not already exist
				$where = array(
					$recipe->getAdapter()->quoteInto('user_id = ?', $this->session->user_id),
					$recipe->getAdapter()->quoteInto('title = ?', $data['recipe']['title']),
					'status is null'
				);
				$recipe_row = $recipe->fetchRow($where);
				if($recipe_row != null){
					$this->view->errorMsg = 'Meal "'.$data['recipe']['title'].'" already exists, please use a different title.';
				}
				else{
				
					//add recipe
					$recipe_id = $recipe->insert($data['recipe']);
					$recipe->recipe_id = $recipe_id;
					
					//add ingredients and quantities
					$this->_saveRecipexQuantityxIngredient($recipe_id, $data); 
		
					//if successful, redirect to meals page
				 	$this->_redirect($this->baseUrl.'/meals');
					return;
				}
			}
	
			$this->view->recipe_data = $data;		
		}
	

		//populate amounts and ingredients for autocomplete textfields (COPY&PASTE = YUCK!)
    	$ingredient_arr = array();
    	$ingredient = new Ingredient();
    	
		//$ingredient_rs = $ingredient->fetchAll();
		//select only ingredients of a single user
		$select = $ingredient->select()->setIntegrityCheck(false);
		$select
			->distinct()
			->from('ingredient')
			->join('recipexquantityxingredient', 'ingredient.ingredient_id = recipexquantityxingredient.ingredient_id', array()) //empty array will choose no columns from the joined table
			->join('recipe', 'recipexquantityxingredient.recipe_id = recipe.recipe_id', array())
			->join('user', 'recipe.user_id = user.user_id', array())
			->where("user.user_id = '".$this->session->user_id."'");
		$ingredient_rs = $ingredient->fetchAll($select);
		
		$i = 0;
		while($ingredient_rs->valid()){
			$ingredient = $ingredient_rs->current();
			$ingredient_arr[$i] = $ingredient->name;
			$i++;
			$ingredient_rs->next();
		}
    	$this->view->ingredient_data = $ingredient_arr;
	
    	$amount_arr = array();
    	$quantity = new Quantity();
    	//select only quantities of a single user
    	//$quantity_rs = $quantity->fetchAll();
    	$select = $quantity->select()->setIntegrityCheck(false);
    	$select
    	->distinct()
    	->from('quantity')
    	->join('recipexquantityxingredient', 'quantity.quantity_id = recipexquantityxingredient.quantity_id', array()) //empty array will choose no columns from the joined table
    	->join('recipe', 'recipexquantityxingredient.recipe_id = recipe.recipe_id', array())
    	->join('user', 'recipe.user_id = user.user_id', array())
    	->where("user.user_id = '".$this->session->user_id."'");
    	$quantity_rs = $quantity->fetchAll($select);
    	
		$i = 0;
		while($quantity_rs->valid()){
			$amount = $quantity_rs->current();
			if($amount->measure != null) $amount_arr[$i] = $this->_convertd2f($amount->amount).' '.$amount->measure;
			else $amount_arr[$i] = $amount->amount;
			$i++;
			$quantity_rs->next();
		}
    	$this->view->amount_data = $amount_arr;
    	
    	
    	
    	// additional view fields required by form
		$this->view->action = 'add';
    }
    
	public function editAction(){
    
		// set up an "empty" recipe and ingredients
		$recipe = new Recipe();
		
		//updating the meal
		if ($this->_request->isPost()) {
	
			$data = $this->_validateRecipe();
			//if no errors validating fields add recipe
			if ($this->view->errorMsg == null) {
				$recipe_id = $this->_request->getPost('recipe_idTF');
				
				//make sure recipe with this name does not already exist
				$where = array(
					$recipe->getAdapter()->quoteInto('user_id = ?', $this->session->user_id),
					$recipe->getAdapter()->quoteInto('recipe_id != ?', $recipe_id),
					$recipe->getAdapter()->quoteInto('title = ?', $data['recipe']['title']),
					'status is null'
				); 
				$recipe_row = $recipe->fetchRow($where);
				if($recipe_row != null){
					$this->view->errorMsg = 'Meal "'.$data['recipe']['title'].'" already exists, please use a different title.';
					$data['recipe']['recipe_id'] = $recipe_id;
				}
				else{
										
					//update recipe
					$where = $recipe->getAdapter()->quoteInto('recipe_id = ?', $recipe_id);
					$recipe->update($data['recipe'], $where);
	
					//delete old recipexquantityxingredients
					$rxqxi = new RecipexQuantityxIngredient();
					$where = $rxqxi->getAdapter()->quoteInto('recipe_id = ?', $recipe_id);
					$rxqxi->delete($where);
					
					$this->_saveRecipexQuantityxIngredient($recipe_id, $data);
		
					//if successful, redirect to main page
				 	$this->_redirect($this->baseUrl.'/meals');
					return;
				}
			}
	
			$this->view->recipe_data = $data;		
		}
		//get meal to display in edit form
		else{
			$filter = new Zend_Filter_StripTags();
			$id = trim($filter->filter($this->_request->getQuery('id')));
			if($id != null){
				$where = $recipe->getAdapter()->quoteInto('recipe_id = ?', $id);
				$row = $recipe->fetchRow($where);
		
				$data = $this->_getRecipe($row);
				$this->view->recipe_data = $data;
		
			}
		}
	

		//populate amounts and ingredients for autocomplete textfields (COPY&PASTE = YUCK!)
    	$ingredient_arr = array();
    	$ingredient = new Ingredient();
		$ingredient_rs = $ingredient->fetchAll();
		$i = 0;
		while($ingredient_rs->valid()){
			$ingredient = $ingredient_rs->current();
			$ingredient_arr[$i] = $ingredient->name;
			$i++;
			$ingredient_rs->next();
		}
    	$this->view->ingredient_data = $ingredient_arr;
	
    	$amount_arr = array();
    	$quantity = new Quantity();
		$quantity_rs = $quantity->fetchAll();
		$i = 0;
		while($ingredient_rs->valid()){
			$amount = $quantity_rs->current();
			if($amount->measure != null) $amount_arr[$i] = $this->_convertd2f($amount->amount).' '.$amount->measure;
			else $amount_arr[$i] = $amount->amount;
			$i++;
			$quantity_rs->next();
		}
    	$this->view->amount_data = $amount_arr;
    	
    	
    	
    	// additional view fields required by form
		$this->view->action = 'edit';
		$this->_helper->viewRenderer->setNoRender();
		$this->render('add');
    }
    
	public function deleteAction(){
    
		// set up an "empty" recipe and ingredients
		$recipe = new Recipe();
		
		$filter = new Zend_Filter_StripTags();
		$id = trim($filter->filter($this->_request->getQuery('id')));
		if($id != null){
			$where = array($recipe->getAdapter()->quoteInto('recipe_id = ?', $id),
						$recipe->getAdapter()->quoteInto('user_id = ?', $this->session->user_id)				
			);
			$row = $recipe->fetchRow($where);
		
			//mark recipe as deleted
			$row->status = 'D';
			$row->save();
		
		}
	
		//if successful, redirect to main page
		$this->_redirect($this->baseUrl.'/main');
		return;
    }

    public function logoutAction(){
    
		$this->session->user_email = null;
		$this->_redirect($this->baseUrl);
    }

    public function aboutAction(){
    
    }
    
    public function helpAction(){

    }
    
    public function mainAction(){
    
    	//variable determining if this is an AJAX call
    	$isAJAX = false;
    
    	//get date in format 'Y-m-d'
    	$date = $this->_request->getQuery("date");
    	if($date == null) $date = new DateTime();
    	else{
    		$date = new DateTime($date);
    		$isAJAX = true;
    	}
    	//get meal type (breakfast, lunch, or dinner)
    	$meal_type = $this->_request->getQuery("meal");   		
    	if($meal_type != "B" && $meal_type != "L") $meal_type = "D";
    		
    	//retrieve options to determine if manual or auto meals
		$data = array();
		$options = new Options();
    	//get options
		$where = array(
			$options->getAdapter()->quoteInto('user_id = ?', $this->session->user_id),
			$options->getAdapter()->quoteInto('meal_type = ?', $meal_type)
		);

		$options_row = $options->fetchRow($where);
		
		//auto generation
		if($options_row->meal_generation == "A"){
			//select meals from auto_menu
			$auto_menu = new AutoMenu();
			$where = array(
				$auto_menu->getAdapter()->quoteInto('user_id = ?', $this->session->user_id),
				$auto_menu->getAdapter()->quoteInto('meal_type = ?', $meal_type),
				"date = '".date_format($date, "Y-m-d")."'"
			);
			$row = $auto_menu->fetchRow($where);
			
			//recipe id of recipe for the given day
			$recipe_id = null;
			
			//generate menu if meal does not exist for given day
			if($row == null){
				$recipe_id = $this->_generateMenu($this->session->user_id, $date, $meal_type);
			}
			else{
				$recipe_id = $row->recipe_id;
			}
			
			//get recipe data
			$recipe = new Recipe();
			$where = $recipe->getAdapter()->quoteInto('recipe_id = ?', $recipe_id);
			$row = $recipe->fetchRow($where);
			//populate recipe data
			if($row != null){
				$data = $this->_getRecipe($row);
				$this->view->recipe_data = $data;
			}

		}
		//custom chosen menu
		else{
			//select meals from manual_meal table
			$manual_meal = new ManualMeal();
			$where = array(
				$manual_meal->getAdapter()->quoteInto('options_id = ?', $options_row->options_id),
				"date = '".date_format($date, "Y-m-d")."'"
			);
			$row = $manual_meal->fetchRow($where);
			//if menu item exists, grab recipe
			if($row != null){
				$recipe = new Recipe();
				$where = $recipe->getAdapter()->quoteInto('recipe_id = ?', $row->recipe_id);
				$row = $recipe->fetchRow($where);
				//populate recipe data
				if($row != null){
					$data = $this->_getRecipe($row);
					$this->view->recipe_data = $data;
				}
			}
		}
		
		$this->view->editable = true;
		
		if($isAJAX){
			$this->_helper->viewRenderer->setNoRender();
			if($this->view->recipe_data != null){ $this->render('meal'); }
			return;
		}

    }


    public function optionsAction(){
    
		$data = array();
		$data['user_id'] = $this->session->user_id;
    

		$options = new Options();
    	//get options_id
		$where = $options->getAdapter()->quoteInto('user_id = ?', $data['user_id']);
		//set options
		$optionsRS = $options->fetchAll($where);
		$breakfast_options = $optionsRS->current();
		$optionsRS->next();
		$lunch_options = $optionsRS->current();
		$optionsRS->next();
		$dinner_options = $optionsRS->current();
				
		
    	if ($this->_request->isPost()) {
    	

	    	$filter = new Zend_Filter_StripTags();
		
	    	//meal_type
	    	$meal = trim($filter->filter($this->_request->getPost('perDayMealCB')));
	    	if($meal == "B"){
	    		$data['options_id'] = $breakfast_options->options_id;
	    	}
	    	else if($meal == "L"){
	    		$data['options_id'] = $lunch_options->options_id;
	    	}
	    	else if($meal == "D"){
	    		$data['options_id'] = $dinner_options->options_id;
	    	}
	    	
	    	$data['meal_type'] = $meal;
	    	
	    	//meal_generation
			$val = trim($filter->filter($this->_request->getPost('autoChkBox')));

			//(A)uto generate meals
			if($val == "on"){
				$data['meal_generation'] = "A";
				
				//auto_options
				$val = trim($filter->filter($this->_request->getPost('autoChooseChkBox')));
				
				//(C)hoose meals automatically
				if($val == "on"){
					$data['auto_options'] = "C";
					
					//update options
					$where = $options->getAdapter()->quoteInto('options_id = ?', $data['options_id']);
					$options->update($data, $where);
				}
				//(A)dditional meal options
				else{
					//auto_meal (TABLE)
					$auto_meal = new AutoMeal();
					
					$data['auto_options'] = "A";
					
					//dish_order
					$val = trim($filter->filter($this->_request->getPost('shuffleChkBox')));
					
					//(S)huffle dish order
					if($val == "on"){
						$data['dish_order'] = "S";
					}
					//(M)aintain dish order
					else{
						$data['dish_order'] = "M";
					}
					
					//validate input
					
					//select input
					$tags = array();
					$tags[0] = trim($filter->filter($this->_request->getPost('sundayTF')));
					$tags[1] = trim($filter->filter($this->_request->getPost('mondayTF')));
					$tags[2] = trim($filter->filter($this->_request->getPost('tuesdayTF')));
					$tags[3] = trim($filter->filter($this->_request->getPost('wednesdayTF')));
					$tags[4] = trim($filter->filter($this->_request->getPost('thursdayTF')));
					$tags[5] = trim($filter->filter($this->_request->getPost('fridayTF')));
					$tags[6] = trim($filter->filter($this->_request->getPost('saturdayTF')));
					
					//should have a tag for each day
					$sum = 0;
					for($i=0; $i<7; $i++){
						if(strlen($tags[$i]) < 1){
							$this->view->errorMsg = 'Please enter an tag for each day.';
							break;
						}
					}
					
					//do not update or delete if there is no error
					if($this->view->errorMsg == null){
						//update options
						$where = $options->getAdapter()->quoteInto('options_id = ?', $data['options_id']);
						$options->update($data, $where);
										
						//delete all old auto_meal records
						$where = $auto_meal->getAdapter()->quoteInto('options_id = ?', $data['options_id']);
						$auto_meal->delete($where);
					}
					
					$addnl_options = array();

					$days = array();
					$days[0] = 'sunday';
					$days[1] = 'monday';
					$days[2] = 'tuesday';
					$days[3] = 'wednesday';
					$days[4] = 'thursday';
					$days[5] = 'friday';
					$days[6] = 'saturday';				
					
					for($i=0; $i<7; $i++){
						//only insert if there is no error
						if($this->view->errorMsg == null){	
							//insert record into auto_meal for each tag
							$dish = array('options_id' => $data['options_id'], 'tags' => $tags[$i], 'day' => $i);
							$auto_meal->insert($dish);
						}
						$addnl_options[$days[$i]] = $tags[$i];
					}
					
					$data['addnl_options'] = $addnl_options;
				}
				
				//delete records from auto_menu and mealgen_history table so meals are regenerated again
				if($this->view->errorMsg == null){ 
					$date = new DateTime();
					$dateArr = getdate((int) $date->format('U'));
					$dow = $dateArr['wday'];
					if($dow > 0) $date->modify("-".$dow." day"); 
					
					$auto_menu = new AutoMenu();
					$where = array(
						$auto_menu->getAdapter()->quoteInto('user_id = ?', $data['user_id']),
						$auto_menu->getAdapter()->quoteInto('meal_type = ?', $data['meal_type']),
						"date >= '".date_format($date, "Y-m-d")."'"
					);
					$auto_menu->delete($where);
					
					$mealgen = new MealGenHistory();
					$where = array(
						$mealgen->getAdapter()->quoteInto('user_id = ?', $data['user_id']),
						$mealgen->getAdapter()->quoteInto('meal_type = ?', $data['meal_type']),
						"date >= '".date_format($date, "Y-m-d")."'"
					);
					$mealgen->delete($where);
				}
				
			}
			//(C)ustom generate meals
			else{
				$data['meal_generation'] = "C";
				
				//validate input
					
				//select input
				$title = array();
				$title[0] = trim($filter->filter($this->_request->getPost('c_sundayTF')));
				$title[1] = trim($filter->filter($this->_request->getPost('c_mondayTF')));
				$title[2] = trim($filter->filter($this->_request->getPost('c_tuesdayTF')));
				$title[3] = trim($filter->filter($this->_request->getPost('c_wednesdayTF')));
				$title[4] = trim($filter->filter($this->_request->getPost('c_thursdayTF')));
				$title[5] = trim($filter->filter($this->_request->getPost('c_fridayTF')));
				$title[6] = trim($filter->filter($this->_request->getPost('c_saturdayTF')));
				
				for($i=0; $i<7; $i++){
					if(strlen($title[$i]) > 0){
						//find recipe by title
						$recipe = new Recipe();
						$where = $recipe->getAdapter()->quoteInto('title = ?', $title[$i]);
						$row = $recipe->fetchRow($where);
						//if recipe does not exist, error
						if($row == null){
							$this->view->errorMsg = 'There is no meal with name "'.$title[$i].'", please enter a valid meal.';
							break;
						}
					}
				}
				
				//only update if there is no error
				if($this->view->errorMsg == null){
					//update options
					$where = $options->getAdapter()->quoteInto('options_id = ?', $data['options_id']);
					$options->update($data, $where);
				}
				
				//manual_meal (TABLE)
				$manual_meal = new ManualMeal();
				$manual_options = array();
					
				$date = trim($filter->filter($this->_request->getPost('customCalendar')));
				$date = new DateTime($date);

				$days = array();
				$days[0] = 'sunday';
				$days[1] = 'monday';
				$days[2] = 'tuesday';
				$days[3] = 'wednesday';
				$days[4] = 'thursday';
				$days[5] = 'friday';
				$days[6] = 'saturday';	
					
				for($i=0; $i<7; $i++){
					//only delete if there is no error
					if($this->view->errorMsg == null){
						//delete old manual_meal record
						$where = array( 
							$manual_meal->getAdapter()->quoteInto('options_id = ?', $data['options_id']),
							"date = '".date_format($date, "Y-m-d")."'"			
						);
						$manual_meal->delete($where);
					}
					
					if(strlen($title[$i]) > 0){
						//find recipe by title
						$recipe = new Recipe();
						$where = $recipe->getAdapter()->quoteInto('title = ?', $title[$i]);
						$row = $recipe->fetchRow($where);
		
						$meal = array();
						//only insert if there is no error
						if($this->view->errorMsg == null){
							$meal['options_id'] = $data['options_id'];
							$meal['recipe_id'] = $row->recipe_id;
							$meal['date'] = date_format($date, "Y-m-d");
							$manual_meal_id = $manual_meal->insert($meal);
							$meal['manual_meal_id'] = $manual_meal_id;
						}
						$meal['title'] = $title[$i];
						$manual_options[$days[$i]] = $meal;

					}
					$date->modify("+1 day");
				}
				
				$data['manual_options'] = $manual_options;
    		}
    		
    		$this->view->options_data = $data;
    	}
    	//get user options
    	else{
    		
    		$options_id = $this->_request->getQuery("options_id"); 
    		$date = $this->_request->getQuery("date");
    		
    		//AJAX call to retrieve manual meals for a given week
    		if($options_id != null && $date != null){
    			//disable autorendering for this action only:
	        	$this->_helper->viewRenderer->setNoRender();
	        	
	        	
    			//get date of first day of the week
    			$startDate = new DateTime($date);
    			$stopDate = new DateTime($date);
 				$stopDate->modify("+6 day");
    			
    			//get manual_meal records
				$manual_meal = new ManualMeal();

				$where = array(
					$manual_meal->getAdapter()->quoteInto('options_id = ?', $options_id),
					"date between '".date_format($startDate, "Y-m-d")."' and '".date_format($stopDate, "Y-m-d")."'"
				);
				
				$manualRS = $manual_meal->fetchAll($where);
				$meal = array();
				for($i=1; $i<8; $i++) $meal[$i] = "";
					
				while($manualRS->valid()){
					$row = $manualRS->current();

					$i = (int) date_format(new DateTime($row->date), "N");
				
					//find recipe by title
					$recipe = new Recipe();
					$where = $recipe->getAdapter()->quoteInto('recipe_id = ?', $row->recipe_id);
					$row = $recipe->fetchRow($where);
					if($row) $meal[$i] = $row->title;
						
					$manualRS->next();
				}
				
				$text = "<meals>".$meal[1].",".$meal[2].",".$meal[3].",".$meal[4].",".$meal[5].",".$meal[6].",".$meal[7]."</meals>";
	        	
				$this->_response->setBody($text);
    		
    			return;
    		}
    	
    	
			//get
    		$meal_type = $this->_request->getQuery("meal");   		
    		if($meal_type != "B" && $meal_type != "L") $meal_type = "D";    		
    		
    		$meal = null;
    		if($meal_type == "B") $meal = $breakfast_options;
    		else if($meal_type == "L") $meal = $lunch_options;
    		else if($meal_type == "D") $meal = $dinner_options;
    		
    		$data['options_id'] = $meal->options_id;
    		$data['meal_type'] = $meal_type;
    		$data['meal_generation'] = $meal->meal_generation;
    		
    		//auto meal generation
    		if($data['meal_generation'] == "A"){
    			$data['auto_options'] = $meal->auto_options;
    			if($data['auto_options'] == "A"){
    				$data['dish_order'] = $meal->dish_order;
    				
    				//get auto_meal records
					$auto_meal = new AutoMeal();
					
					$where = $auto_meal->getAdapter()->quoteInto('options_id = ?', $meal->options_id);
					$autoRS = $auto_meal->fetchAll($where, 'day');
								
					$addnl_options = array();
					$days = array();
					$days[0] = 'sunday';
					$days[1] = 'monday';
					$days[2] = 'tuesday';
					$days[3] = 'wednesday';
					$days[4] = 'thursday';
					$days[5] = 'friday';
					$days[6] = 'saturday';
					
					
	    			while($autoRS->valid()){
	    				$row = $autoRS->current();
	    				$addnl_options[$days[(int) $row->day]] = $row->tags;
						$autoRS->next();
		    		}
		    		
		    		$data['addnl_options'] = $addnl_options;
    			}
    		}
    		//manual generation
    		else{
    			$manual_options = array();
    			
    			//get date of first day of the week
    			$startDate = new DateTime();
    			$stopDate = new DateTime();
    			$dateArr = getdate((int) $startDate->format('U'));
    			$dow = $dateArr['wday'];

				if($dow > 0) $startDate->modify("-".$dow." day");
				if($dow < 6) $stopDate->modify("+".(6-$dow)."day");
    			
    		}
    		
    		$this->view->options_data = $data;
    	}
    	
    	//populate recipes for autocomplete textfields when adding meals manually
    	$recipe_arr = array();
    	$recipe = new Recipe();
		$where = 'status is null';
		$recipe_rs = $recipe->fetchAll($where);
		$i = 0;
		while($recipe_rs->valid()){
			$recipe = $recipe_rs->current();
			$recipe_arr[$i] = $recipe->title;
			$i++;
			$recipe_rs->next();
		}
    	$this->view->options_data['recipe_arr'] = $recipe_arr;
    	
    	$this->view->action = 'options';
    }

    
    public function registerAction(){

		// set up an "empty" user
		$user = new User();
	
	   	if ($this->_request->isPost()) {
	
			$data = $this->_validateUser();
			//if no errors validating fields insert user
			if ($this->view->errorMsg == null) {
	
				//verify user is not already registered
				$user_row = $user->fetchRow($user->select()->where('email = ?', $data['email']));
			
				//user already registered
				if($user_row != null){
					$this->view->errorMsg = 'Email address has already been registered.';
				}
				else{
					$user_data = array(
						'email' => $data['email'],
						'pass' => $data['pass'],
				        'name' => $data['name'],
						'url' => $data['url']
					);
		
					$user_id = $user->insert($user_data);
					$user_data['id'] = $user_id;
					
					//create default options
					$options = new Options();
					//breakfast
					$options_data = array();
					$options_data['user_id'] = $user_id;
					$options_data['meal_type'] = 'B';
					$options_data['meal_generation'] = 'A';
					$options_data['auto_options'] = 'C';
					$options->insert($options_data);
					//lunch
					$options_data['meal_type'] = 'L';
					$options->insert($options_data);
					//dinner
					$options_data['meal_type'] = 'D';
					$options->insert($options_data);
					
					//if successful, redirect to main page
					$this->session->user_email = $data['email'];
					$this->session->user_id = $user_id;
				 	$this->_redirect($this->baseUrl.'/main');
					return;
				}
			}
	
			$this->view->user_data = $data;
			
		}
	
	
		// additional view fields required by form
		$this->view->action = 'register';
		$this->view->buttonText = 'Register';

    }

    public function profileAction(){

	   	if ($this->_request->isPost()) {
			// set up an "empty" user
			$user = new User();
	
			$data = $this->_validateUser();
			//if no errors validating fields update user
			if ($this->view->errorMsg == null) {
	
				$user_data = array(
					'email' => $data['email'],
					'pass' => $data['pass'],
			        'name' => $data['name'],
					'url' => $data['url'],
					'id' => $this->view->user->user_id
				);
	
				$where = $user->getAdapter()->quoteInto('user_id = ?', $this->view->user->user_id);
				$user->update($user_data, $where);
			}
	
	
			$this->view->user_data = $data;
		}
	
		$this->view->action = 'profile';
		$this->view->buttonText = 'Update';

    }

    public function shoppinglistAction(){
    
    	//variable determining if this is an AJAX call
    	$isAJAX = false;
    	
    	//get date in format 'Y-m-d'
    	$date_str = $this->_request->getQuery("date");
    	//if no date param, get first day of week
    	if($date_str == null){
    		$date = new DateTime();
    		$dateArr = getdate((int) $date->format('U'));
    		$dow = $dateArr['wday'];
			if($dow > 0) $date->modify("-".$dow." day");
    	}
    	else{
    		$date = new DateTime($date_str);
    		$isAJAX = true;
    	}
    	
    	
    	//get date of first day of the week
    	$startDate = new DateTime($date->format("Y-m-d"));
    	$stopDate = new DateTime($date->format("Y-m-d"));
 		$stopDate->modify("+6 day");

 		$mappings = array('one','two','three','four','five','six','seven','eight','nine','ten','eleven','twelve','thirteen','fourteen','fifteen');
		$ingredients_data = array();	
 		$cnt = 0;
 		    
		//get options, should be 3 records, one for each meal_type
		$options = new Options();
		$where = $options->getAdapter()->quoteInto('user_id = ?', $this->session->user_id);

		$options_row = $options->fetchAll($where);
		while($options_row->valid()){

			$row = $options_row->current();	
			$options_row->next();	
			$menu_rs = null;
			
			//auto generation
			if($row->meal_generation == "A"){
				//select meals from auto_menu
				$auto_menu = new AutoMenu();
				$where = array(
					$auto_menu->getAdapter()->quoteInto('user_id = ?', $this->session->user_id),
					$auto_menu->getAdapter()->quoteInto('meal_type = ?', $row->meal_type),
					"date between '".$startDate->format("Y-m-d")."' and '".$stopDate->format("Y-m-d")."'"
				);
				$menu_rs = $auto_menu->fetchAll($where);
					
				//generate menu if meal does not exist for given day
				if(!$menu_rs->valid()){
					$this->_generateMenu($user_row->user_id, $date, $row->meal_type);
					//retrieve menu
					$where = array(
						$auto_menu->getAdapter()->quoteInto('user_id = ?', $this->session->user_id),
						$auto_menu->getAdapter()->quoteInto('meal_type = ?', $row->meal_type),
						"date between '".date_format($startDate, "Y-m-d")."' and '".date_format($stopDate, "Y-m-d")."'"
					);
					$menu_rs = $auto_menu->fetchAll($where);
				}
			}
			//custom chosen menu
			else{
				//select meals from manual_meal table
				$manual_meal = new ManualMeal();
				$where = array(
					$manual_meal->getAdapter()->quoteInto('options_id = ?', $row->options_id),
					"date between '".date_format($startDate, "Y-m-d")."' and '".date_format($stopDate, "Y-m-d")."'"
				);
				$menu_rs = $manual_meal->fetchAll($where);
			}
			
			while($menu_rs->valid()){
				$menu = $menu_rs->current();
				$menu_rs->next();
					
				//get recipe data
				$recipe = new Recipe();
				$where = $recipe->getAdapter()->quoteInto('recipe_id = ?', $menu->recipe_id);
				$recipe_row = $recipe->fetchRow($where);
				//populate recipe data
				if($recipe_row != null){
					$data = $this->_getRecipe($recipe_row);
					//get ingredients from recipe
					for($i=0; $i<15; $i++){
						if(array_key_exists($mappings[$i], $data)){
							$ingredient = $data[$mappings[$i]]['ingredient'];
							$full_text = $data[$mappings[$i]]['amount'];
							$amount = $data[$mappings[$i]]['amount_decimal'];
							$measure = $data[$mappings[$i]]['measure'];

							$sum = null;
							
							if(array_key_exists($ingredient, $ingredients_data)) $sum = $ingredients_data[$ingredient];
								
							//unknown
							if($amount == null && $measure == null){
								if($sum != null) $sum[$full_text] = $full_text;
								else $sum = array($full_text => $full_text);
							}
							else if($measure == null || $measure == ''){
								if($sum != null && array_key_exists('NONE', $sum)) $sum['NONE'] = $sum['NONE'] + $amount;
								else if($sum != null) $sum['NONE'] = $amount;
								else $sum = array('NONE' => $amount);
							}
							else{
								//find measurement
								$measurement = null;
								//convert pound to ounce
								if(strcasecmp($measure, 'pound') == 0 || strcasecmp($measure, 'lb') == 0 || strcasecmp($measure, 'lbs') == 0 || strcasecmp($measure, 'lb.') == 0 || strcasecmp($measure, 'lbs.') == 0) $measurement = Zend_Measure_Cooking_Weight::POUND;
								else if(strcasecmp($measure, 'oz') == 0 ||  strcasecmp($measure, 'oz.') == 0 || strcasecmp($measure, 'ounce') == 0 || strcasecmp($measure, 'ounces') == 0) $measurement = Zend_Measure_Cooking_Volume::OUNCE;
								else if(strcasecmp($measure, 'c') == 0 || strcasecmp($measure, 'c.') == 0 || strcasecmp($measure, 'cup') == 0 ||  strcasecmp($measure, 'cups') == 0) $measurement = Zend_Measure_Cooking_Volume::CUP_US;
								else if(strcasecmp($measure, 'g') == 0 || strcasecmp($measure, 'gal') == 0 || strcasecmp($measure, 'g.') == 0 || strcasecmp($measure, 'gal.') == 0 || strcasecmp($measure, 'gallon') == 0 || strcasecmp($measure, 'gallons') == 0) $measurement = Zend_Measure_Cooking_Volume::GALLON_US;
								else if(strcasecmp($measure, 'lt') == 0 || strcasecmp($measure, 'ltr') == 0 || strcasecmp($measure, 'lt.') == 0 || strcasecmp($measure, 'ltr.') == 0 || strcasecmp($measure, 'liter') == 0 || strcasecmp($measure, 'liters') == 0) $measurement = Zend_Measure_Cooking_Volume::LITER;
								else if(strcasecmp($measure, 'qt') == 0 || strcasecmp($measure, 'qt.') == 0 || strcasecmp($measure, 'quart') == 0 || strcasecmp($measure, 'quarts') == 0) $measurement = Zend_Measure_Cooking_Volume::QUART;
								else if(strcasecmp($measure, 'tsp') == 0 ||  strcasecmp($measure, 'tsp.') == 0 || strcasecmp($measure, 'teaspoon') == 0 || strcasecmp($measure, 'teaspoons') == 0) $measurement = Zend_Measure_Cooking_Volume::TEASPOON_US;
								else if(strcasecmp($measure, 'tbsp') == 0 ||  strcasecmp($measure, 'tbsp.') == 0 || strcasecmp($measure, 'tbs') == 0 || strcasecmp($measure, 'tbs.') == 0 || strcasecmp($measure, 'tablespoon') == 0 || strcasecmp($measure, 'tablespoons') == 0) $measurement = Zend_Measure_Cooking_Volume::TABLESPOON_US;

								if($measurement != null){
									$locale = new Zend_Locale('en');
									if($measurement == Zend_Measure_Cooking_Weight::POUND) $new = new Zend_Measure_Cooking_Weight($amount, $measurement, $locale);
									else $new = new Zend_Measure_Cooking_Volume($amount, $measurement, $locale);
									
									if($sum != null && array_key_exists($measurement, $sum)) $sum[$measurement] = $sum[$measurement]->add($new);
									else if($sum != null) $sum[$measurement] = $new;
									else $sum = array($measurement => $new);
								}
								//unknown measurement
								else{
									if($sum != null && array_key_exists($measure, $sum)) $sum[$measure] = $sum[$measure] + $amount;
									else if($sum != null) $sum[$measure] = $amount;
									else $sum = array($measure => $amount);
								}
							}
							
							$ingredients_data[$ingredient] = $sum;
						}
					}
				}
			}
			
			$keysArr = array_keys($ingredients_data);
			$data = array();
			for($i=0; $i<count($keysArr); $i++){

				$sum = $ingredients_data[$keysArr[$i]];
				$sumKeysArr = array_keys($sum);
				$ingredientStr = '';
				for($j=0; $j<count($sumKeysArr); $j++){
					if($sum[$sumKeysArr[$j]] instanceof Zend_Measure_Abstract){

						$val = $sum[$sumKeysArr[$j]]->toString();
						$arr = explode(' ', $val);
						$ingredientStr = $ingredientStr.$this->_convertd2f($arr[0]).' '.$arr[1].', ';
					}
					else $ingredientStr = $ingredientStr.$sum[$sumKeysArr[$j]].', ';
				}
				//remove trailing ' ,'
				$data[$keysArr[$i]] = substr($ingredientStr, 0, strlen($ingredientStr)-2);
			}
			
			$this->view->ingredients_data = $data;
		}
		
    	if($isAJAX){
			$this->_helper->viewRenderer->setNoRender();
			if($this->view->ingredients_data != null) $this->render('slist');
			return;
		}
    }




    private function _validateUser(){

		$filter = new Zend_Filter_StripTags();
	
		$email = trim($filter->filter($this->_request->getPost('emailTF')));
		$email2 = trim($filter->filter($this->_request->getPost('email2TF')));
		$pass = trim($filter->filter($this->_request->getPost('passTF')));
		$pass2 = trim($filter->filter($this->_request->getPost('pass2TF')));
		$name = trim($filter->filter($this->_request->getPost('nameTF')));
		$website = trim($filter->filter($this->_request->getPost('websiteTF')));
	
	        if($website == '') $website = null;
	
		$data = array(
			'email' => $email,
			'email2' => $email2,
			'pass' => $pass,
			'pass2' => $pass2,
	        'name' => $name,
			'url' => $website
		);
	
	
		//if required fields populated, insert
		if ($email != '' && $email2 != '' && $pass != '' && $pass2 != '') {
				
			$atpos = strpos($email, "@");
			$dotpos = strpos($email, ".");
	
			//check for valid email syntax
			if(!($atpos && $dotpos && $atpos < $dotpos)){
				$this->view->errorMsg = 'Invalid Email, Please Enter Valid Address';
			}
			//check for min password length
			else if(strlen($pass) < 8){
				$this->view->errorMsg = 'Invalid Password, Password Must Be At Least 8 Characters.';
			}
			//validate email
			else if(strcmp($email, $email2) != 0){
				$this->view->errorMsg = 'Confirmation Email Does Not Match.';
			}
			//validate pass
			else if(strcmp($pass, $pass2) != 0){
				$this->view->errorMsg = 'Confirmation Password Does Not Match.';
			}
			//valid input
			else{
				$this->view->errorMsg = null;
			}
		}
		else{
			$this->view->errorMsg = 'Please Complete Required Fields';
		}
	
		return $data;

    }


    private function _validateRecipe(){

		$filter = new Zend_Filter_StripTags();
	
		$id = trim($filter->filter($this->_request->getPost('recipe_idTF')));
		$meal_type = trim($filter->filter($this->_request->getPost('perDayMealCB')));
		$title = trim($filter->filter($this->_request->getPost('titleTF')));
		$tags = trim($filter->filter($this->_request->getPost('tagsTF')));
		$instructions = trim($filter->filter($this->_request->getPost('instructionsTA')));
		$url = trim($filter->filter($this->_request->getPost('urlTF')));
		$breakfast = (strcmp(trim($filter->filter($this->_request->getPost('breakfastBTN'))), "on") == 0);
		$lunch = (strcmp(trim($filter->filter($this->_request->getPost('lunchBTN'))), "on") == 0);
		$dinner = (strcmp(trim($filter->filter($this->_request->getPost('dinnerBTN'))), "on") == 0);
		
		
		
		$data = array(
			'recipe' => array(
					'user_id' => $this->session->user_id,
					'title' => $title,
					'tags' => $tags,
			        'instructions' => $instructions,
				    'url' => $url,
					'breakfast' => (int) $breakfast,
		            'lunch' => (int) $lunch,
		            'dinner' => (int) $dinner
				    )
		);
		
		//add id if in edit mode
		if($id != null && strlen($id) > 0) $data['recipe']['recipe_id'] = $id;
		
		$mappings = array('one','two','three','four','five','six','seven','eight','nine','ten','eleven','twelve','thirteen','fourteen','fifteen');

		for($i=0; $i<15; $i++){
			$ingredient = trim($filter->filter($this->_request->getPost($mappings[$i].'TF')));
			$amt = trim($filter->filter($this->_request->getPost($mappings[$i].'AmtTF')));
			
			$data[$mappings[$i]]['ingredient'] = $ingredient;
			$data[$mappings[$i]]['amount'] = $amt;
			
			if($ingredient != null && strlen($ingredient) > 0 && ($amt == null || strlen($amt) < 1)) $this->view->errorMsg = 'Please add Qty/Amt for Ingredient #'.($i+1);
			else if($amt != null && strlen($amt) > 0 && ($ingredient == null || strlen($ingredient) < 1)) $this->view->errorMsg = 'Ingredient #'.($i+1).' has Qty/Amt but no Ingredient name';
		}
		

		//if required fields populated, insert
		if ($title == null || strlen($title) < 1) {
			$this->view->errorMsg = 'Please Complete Required Fields';
		}
	
		return $data;

    }
    
    private function _getRecipe($row){
    
    	//retrieve ingredients
		$ingredientsRS = $row->findManyToManyRowset('Ingredient', 'RecipexQuantityxIngredient');
		//retrieve quantities
		$quantityRS = $row->findManyToManyRowset('Quantity', 'RecipexQuantityxIngredient');
			
		$data = array(
			'recipe' => array(
				'recipe_id' => $row->recipe_id,
				'user_id' => $row->user_id,
				'title' => $row->title,
				'tags' => $row->tags,
		        'instructions' => $row->instructions,
				'url' => $row->url,
				'breakfast' => $row->breakfast,
		        'lunch' => $row->lunch,
		        'dinner' => $row->dinner,
				'num_copies' => $row->num_copies,
				'copied_from_recipe_id' => $row->copied_from_recipe_id
		    )
		);
	
		$cnt = 0;
		$mappings = array('one','two','three','four','five','six','seven','eight','nine','ten','eleven','twelve','thirteen','fourteen','fifteen');
		while($ingredientsRS->valid() && $quantityRS->valid()){
	
			$ingredient_row = $ingredientsRS->current();
			$data[$mappings[$cnt]]['ingredient'] = $ingredient_row->name;
			$ingredientsRS->next();
			
			$quantity_row = $quantityRS->current();
			$data[$mappings[$cnt]]['amount'] = $quantity_row->full_text;
			$data[$mappings[$cnt]]['amount_decimal'] = $quantity_row->amount;
			$data[$mappings[$cnt]]['amount_fraction'] = $this->_convertd2f($quantity_row->amount);
			$data[$mappings[$cnt]]['measure'] = $quantity_row->measure;
			$quantityRS->next();
			
			$cnt++;
		}
		
		return $data;
    }
    
    
    private function _generateMenu($user_id, $date, $meal_type){
           
    	$options = new Options();
   		//get options
		$where = array(
			$options->getAdapter()->quoteInto('user_id = ?', $user_id),
			$options->getAdapter()->quoteInto('meal_type = ?', $meal_type)
		);

		$options_row = $options->fetchRow($where);

		//only generate the menu if user options for this meal type
		//is set to auto generate
		if($options_row->meal_generation == "A"){
			//get date of first day of the week
			$startDate = new DateTime(date_format($date, "Y-m-d"));
			$dateArr = getdate((int) $startDate->format('U'));
			$dow = $dateArr['wday'];
			if($dow > 0) $startDate->modify("-".$dow." day");
			
			//first, check to see if meals have already been generated
			$auto_menu = new AutoMenu();
			
			$mealgen = new MealGenHistory();
			$where = array(
				$mealgen->getAdapter()->quoteInto('user_id = ?', $user_id),
				$mealgen->getAdapter()->quoteInto('meal_type = ?', $meal_type),
				"date = '".date_format($startDate, "Y-m-d")."'"
			);
			$row = $mealgen->fetchRow($where);
			
			
			//if row is null, menu has not been created
			if($row == null){
				//id of recipe for today to be returned, generated from logic below
				$recipe_id = null;
				$recipe = new Recipe();
				
				
				//generate array with every day of week
				$dates = array();
				for($i=1; $i<8; $i++){
					$dates[$i] = date_format($startDate, "Y-m-d");
					$startDate->modify("+1 day");
				}
				
				$data = array();
				$data['user_id'] = $user_id;
				$data['meal_type'] = $meal_type;
				$meal_col = null;
				if(strcmp($meal_type, "B") == 0) $meal_col = 'breakfast';
				else if(strcmp($meal_type, "L") == 0) $meal_col = 'lunch';
				else if(strcmp($meal_type, "D") == 0) $meal_col = 'dinner';

				
				//insert record into mealgen_history so we know that we tried to auto gen meals
				$data['date'] = $dates[1];
				$mealgen->insert($data);

				
				//determine if auto_options is (c)hoose auto or (a)dditional options
				if($options_row->auto_options == "C"){
					//if auto, generate menu by randomly choosing any recipe for each day of the week
					for($i=1; $i<8; $i++){
						//randomly grab any meal
						$where = array(
							$recipe->getAdapter()->quoteInto('user_id = ?', $user_id),
							$meal_col.' = true',
							'status is null'
						);
						$row = $recipe->fetchRow($where, 'RAND()');

						if($row != null){
							$data['recipe_id'] = $row->recipe_id;
							$data['date'] = $dates[$i];
							$auto_menu->insert($data);
							
							if(date_format($date, "Y-m-d") == $data['date']) $recipe_id = $row->recipe_id;
						}
					}
				}
				//additional options
				else{
					$s = array(1 => 1, 2 => 2, 3 => 3, 4 => 4, 5 => 5, 6 => 6, 7 => 7);
					
					//get auto meal records
					$auto_meal = new AutoMeal();
					$where = $auto_meal->getAdapter()->quoteInto('options_id = ?', $options_row->options_id);
					$autoRS = $auto_meal->fetchAll($where, 'day');
									
					//get tags of meals to use in the menu
					$tags = array();
					$cnt = 1;
		    		while($autoRS->valid()){
						$row = $autoRS->current();
						
						//split tags by comma deliminator
						$tagArr = explode(',', $row->tags);
						//create tag string in format: tags like '%<tag1>%' AND tags like '%<tag2>%'
						$tag = "(tags like '%".trim($tagArr[0])."%'";
						for($k=1; $k<count($tagArr); $k++) $tag = $tag." and tags like '%".trim($tagArr[$k])."%'"; 
						$tag = $tag.')';
						$tags[$cnt] = $tag;
						$cnt++;

						$autoRS->next();
			    	}
	    		
			    	//tag index array
			    	$s = array(1 => 1, 2 => 2, 3 => 3, 4 => 4, 5 => 5, 6 => 6, 7 => 7);
			    		
					//shuffle dish order
					if($options_row->dish_order == "S"){
						//generation permutation tag index arra
						//there are 7! (5040) permutations
						//create permutation of function of week/month/year
						//k = week * year % 5040
						//permutation algorithm: http://en.wikipedia.org/wiki/Permutation (unordered generation)
						$k = ((int) $date->format('W')) * ((int) $date->format('Y')) % 5040;
						$factorial = 1;
						for($j = 2; $j<=7; $j++){
							$factorial = $factorial * ($j-1);
							//swap
							$temp = $s[$j];
							$s[$j] = $s[$j - (($k / $factorial) % $j)];
							$s[$j - (($k / $factorial) % $j)] = $temp;
						}
					}
												
					//generate menu for each day of week using tags retrieved above
					for($i=1; $i<8; $i++){	
						$tag = $tags[$s[$i]];
						//grab meal with tag
						$where = array(
							$recipe->getAdapter()->quoteInto('user_id = ?', $user_id),
							$meal_col.' = true',
							'status is null',
							$tag
						);
						$row = $recipe->fetchRow($where, 'RAND()');
						
						//should we prevent meals from appearing in the
						//menu more than once a week?
						if($row != null){
							$data['recipe_id'] = $row->recipe_id;
							$data['date'] = $dates[$i];
							$auto_menu->insert($data);
							
							if(date_format($date, "Y-m-d") == $data['date']) $recipe_id = $row->recipe_id;
						}
					}
				}
			
				
				return $recipe_id;
			}
		}
    }

	private function _saveRecipexQuantityxIngredient($recipe_id, $data){
		$ingredient = new Ingredient();
		$quantity = new Quantity();
		$rxqxi = new RecipexQuantityxIngredient();
		
		$mappings = array('one','two','three','four','five','six','seven','eight','nine','ten','eleven','twelve','thirteen','fourteen','fifteen');

		for($i=0; $i<15; $i++){
			$ingredient_name = $data[$mappings[$i]]['ingredient'];
			$quantity_full = $data[$mappings[$i]]['amount'];
			if($ingredient_name != null && strlen($ingredient_name) > 0){
				//get ingredient, or add if does not exist
				$where = $ingredient->getAdapter()->quoteInto('name = ?', $ingredient_name);
				$row = $ingredient->fetchRow($where);
							
				$ingredient_id = null;
				if($row != null) $ingredient_id = $row->ingredient_id;
				else $ingredient_id = $ingredient->insert(array('name' => $ingredient_name));
							

				//add quantity
				$quantity_id = null;
				//split quantity string
				$qtyArr = explode(' ', $quantity_full);
				
				//validate
				$valid = true;
				
				//whole number
				if(count($qtyArr) == 1 && is_numeric($qtyArr[0])){}
				//fraction
				else if(count($qtyArr) == 1 && strpos($qtyArr[1], '/') > 0){}
				//whole number and fraction (with no unit of measure, combine whole num and fraction)
				else if(count($qtyArr) == 2 && is_numeric($qtyArr[0]) && strpos($qtyArr[1], '/') > 0) $qtyArr = array($qtyArr[0].' '.$qtyArr[1]);
				//whole number & unit of measure
				else if(count($qtyArr) == 2 && is_numeric($qtyArr[0]) && !is_numeric($qtyArr[1])){}
				//fraction & unit of measure
				else if(count($qtyArr) == 2 && strpos($qtyArr[1], '/') > 0 && !is_numeric($qtyArr[1])){}
				//whole number, fraction, and unit of measure (combine whole num and fraction)
				else if(count($qtyArr) == 3 && is_numeric($qtyArr[0]) && strpos($qtyArr[1], '/') > 0 && !is_numeric($qtyArr[0])) $qtyArr = array($qtyArr[0].' '.$qtyArr[1], $qtyArr[2]);
				//invalid
				else $valid = false;

				//convert amount to decimal equivalent if a fraction
				if($valid) $qtyArr[0] = $this->_convertf2d($qtyArr[0]);
				
				if(count($qtyArr) == 2 && $valid){
					$where = array(
						'amount = '.$qtyArr[0],
						$quantity->getAdapter()->quoteInto('measure = ?', $qtyArr[1])
					);
					$row = $quantity->fetchRow($where);
					if($row != null) $quantity_id = $row->quantity_id;
					else $quantity_id = $quantity->insert(array('amount' => $qtyArr[0], 'measure' => $qtyArr[1], 'full_text' => $quantity_full));
				}
				else if($valid){
					$where = array(
						'amount = '.$qtyArr[0],
						'measure is null'
					);
					$row = $quantity->fetchRow($where);
					if($row != null) $quantity_id = $row->quantity_id;
					else $quantity_id = $quantity->insert(array('amount' => $qtyArr[0], 'full_text' => $quantity_full));
				}
				else{
					$where = array(
					$quantity->getAdapter()->quoteInto('full_text = ?', $quantity_full),
					'amount is null',
					'measure is null'
					);
					$row = $quantity->fetchRow($where);
					if($row != null) $quantity_id = $row->quantity_id;
					else $quantity_id = $quantity->insert(array('full_text' => $quantity_full));
				}
						
				//add recipexquantityxingredient
				$rxqxi->insert(array('recipe_id' => $recipe_id, 'quantity_id' => $quantity_id, 'ingredient_id' => $ingredient_id, 'sequence' => $i));
			}
				
		}
	}
	


	private function _convertf2d ($fraction) {
		$fraction = trim($fraction);
		//extract whole number if exists
		$numArr = explode(' ', $fraction);
		if(count($numArr) > 1){
			$fractArr = explode('/', $numArr[1]);
			return $numArr[0] + ($fractArr[0] / $fractArr[1]);
		}
		else{
			$fractArr = explode('/', $fraction);
			if(count($fractArr) > 1) return $fractArr[0] / $fractArr[1];
			else return $fraction;
		}
		
	}


	
	private function _convertd2f ($decimal) {
	    if ($decimal == 0) {
	      $whole = 0;
	      $numerator = 0;
	      $denominator = 1;
	      $top_heavy = 0;
	    }
	
	    else {
	   	  //round the decimal
	   	  $decimal = round($decimal, 2);
	      //if decimal precision is greater than 2, trim the decimal
	      if(strlen($decimal) - strpos($decimal, '.') > 2){
	      	$decimal = substr($decimal, 0, strpos($decimal, '.')+3);
	      }
	    
	      $sign = 1;
	      if ($decimal < 0) {
	        $sign = -1;
	      }
	
	      if (floor(abs($decimal)) == 0) {
	        $whole = 0;
	        $conversion = abs($decimal);
	      }
	      else {
	        $whole = floor(abs($decimal));
	        $conversion = abs($decimal);
	      }
	
	      $power = 1;
	      $flag = 0;
	      while ($flag == 0) {
	        $argument = $conversion * $power;
	        if ($argument == floor($argument)) {
	          $flag = 1;
	        }
	        else {
	          $power = $power * 10;
	        }
	      }
	
	      $numerator = $conversion * $power;
	      $denominator = $power;
	
	      $hcf = $this->_euclid ($numerator, $denominator);
	
	      $numerator = $numerator/$hcf;
	      $denominator = $denominator/$hcf;
	      $whole = $sign * $whole;
	      $top_heavy = $sign * $numerator;
	
	      $numerator = abs($top_heavy) - (abs($whole) * $denominator);
	
	      if (($whole == 0) && ($sign == -1)) {
	        $numerator = $numerator * $sign;
	      }
	
	
	    }
	    //return array($whole, $numerator, $denominator, $top_heavy);
	    if($whole > 0 && $numerator == 0) return $whole;
	    else if($whole > 0 && $numerator > 0) return $whole.' '.$numerator.'/'.$denominator;
	    else return $numerator.'/'.$denominator;
	  }



   private function _euclid ($number_one, $number_two) {

      if (($number_one == 0) or ($number_two == 0)) {
      	$hcf = 1;
      	return $hcf;
      }
      else {
         if ($number_one < $number_two) {
         $buffer = $number_one;
         $number_one = $number_two;
         $number_two = $buffer;
         }

         $dividend = $number_one;
         $divisor = $number_two;
         $remainder = $dividend;

         while ($remainder > 0) {
           if ((floor($dividend/$divisor)) == ($dividend/$divisor)) {
           $quotient = $dividend/$divisor;
           $remainder = 0;
           }
           else {
           $quotient = floor($dividend/$divisor);
           $remainder = $dividend - ($quotient * $divisor);
           }
           $hcf = $divisor;
           $dividend = $divisor;
           $divisor = $remainder;
         }


      }
   		return $hcf;
   } 
}
?>