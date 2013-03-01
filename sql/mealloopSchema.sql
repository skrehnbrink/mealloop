CREATE TABLE  `mealloop`.`user` (
  `user_id` INTEGER NOT NULL auto_increment,
  `email` varchar(40) default NULL,
  `pass` varchar(40) default NULL,
  `name` varchar(40) default NULL,
  `url` varchar(40) default NULL,
  PRIMARY KEY  (`user_id`)
);


CREATE TABLE  `mealloop`.`recipe` (
  `recipe_id` INTEGER NOT NULL auto_increment,
  `user_id` INTEGER NOT NULL,	
  `breakfast` boolean NOT NULL,
  `lunch` boolean NOT NULL,
  `dinner` boolean NOT NULL,
  `title` varchar(40) default NULL,
  `tags` varchar(1000) default NULL,
  `url` varchar(200) default NULL,
  `instructions` varchar(60000) default NULL,
  `status` varchar(1),
  FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`),
  PRIMARY KEY  (`recipe_id`)
);


CREATE TABLE  `mealloop`.`ingredient` (
  `ingredient_id` INTEGER NOT NULL auto_increment,
  `name` varchar(40) default NULL,
  `tags` varchar(100) default NULL,
  PRIMARY KEY  (`ingredient_id`)
);

CREATE TABLE  `mealloop`.`quantity` (
  `quantity_id` INTEGER NOT NULL auto_increment,
  `amount` DOUBLE default NULL,
  `measure` varchar(40) default NULL,
  `full_text` varchar(40) default NULL,
  PRIMARY KEY  (`quantity_id`)
);


CREATE TABLE  `mealloop`.`recipexquantityxingredient` (
  `recipexquantityxingredient_id` INTEGER NOT NULL auto_increment,
  `recipe_id` INTEGER NOT NULL,
  `quantity_id` INTEGER NOT NULL,
  `ingredient_id` INTEGER NOT NULL,
  `sequence` INTEGER default NULL,
  PRIMARY KEY  (`recipexquantityxingredient_id`),
  FOREIGN KEY (`recipe_id`) REFERENCES `recipe` (`recipe_id`),
  FOREIGN KEY (`quantity_id`) REFERENCES `quantity` (`quantity_id`),
  FOREIGN KEY (`ingredient_id`) REFERENCES `ingredient` (`ingredient_id`)
);


CREATE TABLE  `mealloop`.`options` (
  `options_id` INTEGER NOT NULL auto_increment,
  `user_id` INTEGER NOT NULL,
  `meal_type` varchar(1) NOT NULL,
  `meal_generation` varchar(1) NOT NULL,
  `auto_options` varchar(1),
  `dish_order` varchar(1),
  PRIMARY KEY  (`options_id`) ,
  FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`)
);


CREATE TABLE `mealloop`.`auto_meal` (
  `auto_meal_id` INTEGER NOT NULL AUTO_INCREMENT,
  `options_id` INTEGER NOT NULL,
  `tags` varchar(1000),
  `day` INTEGER,
  PRIMARY KEY (`auto_meal_id`),
  FOREIGN KEY (`options_id`) REFERENCES `options` (`options_id`)
);

CREATE TABLE `mealloop`.`mealgen_history` (
  `mealgen_history_id` INTEGER NOT NULL AUTO_INCREMENT,
  `user_id` INTEGER NOT NULL,
  `date` DATETIME NOT NULL,
  `meal_type` varchar(1) NOT NULL,
  PRIMARY KEY (`mealgen_history_id`),
  FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`)
);

CREATE TABLE `mealloop`.`manual_meal` (
  `manual_meal_id` INTEGER NOT NULL AUTO_INCREMENT,
  `options_id` INTEGER NOT NULL,
  `recipe_id` INTEGER NOT NULL,
  `date` DATETIME NOT NULL,
  PRIMARY KEY (`manual_meal_id`),
  FOREIGN KEY (`options_id`) REFERENCES `options` (`options_id`),
  FOREIGN KEY (`recipe_id`) REFERENCES `recipe` (`recipe_id`)
);


CREATE TABLE  `mealloop`.`auto_menu` (
  `auto_menu_id` INTEGER NOT NULL auto_increment,
  `user_id` INTEGER NOT NULL,
  `recipe_id` INTEGER NOT NULL,
  `meal_type` varchar(1) NOT NULL,
  `date` datetime NOT NULL,
  PRIMARY KEY  (`auto_menu_id`),
  FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`),
  FOREIGN KEY (`recipe_id`) REFERENCES `recipe` (`recipe_id`)
);






