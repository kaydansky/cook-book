<?php

namespace Cookbook\Domain\Ingredient;

use Cookbook\{DB\Database, Picture\Picture, Pagination\Pagination};
use Delight\Auth\Role;

/**
 * Description of IngredientModel
 *
 * @author AlexK
 */
class IngredientModel
{
    
    protected $db;
    protected $gdb;
    protected $auth;
    protected $imgPath = PATH_IMAGES . 'ingredient/';

    private $picture;
    private $patternWhereMy;

    public function __construct(Picture $picture)
    {
        $this->db = Database::dsn();
        $this->gdb = Database::genericDsn();
        $this->picture = $picture;
    }
    
    public function inject($auth)
    {
        $this->auth = $auth;
        $this->patternWhereMy = ' WHERE (approved > 0 OR user_id = ' . $this->auth->getUserId() . ')';
    }
    
    public function getIngredientList()
    {
        $page = new Pagination();
        $page->db = $this->db;
        $page->queryTotal = 'SELECT COUNT(ingredient_id) AS total FROM ' . DATABASE_DEFAULT . '.ingredients' . $this->patternWhereMy;
        $page->queryPage = 'SELECT ingredient_id, ingredient, description, alt_search_terms, image_filename FROM ' . DATABASE_DEFAULT . '.ingredients' . $this->patternWhereMy . ' ORDER BY ingredient';

        return [
            'data' => $page->getData(),
            'paginator' => $page->getPaginator(),
            'totalRecords' => $page->totalRecords,
            'totalPages' => $page->totalPages,
            'currentPage' => $page->currentPage
        ];
    }
    
    public function getAutoComplete($term, $whole_word = null)
    {
        $whole_word;

        $this->gdb->exec('SET NAMES \'utf8\'');

        return $this->gdb->select('SELECT ingredient_id, ingredient FROM ingredients WHERE ingredient LIKE ? AND (approved > 0 OR user_id = ' . $this->auth->getUserId() . ') ORDER BY ingredient', ["%$term%"]);
    }

    public function getRecipeIngredients($id)
    {
        $this->db->exec('SET NAMES \'utf8\'');

        return $this->db->select('SELECT t1.*, t2.ingredient, t2.description AS IngDesc, t3.recipe_title, t3.description AS RecDesc, t4.unit, t4.mimecode, t4.type, t4.measurement_system, t4.conversion, t4.conversion_unit FROM recipe_ingredients t1 LEFT JOIN ' . DATABASE_DEFAULT . '.ingredients t2 USING(ingredient_id) LEFT JOIN recipes t3 ON t3.recipe_id = t1.ingredient_recipe_id LEFT JOIN units t4 ON t1.unit_id = t4.unit_id WHERE t1.recipe_id = ?', [$id]);
    }

    public function getDishIngredients($id)
    {
        $this->db->exec('SET NAMES \'utf8\'');

        return $this->db->select('SELECT t1.*, t2.ingredient, t2.description AS IngDesc, t3.unit, t3.mimecode, t3.type, t3.measurement_system, t3.conversion, t3.conversion_unit FROM dish_ingredients t1 LEFT JOIN ' . DATABASE_DEFAULT . '.ingredients t2 USING(ingredient_id) LEFT JOIN units t3 ON t1.unit_id = t3.unit_id WHERE t1.dish_id = ?', [$id]);
    }
    
    public function createIngredient(
            $new_ingredient,
            $new_dietary_restriction_id,
            $new_alt_search_terms = null, 
            $new_description = null)
    {
        $imgId = null;

        if (isset($_FILES['new_ingredient_image'])) {
            if (is_uploaded_file($_FILES['new_ingredient_image']['tmp_name'])) {
                $this->picture->path = $this->imgPath;
                $this->picture->file = $_FILES['new_ingredient_image']['tmp_name'];
                $imgId = $this->picture->uploadImage();
            }
        }

        $this->gdb->exec('SET NAMES \'utf8\'');
        $this->gdb->insert(
                'ingredients',
                [
                    'ingredient' => $new_ingredient,
                    'user_id' => $this->auth->getUserId(),
                    'alt_search_terms' => $new_alt_search_terms,
                    'description' => $new_description,
                    'image_filename' => $imgId,
                    'approved' => $this->auth->hasRole(Role::SUPER_ADMIN) ? 1 : 0,
                    'approved_by' => $this->auth->hasRole(Role::SUPER_ADMIN) ? $this->auth->getUserId() : 0
                ]
        );
        
        $newId = $this->gdb->getLastInsertId();

        if ($newId) {
            $this->insertDietaryRestrictions($newId, $new_dietary_restriction_id);
        }
        
        return $newId;
    }
    
    public function getIngredientGrid()
    {   
        $this->db->exec('SET NAMES \'utf8\'');
        
        return $this->db->select('SELECT ingredient_id, ingredient, description, alt_search_terms, image_filename, approved, date_added FROM ' . DATABASE_DEFAULT . '.ingredients ORDER BY date_added DESC');
    }
    
    public function getIngredient($id)
    {
        $this->db->exec('SET NAMES \'utf8\'');
        
        return $this->db->selectRow('SELECT * FROM ' . DATABASE_DEFAULT . '.ingredients WHERE ingredient_id = ?', [$id]);
    }
    
    public function updateIngredient(
            $update_ingredient_id,
            $update_dietary_restriction_id = null,
            $update_ingredient = null, 
            $update_alt_search_terms = null, 
            $update_description = null, 
            $remove_image = null,
            $approved = 0)
    {
        $ingredient = $this->getIngredient($update_ingredient_id);
        $imgId = $ingredient['image_filename'];
        
        if (isset($_FILES['update_ingredient_image'])) {
            if (is_uploaded_file($_FILES['update_ingredient_image']['tmp_name'])) {
                $this->picture->path = $this->imgPath;
                $this->picture->file = $_FILES['update_ingredient_image']['tmp_name'];
                $imgId = $this->picture->uploadImage();
                $this->picture->file = $ingredient['image_filename'];
                $this->picture->deleteImages();
            }
        }
        
        if ($remove_image) {
            $this->picture->path = $this->imgPath;
            $this->picture->file = $ingredient['image_filename'];
            $this->picture->deleteImages();
            $imgId = null;
        }

        $this->gdb->exec('SET NAMES \'utf8\'');
        $this->gdb->update(
                'ingredients',
                [
                    'ingredient' => $update_ingredient,
                    'alt_search_terms' => $update_alt_search_terms,
                    'description' => $update_description,
                    'image_filename' => $imgId,
                    'approved' => $this->auth->hasRole(Role::SUPER_ADMIN) ? (int)$approved : 0
                ],
                [
                    'ingredient_id' => $update_ingredient_id
                ]
        );

        if ($update_dietary_restriction_id) {
            $this->gdb->delete('ingredient_dietary_restrictions', ['ingredient_id' => $update_ingredient_id]);
            $this->insertDietaryRestrictions($update_ingredient_id, $update_dietary_restriction_id);
        }
    }
    
    public function approveIngredient($id)
    {
        if (! $this->auth->hasRole(Role::SUPER_ADMIN)) {
            return false;
        }

        $this->gdb->update(
                'ingredients',
                [
                    'approved' => 1,
                    'approved_by' => $this->auth->getUserId()
                ],
                [
                    'ingredient_id' => $id
                ]
        );
    }

    public function disapproveIngredient($id)
    {
        if (! $this->auth->hasRole(Role::SUPER_ADMIN)) {
            return false;
        }

        $this->gdb->update('ingredients', ['approved' => 0], ['ingredient_id' => $id]);
    }
    
    public function deleteIngredient($id)
    {
        $ingredient = $this->getIngredient($id);
        $this->picture->path = $this->imgPath;
        $this->picture->file = $ingredient['image_filename'];
        $this->picture->deleteImages();
        $this->gdb->delete('ingredients', ['ingredient_id' => $id]);
    }

    private function insertDietaryRestrictions(int $ingredientId, array $restrictions)
    {
        if (! count($restrictions)) {
            return false;
        }

        foreach (array_filter($restrictions) as $id) {
            $this->gdb->insert(
                'ingredient_dietary_restrictions',
                [
                    'ingredient_id' => $ingredientId,
                    'dietary_restriction_id' => $id
                ]
            );
        }
    }
    
}
