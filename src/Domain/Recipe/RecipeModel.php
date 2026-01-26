<?php

namespace Cookbook\Domain\Recipe;

use Cookbook\{DB\Database, Helpers\Sanitizer, Picture\Picture, Pagination\Pagination};
use Delight\Auth\Role;

/**
 * Description of Model
 *
 * @author AlexK
 */
class RecipeModel
{
    private $db;
    private $gdb;
    private $imgPath = PATH_IMAGES . 'recipe/';
    private $prepare_hours;
    private $prepare_min;
    private $cook_hours;
    private $cook_min;
    private $source;
    private $source_link;
    private $alt_search_terms;
    private $notes;
    private $yield_type;
    private $yield_value;
    private $yield_unit;
    private $ingredient_unit;
    private $ingredient;
    private $quantity;
    private $comment;
    private $ingredient_array;
    private $step_content;
    private $equipment_id;
    private $eq_quantity;
    private $eq_comment;
    private $approved;
    private $remove_image;
    private $image_filenames;
    private $step_image_filenames;
    private $images_step_filenames;
    private $step_images_current;
    private $picture;
    private $auth;
    private $my_items;
    private $patternWhereMy;
    private $patternAndMy;
    private $patternSource;
    private $patternRange;
    private $patternAuthor;
    private $patternSort = 't1.date_added DESC';
    private $patternCategoryJoin;
    private $patternCategoryWhere;
    private $patternQuery;
    private $patternIsIngredientJoin;
    private $patternIsIngredientWhere;
    private $stepIngredients;

    public function __construct(Picture $picture)
    {
        $this->db = Database::dsn();
        $this->gdb = Database::genericDsn();
        $this->picture = $picture;
        $this->my_items = filter_input(INPUT_GET, 'my_items', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    }

    public function inject($auth)
    {
        $this->auth = $auth;

        if ($this->my_items === 'on') {
            $this->patternWhereMy = ' WHERE user_id = ' . $this->auth->getUserId();
            $this->patternAndMy = ' AND user_id = ' . $this->auth->getUserId();
        } else {
            // $this->patternWhereMy = ' WHERE t1.approved = 1';
            // $this->patternAndMy = ' AND t1.approved = 1';
        }
    }

    public function __set($name, $value)
    {
        $this->$name = $value;
    }

    public function getRecipeList()
    {
        $page = new Pagination();
        $page->db = $this->db;
        $page->queryTotal = 'SELECT COUNT(DISTINCT recipe_id) AS total FROM recipes t1'
            . $this->patternCategoryJoin
            . $this->patternIsIngredientJoin
            . $this->patternWhereMy
            . $this->patternSource
            . $this->patternAuthor
            . $this->patternRange
            . $this->patternQuery
            . $this->patternCategoryWhere
            . $this->patternIsIngredientWhere;

        $page->queryPage = '
SELECT DISTINCT t1.recipe_id, t1.recipe_title, t1.description, t1.source, t1.source_link, t1.image_filenames, t1.date_added, t1.date_modified, t2.first_name, t2.last_name 
FROM recipes t1 
LEFT JOIN ' . DATABASE_DEFAULT .
            '.accounts t2 USING (user_id)'
            . $this->patternCategoryJoin
            . $this->patternIsIngredientJoin
            . $this->patternWhereMy
            . $this->patternSource
            . $this->patternAuthor
            . $this->patternRange
            . $this->patternQuery
            . $this->patternCategoryWhere
            . $this->patternIsIngredientWhere
            . ' ORDER BY '
            . $this->patternSort;

        return [
            'data' => $page->getData(),
            'paginator' => $page->getPaginator(),
            'totalRecords' => $page->totalRecords,
            'totalPages' => $page->totalPages,
            'currentPage' => $page->currentPage
        ];
    }

    public function getRecipeListSearch($q, $whole_word)
    {
        if ($whole_word !== 'on') {
            $pattern = 'LIKE ?';
            $queryData = ["%$q%", "%$q%"];
        } else {
            $pattern = 'REGEXP \'[[:<:]]' . $q . '[[:>:]]\'';
            $queryData = null;
        }

        $patternUser = $this->userPattern($q, $whole_word);

        $page = new Pagination();
        $page->db = $this->db;
        $page->queryTotal = 'SELECT COUNT(*) AS total FROM recipes t1 WHERE (recipe_title ' . $pattern . ' OR description ' . $pattern . $patternUser . ')' . $this->patternAndMy;
        $page->queryPage = '
SELECT recipe_id, recipe_title, description, image_filenames, date_added, date_modified 
FROM recipes t1 
WHERE (recipe_title ' . $pattern . ' OR description ' . $pattern . $patternUser . ')'
            . $this->patternAndMy
            . ' ORDER BY recipe_title';

        $page->queryData = $queryData;

        return [
            'data' => $page->getData(),
            'paginator' => $page->getPaginator(),
            'totalRecords' => $page->totalRecords,
            'totalPages' => $page->totalPages,
            'currentPage' => $page->currentPage
        ];
    }

    public function getRecipeListIsIngredient($q_ingredient, $is_ingredient, $whole_word) {
        if ($whole_word !== 'on') {
            $pattern = $is_ingredient === 'no' ? 'NOT LIKE ?' : 'LIKE ?';
            $queryData = ["%$q_ingredient%"];
        } else {
            $pattern = $is_ingredient === 'no' ? 'NOT REGEXP \'[[:<:]]' . $q_ingredient . '[[:>:]]\'' : 'REGEXP \'[[:<:]]' . $q_ingredient . '[[:>:]]\'';
            $queryData = null;
        }

        $page = new Pagination();
        $page->db = $this->db;
        $page->queryTotal = 'SELECT COUNT(DISTINCT t1.recipe_id) AS total FROM recipes t1 JOIN recipe_ingredients t2 USING(recipe_id) JOIN ' . DATABASE_DEFAULT . '.ingredients t3 ON t2.ingredient_id = t3.ingredient_id WHERE t3.ingredient ' . $pattern . $this->patternAndMy;
        $page->queryPage = '
SELECT DISTINCT t1.recipe_id, t1.recipe_title, t1.description, t1.image_filenames, t1.date_added, t1.date_modified 
FROM recipes t1 
JOIN recipe_ingredients t2 USING(recipe_id) 
JOIN ' . DATABASE_DEFAULT . '.ingredients t3 ON t2.ingredient_id = t3.ingredient_id 
WHERE t3.ingredient ' . $pattern . $this->patternAndMy .' 
ORDER BY t1.recipe_title';

        $page->queryData = $queryData;

        return [
            'data' => $page->getData(),
            'paginator' => $page->getPaginator(),
            'totalRecords' => $page->totalRecords,
            'totalPages' => $page->totalPages,
            'currentPage' => $page->currentPage
        ];
    }
    
    public function getRecipeListCategory($id)
    {
        $page = new Pagination();
        $page->db = $this->db;
        $page->queryTotal = 'SELECT DISTINCT COUNT(DISTINCT recipe_id) AS total FROM recipes t1 JOIN recipe_categories t2 USING(recipe_id) WHERE t2.category_id = ?' . $this->patternAndMy;
        $page->queryPage = 'SELECT DISTINCT recipe_id, recipe_title, description, image_filenames, date_added, date_modified FROM recipes t1 JOIN recipe_categories t2 USING(recipe_id) WHERE t2.category_id = ?' . $this->patternAndMy . ' ORDER BY t1.recipe_title';
        $page->queryData = [$id];

        return [
            'data' => $page->getData(),
            'paginator' => $page->getPaginator(),
            'totalRecords' => $page->totalRecords,
            'totalPages' => $page->totalPages,
            'currentPage' => $page->currentPage
        ];
    }

    public function getRecipeListDaysRange($days) {
        $page = new Pagination();
        $page->db = $this->db;
        $page->queryTotal = 'SELECT COUNT(recipe_id) AS total FROM recipes t1 WHERE date_added >= DATE_ADD(CURDATE(), INTERVAL -? DAY)' . $this->patternAndMy;
        $page->queryPage = 'SELECT recipe_id, recipe_title, description, image_filenames, date_added, date_modified FROM recipes t1 WHERE date_added >= DATE_ADD(CURDATE(), INTERVAL -? DAY)' . $this->patternAndMy . ' ORDER BY recipe_title';
        $page->queryData = [$days];

        return [
            'data' => $page->getData(),
            'paginator' => $page->getPaginator(),
            'totalRecords' => $page->totalRecords,
            'totalPages' => $page->totalPages,
            'currentPage' => $page->currentPage
        ];
    }

    public function getAutoComplete($term, $whole_word = false)
    {
        if ($whole_word !== 'on') {
            $pattern = 'LIKE ?';
            $queryData = ["%$term%", "%$term%", "%$term%"];
        } else {
            $pattern = 'REGEXP \'[[:<:]]' . $term . '[[:>:]]\'';
            $queryData = null;
        }

        $patternUser = $this->userPattern($term, $whole_word);

        $this->db->exec('SET NAMES \'utf8\'');

        $recipes = $this->db->select('SELECT recipe_id, recipe_title, description, user_id FROM recipes t1 WHERE (recipe_title ' . $pattern . ' OR description ' . $pattern . ' OR alt_search_terms ' . $pattern . $patternUser . ')' . $this->patternAndMy . ' ORDER BY recipe_title', $queryData);

        if ($recipes) {
            foreach ($recipes as $key => $value) {
                $account = $this->gdb->selectRow('SELECT CONCAT(first_name, \' \', last_name) as author_name FROM accounts WHERE user_id = ?', [$value['user_id']]);
                $recipes[$key]['author_name'] = $account ? $account['author_name'] : 'undefined';
            }
        }

        return $recipes;
    }

    public function getAutoCompleteIsIngredient($term, $is_ingredient, $whole_word) {
        if ($is_ingredient === 'no') {
            return [['recipe_id' => null, 'recipe_title' => 'Autocomplete disabled for excluded search. Write in your query and press Enter.']];
        }

        $this->db->exec('SET NAMES \'utf8\'');

        if ($whole_word !== 'on') {
            $pattern = $is_ingredient === 'no' ? 'NOT LIKE ?' : 'LIKE ?';
            return $this->db->select('SELECT DISTINCT t1.recipe_id, t1.recipe_title, t1.description, t1.image_filenames, t1.date_added, t1.date_modified FROM recipes t1 JOIN recipe_ingredients t2 USING(recipe_id) JOIN ' . DATABASE_DEFAULT . '.ingredients t3 ON t2.ingredient_id = t3.ingredient_id WHERE t3.ingredient ' . $pattern . $this->patternAndMy . ' ORDER BY t1.recipe_title', ["%$term%"]);
        } else {
            $pattern = $is_ingredient === 'no' ? 'NOT REGEXP \'[[:<:]]' . $term . '[[:>:]]\'' : 'REGEXP \'[[:<:]]' . $term . '[[:>:]]\'';
            return $this->db->select('SELECT DISTINCT t1.recipe_id, t1.recipe_title, t1.description, t1.image_filenames, t1.date_added, t1.date_modified FROM recipes t1 JOIN recipe_ingredients t2 USING(recipe_id) JOIN ' . DATABASE_DEFAULT . '.ingredients t3 ON t2.ingredient_id = t3.ingredient_id WHERE t3.ingredient ' . $pattern . $this->patternAndMy . ' ORDER BY t1.recipe_title');
        }
    }

    public function getAutoCompleteIngredients($term)
    {
        $this->gdb->exec('SET NAMES \'utf8\'');

        return $this->gdb->select('SELECT ingredient_id, ingredient FROM ingredients WHERE (approved > 0 OR user_id = ' . $this->auth->getUserId() . ') AND (ingredient LIKE ? OR description LIKE ? OR alt_search_terms LIKE ?) ORDER BY ingredient', ["%$term%", "%$term%", "%$term%"]);
    }

    public function getAutoCompleteSource($term)
    {
        $this->db->exec('SET NAMES \'utf8\'');

        return $this->db->select('SELECT DISTINCT source AS `value` FROM recipes WHERE source LIKE ? ORDER BY source', ["%$term%"]);
    }

    public function getAutoCompleteUser($term)
    {
        $this->gdb->exec('SET NAMES \'utf8\'');

        return $this->gdb->select('SELECT user_id AS `id`, CONCAT(first_name, " ", last_name) AS `value` FROM accounts WHERE CONCAT(first_name, " ", last_name) LIKE ? ORDER BY first_name', ["%$term%"]);
    }

    public function getRecipe($id)
    {
        $this->db->exec('SET NAMES \'utf8\'');

        return $this->db->selectRow('SELECT * FROM recipes WHERE recipe_id = ? LIMIT 0, 1', [$id]);
    }
    
    public function getRecipeIngredients($id)
    {
        $this->db->exec('SET NAMES \'utf8\'');

        return $this->db->select('SELECT t1.*, t2.ingredient, t2.description AS IngDesc, t3.recipe_title, t3.description AS RecDesc, t4.unit, t4.mimecode, t4.type, t4.measurement_system, t4.conversion, t4.conversion_unit FROM recipe_ingredients t1 LEFT JOIN ' . DATABASE_DEFAULT . '.ingredients t2 USING(ingredient_id) LEFT JOIN recipes t3 ON t3.recipe_id = t1.ingredient_recipe_id LEFT JOIN units t4 ON t1.unit_id = t4.unit_id WHERE t1.recipe_id = ?', [$id]);
    }
    
    public function getRecipeSteps($id)
    {
        return $this->db->select('SELECT * FROM recipe_steps WHERE recipe_id = ? ORDER BY recipe_step_id', [$id]);
    }

    public function getRecipeYields($id)
    {
        return $this->db->select('SELECT t1.*, t2.unit, t2.unit_name, t2.unit_name_plural, t2.mimecode, t2.measurement_system, t2.conversion, t2.conversion_unit, t2.type AS unitType FROM recipe_yields t1 LEFT JOIN units t2 ON t1.unit_id = t2.unit_id WHERE t1.recipe_id = ?', [$id]);
    }
    
    public function getRecipeEquipment($id)
    {
        $this->db->exec('SET NAMES \'utf8\'');

        return $this->db->select('SELECT * FROM recipe_equipment t1 JOIN equipment t2 USING(equipment_id) WHERE recipe_id = ?', [$id]);
    }

    public function getRecipeImages($recipeId)
    {
        $q = $this->db->selectRow('SELECT step_image_filenames FROM recipes WHERE recipe_id = ? LIMIT 1', [$recipeId]);

        return $q ? $q['step_image_filenames'] : null;
    }

    public function getStepImages($stepId) {
        $q = $this->db->selectRow('SELECT step_images FROM recipe_steps WHERE recipe_step_id = ? LIMIT 1', [$stepId]);

        return $q ? $q['step_images'] : null;
    }

    public function getStepsContent($recipeId)
    {
        $a = [];
        $s = [];
        $c = 0;
        $this->db->exec('SET NAMES \'utf8\'');
        $q = $this->db->select('SELECT step_content, step_images FROM recipe_steps WHERE recipe_id = ? ORDER BY recipe_step_id', [$recipeId]);
        $qt = $this->db->selectRow('SELECT step_image_filenames FROM recipes teps WHERE recipe_id = ?', [$recipeId]);

        if (! $qt) {
            return $a;
        }

        $allImages = explode(',', $qt['step_image_filenames']);

        if ($q) {
            foreach ($q as $value) {
                ++$c;

                if ($value['step_images']) {
                    $b = [];

                    foreach (explode(',', $value['step_images']) as $v) {
                        $b[$v] = $value['step_content'];
                        $s[] = $value['step_images'];
                    }

                    $a[$c] = $b;
                }
            }
        }

        foreach ($allImages as $file) {
            ++$c;

            if (! in_array($file, $s)) {
                $a[$c . 'x'] = [$file => '&mdash;'];
            }
        }

        return $a;
    }

    public function getRecipeGrid()
    {
        $this->db->exec('SET NAMES \'utf8\'');

        return $this->db->select('SELECT recipe_id, recipe_title, description, alt_search_terms, approved FROM recipes ORDER BY recipe_title');
    }

    public function createRecipe($recipe_title, $description, $category_id)
    {
        $this->filterPost();
        $image_filenames = [];
        $step_image_filenames = [];
        
        if (isset($_FILES['image_id']['tmp_name'])) {
            foreach ($_FILES['image_id']['tmp_name'] as $file) {
                $image_filenames[] = $this->uploadImage($file);
            }
        }

        if (isset($_FILES['step_image_id']['tmp_name'])) {
            foreach ($_FILES['step_image_id']['tmp_name'] as $file) {
                $step_image_filenames[] = $this->uploadImage($file);
            }
        }

        $this->db->exec('SET NAMES \'utf8\'');
        $this->db->insert(
                'recipes', 
                [
                    'recipe_title' => $recipe_title,
                    'description' => $description,
                    'source' => $this->source,
                    'source_link' => $this->source_link,
                    'prepare_hours' => $this->prepare_hours ?: 0,
                    'prepare_min' => $this->prepare_min ?: 0,
                    'cook_hours' => $this->cook_hours ?: 0,
                    'cook_min' => $this->cook_min ?: 0,
                    'notes' => $this->notes,
                    'alt_search_terms' => $this->alt_search_terms,
                    'user_id' => $this->auth->getUserId(),
                    'approved' => $this->auth->hasRole(Role::CHEF) ? 1 : 0,
                    'approved_by' => $this->auth->hasRole(Role::CHEF) ? $this->auth->getUserId() : 0,
                    'image_filenames' => count($image_filenames) ? implode(',', $image_filenames) : null,
                    'step_image_filenames' => count($step_image_filenames) ? implode(',', $step_image_filenames) : null
                ]
        );
        
        $newId = $this->db->getLastInsertId();
        
        if (! $newId) {
            return false;
        }
        
        $this->insertCategories($category_id, $newId);
        $this->insertIngredients($newId);
        $this->insertSteps($newId);
        $this->insertEquipment($newId);
        $this->insertYield($newId);

        return $newId;
    }
    
    public function updateRecipe($id, $recipe_title, $description, $category_id = null)
    {
        $this->filterPost();
        $this->db->exec('SET NAMES \'utf8\'');
        
//        $this->db->delete('recipe_categories', ['recipe_id' => $id]);
        $this->insertCategories($category_id, $id);
//        $this->db->delete('recipe_ingredients', ['recipe_id' => $id]);
        $this->insertIngredients($id);
//        $this->db->delete('recipe_steps', ['recipe_id' => $id]);
        $this->insertSteps($id);
//        $this->db->delete('recipe_equipment', ['recipe_id' => $id]);
        $this->insertEquipment($id);
//        $this->db->delete('recipe_yields', ['recipe_id' => $id]);
        $this->insertYield($id);

        $this->removeImages();

        $dataRecipes = [
            'recipe_title' => $recipe_title,
            'description' => $description,
            'source' => $this->source,
            'source_link' => $this->source_link,
            'prepare_hours' => $this->prepare_hours ?: 0,
            'prepare_min' => $this->prepare_min ?: 0,
            'cook_hours' => $this->cook_hours ?: 0,
            'cook_min' => $this->cook_min ?: 0,
            'notes' => $this->notes,
            'alt_search_terms' => $this->alt_search_terms
        ];
        
        if ($this->auth->hasRole(Role::CHEF)) {
            if ($this->approved == 'on') {
                $dataRecipes['approved'] = 1;
                $dataRecipes['approved_by'] = $this->auth->getUserId();
            } else {
                $dataRecipes['approved'] = 0;
            }
        }

        $dataRecipes['image_filenames'] = $this->fetchImages((isset($_FILES['image_id']) ? $_FILES['image_id'] : null), $this->image_filenames);
        $dataRecipes['step_image_filenames'] = $this->fetchImages((isset($_FILES['step_image_id']) ? $_FILES['step_image_id'] : null), $this->step_image_filenames);

        $this->db->update('recipes', ['image_filenames' => null, 'step_image_filenames' => null], ['recipe_id' => $id]);
        $this->db->update('recipes', $dataRecipes, ['recipe_id' => $id]);
    }

    private function fetchImages($uploadedFiles, $imageFilenames)
    {
        $image_filenames = null;

        if (isset($uploadedFiles['tmp_name'])) {
            foreach ($uploadedFiles['tmp_name'] as $file) {
                $imgFileName = $this->uploadImage($file);

                if ($imgFileName) {
                    $image_filenames[] = $imgFileName;
                }
            }
        }

        $aImg = [];

        if (is_array($imageFilenames)) {
            $aImg = $imageFilenames;
        }

        if ($image_filenames) {
            $aImg = array_merge($aImg, $image_filenames);
        }

        if ($aImg) {
            return count($aImg) > 1 ? implode(',', $aImg) : $aImg[0];
        }

        return null;
    }

    public function deleteRecipe($id)
    {
        $recipe = $this->getRecipe($id);
        $this->picture->path = $this->imgPath;
        $this->picture->file = $recipe['image_filenames'];
        $this->picture->deleteImages();
        $this->picture->file = $recipe['step_image_filenames'];
        $this->picture->deleteImages();
        $this->db->delete('recipes', ['recipe_id' => $id]);
        $this->db->delete('recipe_categories', ['recipe_id' => $id]);
        $this->db->delete('recipe_equipment', ['recipe_id' => $id]);
        $this->db->delete('recipe_ingredients', ['recipe_id' => $id]);
        $this->db->delete('recipe_steps', ['recipe_id' => $id]);
        $this->db->delete('recipe_yields', ['recipe_id' => $id]);
    }

    public function approveRecipe($id)
    {
        $this->db->update('recipes', ['approved' => 1, 'approved_by' => $this->auth->getUserId()], ['recipe_id' => $id]);
    }

    public function disapproveRecipe($id)
    {
        $this->db->update('recipes', ['approved' => 0], ['recipe_id' => $id]);
    }

    public function cloneRecipe($id)
    {
        $this->db->exec(<<<'SQL'
INSERT INTO recipes (
recipe_title, 
description,
source,
source_link,
prepare_hours,
prepare_min,
cook_hours,
cook_min,
yield,
yield_value,
unit_id,
notes,
user_id,
alt_search_terms,
approved,
approved_by,
image_filenames,
step_image_filenames
) 
SELECT 
recipe_title, 
description,
source,
source_link,
prepare_hours,
prepare_min,
cook_hours,
cook_min,
yield,
yield_value,
unit_id,
notes,
user_id,
alt_search_terms,
approved,
approved_by,
image_filenames,
step_image_filenames
FROM recipes WHERE recipe_id = ?
SQL
, [$id]);

        $newId = $this->db->getLastInsertId();

        $categories = $this->db->select('SELECT category_id FROM recipe_categories WHERE recipe_id = ?', [$id]);

        if ($categories) {
            foreach ($categories as $value) {
                $this->db->insert('recipe_categories', ['recipe_id' => $newId, 'category_id' => $value['category_id']]);
            }
        }

        $equipment = $this->db->select('SELECT equipment_id, quantity, comment FROM recipe_equipment WHERE recipe_id = ?', [$id]);

        if ($equipment) {
            foreach ($equipment as $value) {
                $this->db->insert('recipe_equipment',
                    [
                        'recipe_id' => $newId,
                        'equipment_id' => $value['equipment_id'],
                        'quantity' => $value['quantity'],
                        'comment' => $value['comment']
                    ]);
            }
        }

        $ingredients = $this->db->select('SELECT uuid, ingredient_id, ingredient_recipe_id, quantity, unit_id, comment FROM recipe_ingredients WHERE recipe_id = ?', [$id]);

        if ($ingredients) {
            foreach ($ingredients as $value) {
                $this->db->insert('recipe_ingredients',
                    [
                        'recipe_id' => $newId,
                        'uuid' => $value['uuid'],
                        'ingredient_id' => $value['ingredient_id'],
                        'ingredient_recipe_id' => $value['ingredient_recipe_id'],
                        'quantity' => $value['quantity'],
                        'unit_id' => $value['unit_id'],
                        'comment' => $value['comment']
                    ]);
            }
        }

        $steps = $this->db->select('SELECT step_content, step_images, ingredient_array FROM recipe_steps WHERE recipe_id = ?', [$id]);

        if ($steps) {
            foreach ($steps as $value) {
                $this->db->insert('recipe_steps',
                    [
                        'recipe_id' => $newId,
                        'step_content' => $value['step_content'],
                        'step_images' => $value['step_images'],
                        'ingredient_array' => $value['ingredient_array']
                    ]);
            }
        }

        $yields = $this->db->select('SELECT unit_id, quantity FROM recipe_yields WHERE recipe_id = ?', [$id]);

        if ($yields) {
            foreach ($yields as $value) {
                $this->db->insert('recipe_yields',
                    [
                        'recipe_id' => $newId,
                        'unit_id' => $value['unit_id'],
                        'quantity' => $value['quantity']
                    ]);
            }
        }

        return $newId;
    }
    
    private function insertCategories($categoryIds, $recipeId)
    {
        if ($categoryIds && count($categoryIds)) {
            $this->db->delete('recipe_categories', ['recipe_id' => $recipeId]);

            foreach (array_filter($categoryIds) as $catId) {
                $this->db->insert(
                    'recipe_categories',
                    [
                        'recipe_id' => $recipeId,
                        'category_id' => $catId
                    ]
                );
            }
        }
    }
    
    private function insertIngredients($recipeId)
    {
        if ($this->ingredient && count($this->ingredient)) {
            $this->db->delete('recipe_ingredients', ['recipe_id' => $recipeId]);

            foreach ($this->ingredient as $key => $value) {
                $ingredient_id = 0;
                $ingredient_recipe_id = 0;
                $a = explode('|', $value);

                if (strpos($a[0], 'r') !== false) {
                    $ingredient_recipe_id = trim($a[0], 'r');
                } else {
                    $ingredient_id = $a[0];
                }
                
                if ($ingredient_id === '') {
                    continue;
                }

                $this->db->insert(
                        'recipe_ingredients', 
                        [
                            'recipe_id' => $recipeId,
                            'uuid' => $a[1],
                            'ingredient_id' => $ingredient_id,
                            'ingredient_recipe_id' => $ingredient_recipe_id,
                            'quantity' => $this->quantity[$key] ?? null,
                            'unit_id' => $this->ingredient_unit[$key] ?? 0,
                            'comment' => $this->comment[$key] ?? null
                        ]
                );
            }
        }
    }
    
    private function insertSteps($recipeId)
    {
        if ($this->step_content && count($this->step_content)) {
            $this->db->delete('recipe_steps', ['recipe_id' => $recipeId]);

            foreach ($this->step_content as $key => $value) {
                $image_filenames = null;

                if (isset($this->images_step_filenames[$key])) {
                    $image_filenames = $this->images_step_filenames[$key];
                } elseif (isset($this->step_images_current[$key])) {
                    $image_filenames = $this->step_images_current[$key];
                }

                $this->db->insert(
                        'recipe_steps', 
                        [
                            'recipe_id' => $recipeId,
                            'step_content' => $value,
                            'step_images' => $image_filenames,
                            'ingredient_array' => $this->ingredient_array[$key] ?? null
                        ]
                );
            }
        }
    }
    
    private function insertEquipment($recipeId)
    {
        if ($this->equipment_id && count($this->equipment_id)) {
            $this->db->delete('recipe_equipment', ['recipe_id' => $recipeId]);

            foreach ($this->equipment_id as $key => $value) {
                
                if ($value === '') {
                    continue;
                }
                
                $this->db->insert(
                        'recipe_equipment', 
                        [
                            'recipe_id' => $recipeId,
                            'equipment_id' => $value,
                            'quantity' => $this->eq_quantity[$key] ?? 0,
                            'comment' => $this->eq_comment[$key] ?? null
                        ]
                );
            }
        }
    }

    private function insertYield($recipeId)
    {
        if ($this->yield_type && count($this->yield_type)) {
            $this->db->delete('recipe_yields', ['recipe_id' => $recipeId]);

            foreach ($this->yield_type as $key => $value) {

                if ($value === '') {
                    continue;
                }

                $this->db->insert(
                    'recipe_yields',
                    [
                        'recipe_id' => $recipeId,
                        'quantity' => $this->yield_value[$key],
                        'unit_id' => $this->yield_unit[$key]
                    ]
                );
            }
        }
    }
    
    private function removeImages()
    {
        if ($this->remove_image && count($this->remove_image)) {
            foreach ($this->remove_image as $key => $value) {
                if ($value === 'on') {
                    $this->db->exec("UPDATE recipe_steps SET step_images = TRIM(BOTH ',' FROM REPLACE(REPLACE(step_images, '{$key}', ''), ',,', ',')) WHERE step_images IS NOT NULL");
                    $this->picture->path = $this->imgPath;
                    $this->picture->file = $key;
                    $this->picture->deleteImages();
                    $this->image_filenames = str_replace($key, '', $this->image_filenames);
                    $this->step_image_filenames = str_replace($key, '', $this->step_image_filenames);
                }
            }
        }

        if ($this->image_filenames != '') {
            $this->image_filenames = strpos($this->image_filenames, ',') !== false
                    ? array_filter(explode(',', $this->image_filenames))
                    : [$this->image_filenames];
        }

        if ($this->step_image_filenames != '') {
            $this->step_image_filenames = strpos($this->step_image_filenames, ',') !== false
                ? array_filter(explode(',', $this->step_image_filenames))
                : [$this->step_image_filenames];
        }
    }
    
    private function uploadImage($file)
    {
        $image_filename = null;

        if (is_uploaded_file($file)) {
            $this->picture->path = $this->imgPath;
            $this->picture->file = $file;
            $image_filename = $this->picture->uploadImage();
        }
        
        return $image_filename;
    }

    private function userPattern($term, $whole_word) {
        if ($whole_word !== 'on') {
            $ptrn = 'LIKE ?';
            $queryData = ["%$term%"];
        } else {
            $ptrn = 'REGEXP \'[[:<:]]' . $term . '[[:>:]]\'';
            $queryData = null;
        }

        $userIds = $this->gdb->select('SELECT user_id FROM accounts WHERE CONCAT(first_name, \' \', last_name) ' . $ptrn, $queryData);
        $pattern = '';

        if ($userIds) {
            foreach ($userIds as $value) {
                $pattern .= ' OR user_id=' . $value['user_id'];
            }
        }

        return $pattern;
    }
    
    private function filterPost()
    {
        $this->source = Sanitizer::sanitize(filter_input(INPUT_POST, 'source', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        $this->source_link = Sanitizer::sanitize(filter_input(INPUT_POST, 'source_link', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        $this->alt_search_terms = Sanitizer::sanitize(filter_input(INPUT_POST, 'alt_search_terms', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        $this->notes = Sanitizer::sanitize(filter_input(INPUT_POST, 'notes', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        $this->image_filenames = Sanitizer::sanitize(filter_input(INPUT_POST, 'image_filenames', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        $this->step_image_filenames = Sanitizer::sanitize(filter_input(INPUT_POST, 'step_image_filenames', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        $this->approved = Sanitizer::sanitize(filter_input(INPUT_POST, 'approved', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        $this->prepare_hours = filter_input(INPUT_POST, 'prepare_hours', FILTER_SANITIZE_NUMBER_INT);
        $this->prepare_min = filter_input(INPUT_POST, 'prepare_min', FILTER_SANITIZE_NUMBER_INT);
        $this->cook_hours = filter_input(INPUT_POST, 'cook_hours', FILTER_SANITIZE_NUMBER_INT);
        $this->cook_min = filter_input(INPUT_POST, 'cook_min', FILTER_SANITIZE_NUMBER_INT);

        $yield_type = filter_input(INPUT_POST, 'yield_type', FILTER_SANITIZE_FULL_SPECIAL_CHARS, FILTER_REQUIRE_ARRAY);
        $this->yield_type = $yield_type ? array_filter($yield_type) : null;
        $yield_value = filter_input(INPUT_POST, 'yield_value', FILTER_SANITIZE_FULL_SPECIAL_CHARS, FILTER_REQUIRE_ARRAY);
        $this->yield_value = $yield_value ? array_filter($yield_value) : null;
        $yield_unit = filter_input(INPUT_POST, 'yield_unit', FILTER_SANITIZE_NUMBER_INT, FILTER_REQUIRE_ARRAY);
        $this->yield_unit = $yield_unit ? array_filter($yield_unit) : null;
        $ingredient_unit = filter_input(INPUT_POST, 'ingredient_unit', FILTER_SANITIZE_NUMBER_INT, FILTER_REQUIRE_ARRAY);
        $this->ingredient_unit = $ingredient_unit ? array_filter($ingredient_unit) : null;
        $ingredient = filter_input(INPUT_POST, 'ingredient', FILTER_SANITIZE_FULL_SPECIAL_CHARS, FILTER_REQUIRE_ARRAY);
        $this->ingredient = $ingredient ? array_filter($ingredient) : null;
        $quantity = filter_input(INPUT_POST, 'quantity', FILTER_SANITIZE_FULL_SPECIAL_CHARS, FILTER_REQUIRE_ARRAY);
        $this->quantity = $quantity ? array_filter($quantity) : null;
        $comment = filter_input(INPUT_POST, 'comment', FILTER_SANITIZE_FULL_SPECIAL_CHARS, FILTER_REQUIRE_ARRAY);
        $this->comment = $comment ? array_filter($comment) : null;
        $ingredient_array = filter_input(INPUT_POST, 'ingredient_array', FILTER_SANITIZE_FULL_SPECIAL_CHARS, FILTER_REQUIRE_ARRAY);
        $this->ingredient_array = $ingredient_array ? array_filter($ingredient_array) : null;
        $step_content = filter_input(INPUT_POST, 'step_content', FILTER_SANITIZE_FULL_SPECIAL_CHARS, FILTER_REQUIRE_ARRAY);
        $this->step_content = $step_content ? array_filter($step_content) : null;
        $images_step_filenames = filter_input(INPUT_POST, 'images_step_filenames', FILTER_SANITIZE_FULL_SPECIAL_CHARS, FILTER_REQUIRE_ARRAY);
        $this->images_step_filenames = $step_content ? array_filter($images_step_filenames) : null;
        $equipment_id = filter_input(INPUT_POST, 'equipment_id', FILTER_SANITIZE_NUMBER_INT, FILTER_REQUIRE_ARRAY);
        $this->equipment_id = $equipment_id ? array_filter($equipment_id) : null;
        $eq_quantity = filter_input(INPUT_POST, 'eq_quantity', FILTER_SANITIZE_NUMBER_INT, FILTER_REQUIRE_ARRAY);
        $this->eq_quantity = $eq_quantity ? array_filter($eq_quantity) : null;
        $eq_comment = filter_input(INPUT_POST, 'eq_comment', FILTER_SANITIZE_FULL_SPECIAL_CHARS, FILTER_REQUIRE_ARRAY);
        $this->eq_comment = $eq_comment ? array_filter($eq_comment) : null;
        $remove_image = filter_input(INPUT_POST, 'remove_image', FILTER_SANITIZE_FULL_SPECIAL_CHARS, FILTER_REQUIRE_ARRAY);
        $this->remove_image = $remove_image ? array_filter($remove_image) : null;
        $step_image_current = filter_input(INPUT_POST, 'step_images_current', FILTER_SANITIZE_FULL_SPECIAL_CHARS, FILTER_REQUIRE_ARRAY);
        $this->step_images_current = $step_image_current ? array_filter($step_image_current) : null;
    }
}