<?php

namespace Cookbook\Domain\Categories;

use Cookbook\DB\Database;

/**
 * Description of IngredientModel
 *
 * @author AlexK
 */
class CategoriesModel
{
    
    protected $db;
    protected $auth;

    public function __construct()
    {
        $this->db = Database::dsn();
    }
    
    public function inject($auth)
    {
        $this->auth = $auth;
    }
    
    public function createCategory($new_category_name, $new_description = null)
    {
        $this->db->exec('SET NAMES \'utf8\'');
        $this->db->insert(
                'category', 
                [
                    'category_name' => $new_category_name,
                    'description' => $new_description
                ]
        );
        
        return $this->db->getLastInsertId();
    }
    
    public function getCategoryList()
    {   
        $this->db->exec('SET NAMES \'utf8\'');
        
        return $this->db->select('SELECT * FROM category ORDER BY category_name');
    }
    
    public function getAutoComplete($term)
    {
        $this->db->exec('SET NAMES \'utf8\'');

        return $this->db->select('SELECT category_id, category_name FROM category WHERE category_name LIKE ? ORDER BY category_name', ["%$term%"]);
    }
    
    public function getCategory($id)
    {
        $this->db->exec('SET NAMES \'utf8\'');
        
        return $this->db->selectRow('SELECT * FROM category WHERE category_id = ?', [$id]);
    }
    
    public function updateCategory(
            $update_category_id, 
            $update_category_name = null, 
            $update_description = null)
    {
        $this->db->exec('SET NAMES \'utf8\'');
        $this->db->update(
                'category',
                [
                    'category_name' => $update_category_name,
                    'description' => $update_description
                ],
                [
                    'category_id' => $update_category_id
                ]
        );
    }
    
    public function deleteCategory($id)
    {
        $this->db->delete('category', ['category_id' => $id]);
    }

    public function getRecipeCategories($recipeId)
    {
        $this->db->exec('SET NAMES \'utf8\'');

        return $this->db->select('SELECT * FROM category t1 JOIN recipe_categories t2 USING(category_id) WHERE t2.recipe_id = ?', [$recipeId]);
    }

    public function getDishCategories($id)
    {
        $this->db->exec('SET NAMES \'utf8\'');

        return $this->db->select('SELECT * FROM category t1 JOIN dish_categories t2 USING(category_id) WHERE t2.dish_id = ?', [$id]);
    }

    public function getIngredientCategories($id)
    {
        $this->db->exec('SET NAMES \'utf8\'');

        return $this->db->select('SELECT * FROM category t1 JOIN ingredient_categories t2 USING(category_id) WHERE t2.ingredient_id = ?', [$id]);
    }
    
}
