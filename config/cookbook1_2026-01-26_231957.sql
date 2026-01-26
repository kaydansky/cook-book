-- MySQL dump 10.13  Distrib 8.4.3, for Win64 (x86_64)
--
-- Host: 127.0.0.1    Database: cookbook1
-- ------------------------------------------------------
-- Server version	8.4.3

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `category`
--

DROP TABLE IF EXISTS `category`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `category` (
  `category_id` int NOT NULL AUTO_INCREMENT,
  `category_name` varchar(255) DEFAULT NULL,
  `parent_category_id` int DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`category_id`)
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `china`
--

DROP TABLE IF EXISTS `china`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `china` (
  `china_id` int NOT NULL AUTO_INCREMENT,
  `china_name` varchar(255) NOT NULL,
  `restaurant_location` varchar(255) DEFAULT NULL,
  `manufacturer` varchar(255) DEFAULT NULL,
  `manufacturer_name` varchar(255) DEFAULT NULL,
  `dimensions` varchar(255) DEFAULT NULL,
  `underliner` varchar(255) DEFAULT NULL,
  `price` varchar(255) DEFAULT NULL,
  `quantity` int DEFAULT NULL,
  `image_filename` varchar(100) CHARACTER SET latin1 COLLATE latin1_general_cs DEFAULT NULL,
  PRIMARY KEY (`china_id`)
) ENGINE=MyISAM AUTO_INCREMENT=120 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `dish_categories`
--

DROP TABLE IF EXISTS `dish_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `dish_categories` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `dish_id` int unsigned NOT NULL,
  `category_id` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `dish_cat_index` (`dish_id`),
  KEY `dish_category_id` (`category_id`)
) ENGINE=MyISAM AUTO_INCREMENT=174 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `dish_dates`
--

DROP TABLE IF EXISTS `dish_dates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `dish_dates` (
  `dish_date_id` int NOT NULL AUTO_INCREMENT,
  `dish_id` int DEFAULT NULL,
  `date` varchar(150) DEFAULT NULL,
  `alternative_title` varchar(255) DEFAULT NULL,
  `alternative_subtitle` varchar(255) DEFAULT NULL,
  `china_id` int DEFAULT NULL,
  `marking` mediumtext,
  `wine_pairing` mediumtext,
  `alternative_date` datetime DEFAULT NULL,
  PRIMARY KEY (`dish_date_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3073 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `dish_images`
--

DROP TABLE IF EXISTS `dish_images`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `dish_images` (
  `dish_image_id` int NOT NULL AUTO_INCREMENT,
  `dish_id` int NOT NULL,
  `image_id` int DEFAULT NULL,
  `featured` varchar(15) NOT NULL,
  PRIMARY KEY (`dish_image_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1193 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `dish_ingredients`
--

DROP TABLE IF EXISTS `dish_ingredients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `dish_ingredients` (
  `dish_ingredient_id` int NOT NULL AUTO_INCREMENT,
  `dish_id` int NOT NULL,
  `uuid` varchar(36) DEFAULT NULL,
  `ingredient_id` int NOT NULL,
  `quantity` varchar(16) DEFAULT NULL,
  `unit_id` int DEFAULT NULL,
  `comment` mediumtext,
  `ingredient_order` int DEFAULT NULL,
  PRIMARY KEY (`dish_ingredient_id`),
  KEY `dish_ingredient_id` (`ingredient_id`),
  KEY `ingredients_dish_id` (`dish_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3971 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `dish_recipes`
--

DROP TABLE IF EXISTS `dish_recipes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `dish_recipes` (
  `dish_recipe_id` int NOT NULL AUTO_INCREMENT,
  `dish_id` int NOT NULL,
  `recipe_id` int NOT NULL,
  `recipe_option` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`dish_recipe_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4932 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `dish_steps`
--

DROP TABLE IF EXISTS `dish_steps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `dish_steps` (
  `id` int NOT NULL AUTO_INCREMENT,
  `dish_id` int NOT NULL,
  `step_content` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `step_order` int DEFAULT NULL,
  `ingredient_array` varchar(222) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `step_image` varchar(75) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=2414 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `dishes`
--

DROP TABLE IF EXISTS `dishes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `dishes` (
  `dish_id` int NOT NULL AUTO_INCREMENT,
  `dish_title` varchar(255) DEFAULT NULL,
  `dish_subtitle` varchar(255) DEFAULT NULL,
  `description` text,
  `source_link` varchar(255) DEFAULT NULL,
  `foh_kitchen_assembly` mediumtext,
  `foh_dining_assembly` mediumtext,
  `foh_purveyors` mediumtext,
  `allergies` int NOT NULL DEFAULT '0',
  `user_id` int NOT NULL DEFAULT '0',
  `status` varchar(14) DEFAULT NULL,
  `alt_search_terms` varchar(150) DEFAULT NULL,
  `source` varchar(255) DEFAULT NULL,
  `all_categories` varchar(150) DEFAULT NULL,
  `approved` tinyint unsigned NOT NULL DEFAULT '0',
  `approved_by` int unsigned NOT NULL DEFAULT '0',
  `image_filenames` text CHARACTER SET latin1 COLLATE latin1_general_cs,
  `date_added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_modified` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `dish_date` date DEFAULT NULL,
  `notes` mediumtext,
  `china_id` int unsigned DEFAULT NULL,
  `marking` text,
  `wine_pairing` text,
  PRIMARY KEY (`dish_id`),
  KEY `china_id` (`china_id`),
  KEY `dish_date` (`dish_date`),
  KEY `date_added` (`date_added`),
  KEY `date_modified` (`date_modified`),
  KEY `source` (`source`),
  FULLTEXT KEY `dish_title` (`dish_title`),
  FULLTEXT KEY `dish_subtitle` (`dish_subtitle`),
  FULLTEXT KEY `dish_description` (`description`)
) ENGINE=InnoDB AUTO_INCREMENT=4814 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `equipment`
--

DROP TABLE IF EXISTS `equipment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `equipment` (
  `equipment_id` int NOT NULL AUTO_INCREMENT,
  `equipment` varchar(500) NOT NULL,
  `description` mediumtext,
  `supplier` varchar(500) DEFAULT NULL,
  `image_filename` varchar(100) CHARACTER SET latin1 COLLATE latin1_general_cs DEFAULT NULL,
  PRIMARY KEY (`equipment_id`)
) ENGINE=MyISAM AUTO_INCREMENT=135 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `images`
--

DROP TABLE IF EXISTS `images`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `images` (
  `image_id` mediumint NOT NULL AUTO_INCREMENT,
  `image_name` varchar(255) DEFAULT NULL,
  `image_title` varchar(255) DEFAULT NULL,
  `alt_text` varchar(255) DEFAULT NULL,
  `type` varchar(255) NOT NULL,
  `source` varchar(255) DEFAULT NULL,
  `source_link` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`image_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3200 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `recipe_categories`
--

DROP TABLE IF EXISTS `recipe_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `recipe_categories` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `recipe_id` int unsigned NOT NULL,
  `category_id` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `rec_cat_index` (`recipe_id`),
  KEY `recipe_category_id` (`category_id`)
) ENGINE=MyISAM AUTO_INCREMENT=441 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `recipe_equipment`
--

DROP TABLE IF EXISTS `recipe_equipment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `recipe_equipment` (
  `recipe_equipment_id` int unsigned NOT NULL AUTO_INCREMENT,
  `recipe_id` int unsigned NOT NULL,
  `equipment_id` int unsigned NOT NULL,
  `quantity` tinyint unsigned DEFAULT NULL,
  `comment` mediumtext,
  PRIMARY KEY (`recipe_equipment_id`)
) ENGINE=MyISAM AUTO_INCREMENT=1810 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `recipe_images`
--

DROP TABLE IF EXISTS `recipe_images`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `recipe_images` (
  `recipe_image_id` int NOT NULL AUTO_INCREMENT,
  `recipe_id` int NOT NULL,
  `image_id` int NOT NULL,
  `featured` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `image_filename` varchar(100) CHARACTER SET latin1 COLLATE latin1_general_cs DEFAULT NULL,
  PRIMARY KEY (`recipe_image_id`)
) ENGINE=MyISAM AUTO_INCREMENT=1731 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `recipe_ingredients`
--

DROP TABLE IF EXISTS `recipe_ingredients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `recipe_ingredients` (
  `recipe_ingredient_id` int unsigned NOT NULL AUTO_INCREMENT,
  `recipe_id` int unsigned NOT NULL,
  `uuid` varchar(36) DEFAULT NULL,
  `ingredient_id` int unsigned NOT NULL DEFAULT '0',
  `ingredient_recipe_id` int unsigned NOT NULL DEFAULT '0',
  `quantity` float unsigned DEFAULT NULL,
  `yield_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `yield_value` float unsigned DEFAULT NULL,
  `unit_id` int unsigned DEFAULT NULL,
  `comment` mediumtext,
  `step_order` int unsigned DEFAULT NULL,
  `addition_type` varchar(15) DEFAULT NULL,
  `primary_ingredient` set('Yes','No') DEFAULT NULL,
  PRIMARY KEY (`recipe_ingredient_id`),
  KEY `recipe_ingredient_id` (`ingredient_id`),
  KEY `ingredients_recipe_id` (`recipe_id`)
) ENGINE=InnoDB AUTO_INCREMENT=22639 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `recipe_steps`
--

DROP TABLE IF EXISTS `recipe_steps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `recipe_steps` (
  `recipe_step_id` int unsigned NOT NULL AUTO_INCREMENT,
  `recipe_id` int unsigned NOT NULL,
  `step_content` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `step_images` text CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `step_order` int unsigned DEFAULT NULL,
  `ingredient_array` text CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `recipe_array` varchar(222) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  PRIMARY KEY (`recipe_step_id`)
) ENGINE=MyISAM AUTO_INCREMENT=11992 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `recipe_yields`
--

DROP TABLE IF EXISTS `recipe_yields`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `recipe_yields` (
  `recipe_yield_id` int NOT NULL AUTO_INCREMENT,
  `recipe_id` int NOT NULL,
  `unit_id` int DEFAULT NULL,
  `quantity` float DEFAULT NULL,
  `comment` text CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  PRIMARY KEY (`recipe_yield_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1280 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `recipes`
--

DROP TABLE IF EXISTS `recipes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `recipes` (
  `recipe_id` int unsigned NOT NULL AUTO_INCREMENT,
  `recipe_title` varchar(255) DEFAULT NULL,
  `description` mediumtext,
  `source` varchar(255) DEFAULT NULL,
  `source_link` varchar(255) DEFAULT NULL,
  `time_to_prepare` time DEFAULT '00:00:00',
  `prepare_time` time DEFAULT '00:00:00',
  `cooking_time` time DEFAULT '00:00:00',
  `prepare_hours` tinyint DEFAULT '0',
  `prepare_min` tinyint DEFAULT '0',
  `cook_hours` tinyint DEFAULT '0',
  `cook_min` tinyint DEFAULT '0',
  `yield` varchar(255) DEFAULT NULL,
  `yield_value` float(10,2) unsigned NOT NULL DEFAULT '0.00',
  `unit_id` int unsigned NOT NULL DEFAULT '0',
  `yield_unit` varchar(16) DEFAULT NULL,
  `ingredients` mediumtext,
  `Instructions` mediumtext,
  `notes` mediumtext,
  `storage` varchar(255) DEFAULT NULL,
  `oven_temperature` varchar(255) DEFAULT NULL,
  `degreetype` varchar(12) DEFAULT NULL,
  `origin` varchar(255) DEFAULT NULL,
  `date_added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_modified` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `user_id` int NOT NULL,
  `alt_search_terms` text,
  `all_categories` varchar(150) DEFAULT NULL,
  `approved` tinyint unsigned NOT NULL DEFAULT '0',
  `approved_by` int unsigned NOT NULL DEFAULT '0',
  `image_filenames` text CHARACTER SET latin1 COLLATE latin1_general_cs,
  `step_image_filenames` text CHARACTER SET latin1 COLLATE latin1_general_cs,
  PRIMARY KEY (`recipe_id`),
  KEY `date_added` (`date_added`),
  KEY `date_modified` (`date_modified`),
  KEY `source` (`source`),
  FULLTEXT KEY `recipe_title` (`recipe_title`),
  FULLTEXT KEY `recipe_description` (`description`),
  FULLTEXT KEY `alt_search_terms` (`alt_search_terms`)
) ENGINE=InnoDB AUTO_INCREMENT=2837 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `units`
--

DROP TABLE IF EXISTS `units`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `units` (
  `unit_id` int unsigned NOT NULL AUTO_INCREMENT,
  `unit` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT '',
  `unit_name_plural` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `unit_name` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `measurement_system` varchar(256) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `description` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `type` varchar(256) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `mimecode` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `conversion` varchar(20) DEFAULT NULL,
  `conversion_unit` varchar(16) DEFAULT NULL,
  PRIMARY KEY (`unit_id`)
) ENGINE=InnoDB AUTO_INCREMENT=60 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping routines for database 'cookbook1'
--
/*!50003 DROP FUNCTION IF EXISTS `uExtractNumberFromString` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
CREATE DEFINER=`ciaran1`@`localhost` FUNCTION `uExtractNumberFromString`(`in_string` VARCHAR(50)) RETURNS int
    NO SQL
BEGIN

    DECLARE ctrNumber varchar(50);
    DECLARE finNumber varchar(50) default ' ';
    DECLARE sChar varchar(2);
    DECLARE inti INTEGER default 1;

    IF length(in_string) > 0 THEN

        WHILE(inti <= length(in_string)) DO
            SET sChar= SUBSTRING(in_string,inti,1);
            SET ctrNumber= FIND_IN_SET(sChar,'0,1,2,3,4,5,6,7,8,9');

            IF ctrNumber > 0 THEN
               SET finNumber=CONCAT(finNumber,sChar);
            ELSE
               SET finNumber=CONCAT(finNumber,'');
            END IF;
            SET inti=inti+1;
        END WHILE;
        RETURN CAST(finNumber AS SIGNED INTEGER) ;
    ELSE
        RETURN 0;
    END IF;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `split_dishes` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
CREATE DEFINER=`ciaran1`@`localhost` PROCEDURE `split_dishes`()
    NO SQL
BEGIN
  DECLARE rid INT;
  DECLARE recipe VARCHAR(2048);
  DECLARE step INT;
  DECLARE next_step INT;
  DECLARE this_step VARCHAR(256);
  DECLARE finished INT DEFAULT 0;
  DECLARE recipe_cursor CURSOR FOR SELECT DishID, Assembley FROM dishes;
  DECLARE CONTINUE HANDLER FOR NOT FOUND SET finished = 1;
  DROP TABLE IF EXISTS new_recipes;
  CREATE TABLE new_recipes (RecipeID INT, step_num INT, Instruction VARCHAR(2000));
  OPEN recipe_cursor;
  recipe_loop: LOOP
    FETCH recipe_cursor INTO rid, recipe;
    IF finished = 1 THEN
      LEAVE recipe_loop;
    END IF;
    SET step = 1;
    SET next_step = 2;
    WHILE recipe RLIKE CONCAT('^[[:blank:]]*', step, '[[.period.]]') DO
      -- is there a next step?
      IF recipe RLIKE CONCAT('^[[:blank:]]*', step, '[[.period.]] .*', next_step, '[[.period.]]') THEN
        SET this_step = SUBSTRING_INDEX(SUBSTRING_INDEX(recipe, CONCAT(next_step, '. '), 1), CONCAT(step, '. '), -1);
      ELSE
        SET this_step = SUBSTRING_INDEX(recipe, CONCAT(step, '. '), -1);
      END IF;
      -- insert this step into the new table
      INSERT INTO new_recipes VALUES (rid, step, this_step);
      -- remove this step from the recipe
      SET recipe = SUBSTRING_INDEX(recipe, CONCAT(step, '. ', this_step), -1);
      SET step = next_step;
      SET next_step = step + 1;
    END WHILE;
  END LOOP;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `split_recipes` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
CREATE DEFINER=`ciaran1`@`localhost` PROCEDURE `split_recipes`()
BEGIN
  DECLARE rid INT;
  DECLARE recipe VARCHAR(2048);
  DECLARE step INT;
  DECLARE next_step INT;
  DECLARE this_step VARCHAR(256);
  DECLARE finished INT DEFAULT 0;
  DECLARE recipe_cursor CURSOR FOR SELECT RecipeID, Instructions FROM recipes;
  DECLARE CONTINUE HANDLER FOR NOT FOUND SET finished = 1;
  DROP TABLE IF EXISTS new_recipes;
  CREATE TABLE new_recipes (RecipeID INT, step_num INT, Instruction VARCHAR(256));
  OPEN recipe_cursor;
  recipe_loop: LOOP
    FETCH recipe_cursor INTO rid, recipe;
    IF finished = 1 THEN
      LEAVE recipe_loop;
    END IF;
    SET step = 1;
    SET next_step = 2;
    WHILE recipe RLIKE CONCAT('^[[:blank:]]*', step, '[[.period.]]') DO
      -- is there a next step?
      IF recipe RLIKE CONCAT('^[[:blank:]]*', step, '[[.period.]] .*', next_step, '[[.period.]]') THEN
        SET this_step = SUBSTRING_INDEX(SUBSTRING_INDEX(recipe, CONCAT(next_step, '. '), 1), CONCAT(step, '. '), -1);
      ELSE
        SET this_step = SUBSTRING_INDEX(recipe, CONCAT(step, '. '), -1);
      END IF;
      -- insert this step into the new table
      INSERT INTO new_recipes VALUES (rid, step, this_step);
      -- remove this step from the recipe
      SET recipe = SUBSTRING_INDEX(recipe, CONCAT(step, '. ', this_step), -1);
      SET step = next_step;
      SET next_step = step + 1;
    END WHILE;
  END LOOP;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-01-26 23:20:00
