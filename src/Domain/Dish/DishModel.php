<?php

namespace Cookbook\Domain\Dish;

use Cookbook\{DB\Database, Helpers\Sanitizer, Picture\Picture, Pagination\Pagination};
use Delight\Auth\Role;

/**
 * Description of Model
 *
 * @author AlexK
 */
class DishModel
{
    private $db;
    private $gdb;
    private $imgPath = PATH_IMAGES . 'dish/';
    private $dish_title;
    private $dish_subtitle;
    private $source;
    private $source_link;
    private $notes;
    private $foh_kitchen_assembly;
    private $foh_dining_assembly;
    private $foh_purveyors;
    private $date;
    private $dish_date;
    private $alternative_title;
    private $quantity;
    private $comment;
    private $alternative_subtitle;
    private $step_content;
    private $marking;
    private $wine_pairing;
    private $china_id;
    private $approved;
    private $remove_image;
    private $image_filenames;
    private $step_image_current;
    private $picture;
    private $auth;
    private $ingredient_id;
    private $ingredient_unit;
    private $recipe_id;
    private $recipe_option;
    private $this_china_id;
    private $this_marking;
    private $this_wine_pairing;
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
            $this->patternWhereMy = ' WHERE t1.approved = 1';
            $this->patternAndMy = ' AND t1.approved = 1';
        }
    }

    public function __set($name, $value)
    {
        $this->$name = $value;
    }

    public function getDishList()
    {
        $page = new Pagination();
        $page->db = $this->db;
        $page->queryTotal = 'SELECT COUNT(DISTINCT dish_id) AS total FROM dishes t1'
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
SELECT DISTINCT t1.dish_id, t1.dish_title, t1.dish_subtitle, t1.description, t1.image_filenames, t1.source, t1.source_link, t1.date_added, t1.date_modified, t2.first_name, t2.last_name 
FROM dishes t1 
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

    public function getDishListSearch($q, $whole_word)
    {
        if ($whole_word !== 'on') {
            $pattern = 'LIKE ?';
            $queryData = ["%$q%", "%$q%", "%$q%"];
        } else {
            $pattern = 'REGEXP \'[[:<:]]' . $q . '[[:>:]]\'';
            $queryData = null;
        }

        $patternUser = $this->userPattern($q, $whole_word);

        $page = new Pagination();
        $page->db = $this->db;
        $page->queryTotal = 'SELECT COUNT(t1.dish_id) AS total FROM dishes t1 WHERE (dish_title ' . $pattern . ' OR description ' . $pattern . ' OR dish_subtitle ' . $pattern . $patternUser . ')' . $this->patternAndMy;
        $page->queryPage = 'SELECT dish_id, dish_title, dish_subtitle, description, image_filenames, date_added, date_modified FROM dishes t1 WHERE (dish_title ' . $pattern . ' OR description ' . $pattern . ' OR dish_subtitle ' . $pattern . $patternUser . ')' . $this->patternAndMy . ' ORDER BY dish_title';
        $page->queryData = $queryData;

        return [
            'data' => $page->getData(),
            'paginator' => $page->getPaginator(),
            'totalRecords' => $page->totalRecords,
            'totalPages' => $page->totalPages,
            'currentPage' => $page->currentPage
        ];
    }

    public function getDishListIsIngredient($q_ingredient, $is_ingredient, $whole_word) {
        if ($whole_word !== 'on') {
            $pattern = $is_ingredient === 'no' ? 'NOT LIKE ?' : 'LIKE ?';
            $queryData = ["%$q_ingredient%"];
        } else {
            $pattern = $is_ingredient === 'no' ? 'NOT REGEXP \'[[:<:]]' . $q_ingredient . '[[:>:]]\'' : 'REGEXP \'[[:<:]]' . $q_ingredient . '[[:>:]]\'';
            $queryData = null;
        }

        $page = new Pagination();
        $page->db = $this->db;
        $page->queryTotal = 'SELECT DISTINCT COUNT(DISTINCT t1.dish_id) AS total FROM dishes t1 JOIN dish_ingredients t2 USING(dish_id) JOIN ' . DATABASE_DEFAULT . '.ingredients t3 ON t2.ingredient_id = t3.ingredient_id WHERE t3.ingredient ' . $pattern . $this->patternAndMy;
        $page->queryPage = 'SELECT DISTINCT t1.dish_id, t1.dish_title, t1.dish_subtitle, t1.description, t1.image_filenames, t1.date_added, t1.date_modified FROM dishes t1 JOIN dish_ingredients t2 USING(dish_id) JOIN ' . DATABASE_DEFAULT . '.ingredients t3 ON t2.ingredient_id = t3.ingredient_id WHERE t3.ingredient ' . $pattern . $this->patternAndMy .' ORDER BY t1.dish_title';
        $page->queryData = $queryData;

        return [
            'data' => $page->getData(),
            'paginator' => $page->getPaginator(),
            'totalRecords' => $page->totalRecords,
            'totalPages' => $page->totalPages,
            'currentPage' => $page->currentPage
        ];
    }

    public function getDishListCategory($id)
    {
        $page = new Pagination();
        $page->db = $this->db;
        $page->queryTotal = 'SELECT DISTINCT COUNT(DISTINCT dish_id) AS total FROM dishes t1 JOIN dish_categories t2 USING(dish_id) WHERE t2.category_id = ?' . $this->patternAndMy;
        $page->queryPage = 'SELECT DISTINCT dish_id, dish_title, dish_subtitle, description, image_filenames, date_added, date_modified FROM dishes t1 JOIN dish_categories t2 USING(dish_id) WHERE t2.category_id = ? '. $this->patternAndMy . ' ORDER BY t1.dish_title';
        $page->queryData = [$id];

        return [
            'data' => $page->getData(),
            'paginator' => $page->getPaginator(),
            'totalRecords' => $page->totalRecords,
            'totalPages' => $page->totalPages,
            'currentPage' => $page->currentPage
        ];
    }

    public function getDishListDaysRange($days) {
        $page = new Pagination();
        $page->db = $this->db;
        $page->queryTotal = 'SELECT COUNT(dish_id) AS total FROM dishes t1 WHERE date_added >= DATE_ADD(CURDATE(), INTERVAL -? DAY)' . $this->patternAndMy;
        $page->queryPage = 'SELECT dish_id, dish_title, dish_subtitle, description, image_filenames, date_added, date_modified FROM dishes t1 WHERE date_added >= DATE_ADD(CURDATE(), INTERVAL -? DAY)' . $this->patternAndMy . ' ORDER BY dish_title';
        $page->queryData = [$days];

        return [
            'data' => $page->getData(),
            'paginator' => $page->getPaginator(),
            'totalRecords' => $page->totalRecords,
            'totalPages' => $page->totalPages,
            'currentPage' => $page->currentPage
        ];
    }

    public function getAutoComplete($term, $whole_word)
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

        return $this->db->select('SELECT dish_id, dish_title, dish_subtitle FROM dishes t1 WHERE (dish_title ' . $pattern . ' OR description ' . $pattern . ' OR dish_subtitle ' . $pattern . $patternUser . ')' . $this->patternAndMy . ' ORDER BY dish_title', $queryData);
    }

    public function getAutoCompleteIsIngredient($term, $is_ingredient, $whole_word) {
        if ($is_ingredient === 'no') {
            return [['dish_id' => null, 'dish_title' => 'Autocomplete disabled for excluded search. Write in your query and press Enter.']];
        }

        $this->db->exec('SET NAMES \'utf8\'');

        if ($whole_word !== 'on') {
            $pattern = $is_ingredient === 'no' ? 'NOT LIKE ?' : 'LIKE ?';
            return $this->db->select('SELECT DISTINCT t1.dish_id, t1.dish_title, t1.description, t1.image_filenames, t1.date_added, t1.date_modified FROM dishes t1 JOIN dish_ingredients t2 USING(dish_id) JOIN ' . DATABASE_DEFAULT . '.ingredients t3 ON t2.ingredient_id = t3.ingredient_id WHERE t3.ingredient ' . $pattern . $this->patternAndMy . ' ORDER BY t1.dish_title', ["%$term%"]);
        } else {
            $pattern = $is_ingredient === 'no' ? 'NOT REGEXP \'[[:<:]]' . $term . '[[:>:]]\'' : 'REGEXP \'[[:<:]]' . $term . '[[:>:]]\'';
            return $this->db->select('SELECT DISTINCT t1.dish_id, t1.dish_title, t1.description, t1.image_filenames, t1.date_added, t1.date_modified FROM dishes t1 JOIN dish_ingredients t2 USING(dish_id) JOIN ' . DATABASE_DEFAULT . '.ingredients t3 ON t2.ingredient_id = t3.ingredient_id WHERE t3.ingredient ' . $pattern . $this->patternAndMy . ' ORDER BY t1.dish_title');
        }
    }

    public function getAutoCompleteSource($term)
    {
        $this->db->exec('SET NAMES \'utf8\'');

        return $this->db->select('SELECT DISTINCT source AS `value` FROM dishes WHERE source LIKE ? ORDER BY source', ["%$term%"]);
    }

    public function getAutoCompleteUser($term)
    {
        $this->gdb->exec('SET NAMES \'utf8\'');

        return $this->gdb->select('SELECT user_id AS `id`, CONCAT(first_name, " ", last_name) AS `value` FROM accounts WHERE CONCAT(first_name, " ", last_name) LIKE ? ORDER BY first_name', ["%$term%"]);
    }

    public function getDish($id)
    {
        $this->db->exec('SET NAMES \'utf8\'');

        return $this->db->selectRow('SELECT t1.*, t2.china_name, t2.manufacturer FROM dishes t1 LEFT JOIN china t2 USING(china_id) WHERE dish_id = ? LIMIT 0, 1', [$id]);
    }

    public function getDishIngredients($id)
    {
        $this->db->exec('SET NAMES \'utf8\'');

        return $this->db->select('SELECT t1.*, t2.ingredient, t2.description AS IngDesc, t3.dish_title, t3.description AS DishDesc, t4.unit, t4.mimecode, t4.type, t4.measurement_system, t4.conversion, t4.conversion_unit FROM dish_ingredients t1 LEFT JOIN ' . DATABASE_DEFAULT . '.ingredients t2 USING(ingredient_id) LEFT JOIN dishes t3 ON t3.dish_id = t1.ingredient_id LEFT JOIN units t4 ON t1.unit_id = t4.unit_id WHERE t1.dish_id = ?', [$id]);
    }

    public function getAlternatives($id)
    {
        return $this->db->select('SELECT t1.*, t2.china_name, t2.manufacturer FROM dish_dates t1 LEFT JOIN china t2 USING(china_id) WHERE t1.dish_id = ? AND t1.alternative_date IS NOT NULL', [$id]);
    }

    public function getDishSteps($id)
    {
        return $this->db->select('SELECT * FROM dish_steps WHERE dish_id = ? ORDER BY id', [$id]);
    }

    public function getDishRecipes($id)
    {
        return $this->db->select('SELECT t1.*, t2.recipe_title, t2.description, t2.notes FROM dish_recipes t1 JOIN recipes t2 USING(recipe_id) WHERE t1.dish_id = ?', [$id]);
    }

    public function getDishGrid()
    {
        $this->db->exec('SET NAMES \'utf8\'');

        return $this->db->select('SELECT dish_id, dish_title, dish_subtitle, description, alt_search_terms, approved FROM dishes ORDER BY dish_title');
    }

    public function createDish($dish_title, $description, $category_id)
    {
        $this->filterPost();
        $image_filenames = [];

        if (isset($_FILES['image_id']['tmp_name'])) {
            foreach ($_FILES['image_id']['tmp_name'] as $file) {
                $image_filenames[] = $this->uploadImage($file);
            }
        }

        $this->db->exec('SET NAMES \'utf8\'');
        $this->db->insert(
            'dishes',
            [
                'dish_title' => $dish_title,
                'dish_subtitle' => $this->dish_subtitle,
                'description' => $description,
                'source' => $this->source,
                'source_link' => $this->source_link,
                'notes' => $this->notes,
                'foh_kitchen_assembly' => $this->foh_kitchen_assembly,
                'foh_dining_assembly' => $this->foh_dining_assembly,
                'foh_purveyors' => $this->foh_purveyors,
                'user_id' => $this->auth->getUserId(),
                'approved' => $this->auth->hasRole(Role::CHEF) ? 1 : 0,
                'approved_by' => $this->auth->hasRole(Role::CHEF) ? $this->auth->getUserId() : 0,
                'image_filenames' => count($image_filenames) ? implode(',', $image_filenames) : null,
                'china_id' => $this->this_china_id,
                'marking' => $this->this_marking,
                'wine_pairing' => $this->this_wine_pairing,
                'dish_date' => $this->dish_date
            ]
        );

        $newId = $this->db->getLastInsertId();

        if (! $newId) {
            return false;
        }

        $this->insertCategories($category_id, $newId);
        $this->insertIngredients($newId);
        $this->insertSteps($newId);
        $this->insertRecipes($newId);
        $this->insertAlernatives($newId);

        return $newId;
    }

    public function updateDish($id, $recipe_title, $description, $category_id)
    {
        $this->filterPost();
        $this->db->exec('SET NAMES \'utf8\'');

//        $this->db->delete('dish_categories', ['dish_id' => $id]);
        $this->insertCategories($category_id, $id);
//        $this->db->delete('dish_ingredients', ['dish_id' => $id]);
        $this->insertIngredients($id);
//        $this->db->delete('dish_steps', ['dish_id' => $id]);
        $this->insertSteps($id);
//        $this->db->delete('dish_dates', ['dish_id' => $id]);
        $this->insertAlernatives($id);
//        $this->db->delete('dish_recipes', ['dish_id' => $id]);
        $this->insertRecipes($id);

        $this->removeImages();
        $image_filenames = null;

        if (isset($_FILES['image_id']['tmp_name'])) {
            foreach ($_FILES['image_id']['tmp_name'] as $file) {
                $imgFileName = $this->uploadImage($file);

                if ($imgFileName) {
                    $image_filenames[] = $imgFileName;
                }
            }
        }

        $dataDishes = [
            'dish_title' => $recipe_title,
            'dish_subtitle' => $this->dish_subtitle,
            'description' => $description,
            'source' => $this->source,
            'source_link' => $this->source_link,
            'foh_kitchen_assembly' => $this->foh_kitchen_assembly,
            'foh_dining_assembly' => $this->foh_dining_assembly,
            'foh_purveyors' => $this->foh_purveyors,
            'notes' => $this->notes,
            'china_id' => $this->this_china_id,
            'marking' => $this->this_marking,
            'wine_pairing' => $this->this_wine_pairing,
            'dish_date' => $this->dish_date
        ];

        if ($this->auth->hasRole(Role::CHEF)) {
            if ($this->approved == 'on') {
                $dataDishes['approved'] = 1;
                $dataDishes['approved_by'] = $this->auth->getUserId();
            } else {
                $dataDishes['approved'] = 0;
            }
        }

        $aImg = [];

        if (is_array($this->image_filenames) && $this->image_filenames[0]) {
            $aImg = $this->image_filenames;
        }

        if ($image_filenames) {
            $aImg = array_merge($aImg, $image_filenames);
        }

        if ($aImg) {
            $dataDishes['image_filenames'] = count($aImg) > 1 ? implode(',', $aImg) : $aImg[0];
        }

        $this->db->update('dishes', ['image_filenames' => null], ['dish_id' => $id]);
        $this->db->update('dishes', $dataDishes, ['dish_id' => $id]);
    }

    public function deleteDish($id)
    {
        $dish = $this->getDish($id);
        $this->picture->path = $this->imgPath;
        $this->picture->file = $dish['image_filenames'];
        $this->picture->deleteImages();
        $this->db->delete('dishes', ['dish_id' => $id]);
        $this->db->delete('dish_categories', ['dish_id' => $id]);
        $this->db->delete('dish_dates', ['dish_id' => $id]);
        $this->db->delete('dish_ingredients', ['dish_id' => $id]);
        $this->db->delete('dish_recipes', ['dish_id' => $id]);
        $this->db->delete('dish_steps', ['dish_id' => $id]);
    }

    public function approveDish($id)
    {
        $this->db->update('dishes', ['approved' => 1, 'approved_by' => $this->auth->getUserId()], ['dish_id' => $id]);
    }

    public function disapproveDish($id)
    {
        $this->db->update('dishes', ['approved' => 0], ['dish_id' => $id]);
    }

    private function filterPost()
    {
        $this->dish_title = Sanitizer::sanitize(filter_input(INPUT_POST, 'dish_title', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        $this->dish_subtitle = Sanitizer::sanitize(filter_input(INPUT_POST, 'dish_subtitle', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        $this->source = Sanitizer::sanitize(filter_input(INPUT_POST, 'source', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        $this->source_link = Sanitizer::sanitize(filter_input(INPUT_POST, 'source_link', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        $this->notes = Sanitizer::sanitize(filter_input(INPUT_POST, 'notes', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        $this->approved = Sanitizer::sanitize(filter_input(INPUT_POST, 'approved', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        $this->image_filenames = Sanitizer::sanitize(filter_input(INPUT_POST, 'image_filenames', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        $this->foh_kitchen_assembly = Sanitizer::sanitize(filter_input(INPUT_POST, 'foh_kitchen_assembly', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        $this->foh_dining_assembly = Sanitizer::sanitize(filter_input(INPUT_POST, 'foh_dining_assembly', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        $this->foh_purveyors = Sanitizer::sanitize(filter_input(INPUT_POST, 'foh_purveyors', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        $this->this_china_id = filter_input(INPUT_POST, 'this_china_id', FILTER_SANITIZE_NUMBER_INT) ?: null;
        $this->this_marking = Sanitizer::sanitize(filter_input(INPUT_POST, 'this_marking', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        $this->this_wine_pairing = Sanitizer::sanitize(filter_input(INPUT_POST, 'this_wine_pairing', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        $this->dish_date = Sanitizer::sanitize(filter_input(INPUT_POST, 'dish_date', FILTER_SANITIZE_FULL_SPECIAL_CHARS));

        $date = filter_input(INPUT_POST, 'date', FILTER_SANITIZE_FULL_SPECIAL_CHARS, FILTER_REQUIRE_ARRAY);
        $this->date = $date ? array_filter($date) : null;
        $alternative_title = filter_input(INPUT_POST, 'alternative_title', FILTER_SANITIZE_FULL_SPECIAL_CHARS, FILTER_REQUIRE_ARRAY);
        $this->alternative_title = $alternative_title ? array_filter($alternative_title) : null;
        $alternative_subtitle = filter_input(INPUT_POST, 'alternative_subtitle', FILTER_SANITIZE_FULL_SPECIAL_CHARS, FILTER_REQUIRE_ARRAY);
        $this->alternative_subtitle = $alternative_subtitle ? array_filter($alternative_subtitle) : null;
        $marking = filter_input(INPUT_POST, 'marking', FILTER_SANITIZE_FULL_SPECIAL_CHARS, FILTER_REQUIRE_ARRAY);
        $this->marking = $marking ? array_filter($marking) : null;
        $wine_pairing = filter_input(INPUT_POST, 'wine_pairing', FILTER_SANITIZE_FULL_SPECIAL_CHARS, FILTER_REQUIRE_ARRAY);
        $this->wine_pairing = $wine_pairing ? array_filter($wine_pairing) : null;
        $china_id = filter_input(INPUT_POST, 'china_id', FILTER_SANITIZE_NUMBER_INT, FILTER_REQUIRE_ARRAY);
        $this->china_id = $china_id ? array_filter($china_id) : null;
        $step_content = filter_input(INPUT_POST, 'step_content', FILTER_SANITIZE_FULL_SPECIAL_CHARS, FILTER_REQUIRE_ARRAY);
        $this->step_content = $step_content ? array_filter($step_content) : null;
        $ingredient_id = filter_input(INPUT_POST, 'ingredient_id', FILTER_SANITIZE_NUMBER_INT, FILTER_REQUIRE_ARRAY);
        $this->ingredient_id = $ingredient_id ? array_filter($ingredient_id) : null;
        $quantity = filter_input(INPUT_POST, 'quantity', FILTER_SANITIZE_FULL_SPECIAL_CHARS, FILTER_REQUIRE_ARRAY);
        $this->quantity = $quantity ? array_filter($quantity) : null;
        $ingredient_unit = filter_input(INPUT_POST, 'ingredient_unit', FILTER_SANITIZE_NUMBER_INT, FILTER_REQUIRE_ARRAY);
        $this->ingredient_unit = $ingredient_unit ? array_filter($ingredient_unit) : null;
        $comment = filter_input(INPUT_POST, 'comment', FILTER_SANITIZE_FULL_SPECIAL_CHARS, FILTER_REQUIRE_ARRAY);
        $this->comment = $comment ? array_filter($comment) : null;
        $recipe_id = filter_input(INPUT_POST, 'recipe_id', FILTER_SANITIZE_NUMBER_INT, FILTER_REQUIRE_ARRAY);
        $this->recipe_id = $recipe_id ? array_filter($recipe_id) : null;
        $recipe_option = filter_input(INPUT_POST, 'recipe_option', FILTER_SANITIZE_FULL_SPECIAL_CHARS, FILTER_REQUIRE_ARRAY);
        $this->recipe_option = $recipe_option ? array_filter($recipe_option) : null;
        $remove_image = filter_input(INPUT_POST, 'remove_image', FILTER_SANITIZE_FULL_SPECIAL_CHARS, FILTER_REQUIRE_ARRAY);
        $this->remove_image = $remove_image ? array_filter($remove_image) : null;
        $step_image_current = filter_input(INPUT_POST, 'step_image_current', FILTER_SANITIZE_FULL_SPECIAL_CHARS, FILTER_REQUIRE_ARRAY);
        $this->step_image_current = $step_image_current ? array_filter($step_image_current) : null;
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

    private function insertCategories($categoryIds, $dishId)
    {
        if ($categoryIds && count($categoryIds)) {
            $this->db->delete('dish_categories', ['dish_id' => $dishId]);

            foreach (array_filter($categoryIds) as $catId) {
                $this->db->insert(
                    'dish_categories',
                    [
                        'dish_id' => $dishId,
                        'category_id' => $catId
                    ]
                );
            }
        }
    }

    private function insertIngredients($dishId)
    {
        if ($this->ingredient_id && count($this->ingredient_id)) {
            $this->db->delete('dish_ingredients', ['dish_id' => $dishId]);

            foreach ($this->ingredient_id as $key => $value) {

                if ($value === '') {
                    continue;
                }

                $this->db->insert(
                    'dish_ingredients',
                    [
                        'dish_id' => $dishId,
                        'ingredient_id' => $value,
                        'quantity' => $this->quantity[$key] ?? null,
                        'unit_id' => $this->ingredient_unit[$key] ?? null,
                        'comment' => $this->comment[$key] ?? null
                    ]
                );
            }
        }
    }

    private function insertSteps($dishId)
    {
        if ($this->step_content && count($this->step_content)) {
            $this->db->delete('dish_steps', ['dish_id' => $dishId]);

            foreach ($this->step_content as $key => $value) {
                $image_filename = null;

                if (isset($_FILES['step_image']['tmp_name'][$key])) {
                    $image_filename = $this->uploadImage($_FILES['step_image']['tmp_name'][$key]);
                }

                if (! $image_filename && isset($this->step_image_current[$key])) {
                    $image_filename = $this->step_image_current[$key];
                } elseif (isset($this->step_image_current[$key])) {
                    $this->remove_image[$this->step_image_current[$key]] = 'on';
                }

                $this->db->insert(
                    'dish_steps',
                    [
                        'dish_id' => $dishId,
                        'step_content' => $value,
                        'step_image' => $image_filename
                    ]
                );
            }
        }
    }

    private function insertRecipes($dishId)
    {
        if ($this->recipe_id && count($this->recipe_id)) {
            $this->db->delete('dish_recipes', ['dish_id' => $dishId]);

            foreach ($this->recipe_id as $key => $value) {

                if ($value === '') {
                    continue;
                }

                $this->db->insert(
                    'dish_recipes',
                    [
                        'dish_id' => $dishId,
                        'recipe_id' => $value,
                        'recipe_option' => $this->recipe_option[$key] ?? null,
                    ]
                );
            }
        }
    }

    private function insertAlernatives($dishId)
    {
        if ($this->alternative_title && count($this->alternative_title)) {
            $this->db->delete('dish_dates', ['dish_id' => $dishId]);

            foreach ($this->alternative_title as $key => $value) {

                if ($value === '') {
                    continue;
                }

                $this->db->insert(
                    'dish_dates',
                    [
                        'dish_id' => $dishId,
                        'alternative_date' => $this->date[$key] ?? null,
                        'alternative_title' => $value,
                        'alternative_subtitle' => $this->alternative_subtitle[$key] ?? null,
                        'china_id' => $this->china_id[$key] ?? null,
                        'marking' => $this->marking[$key] ?? null,
                        'wine_pairing' => $this->wine_pairing[$key] ?? null
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
                    $this->picture->path = $this->imgPath;
                    $this->picture->file = $key;
                    $this->picture->deleteImages();
                    $this->db->update('dish_steps', ['step_image' => null], ['step_image' => $key]);
                    $this->image_filenames = str_replace($key, '', $this->image_filenames);
                }
            }
        }

        if ($this->image_filenames !== '') {
            $this->image_filenames = strpos($this->image_filenames, ',') !== false
                ? array_filter(explode(',', $this->image_filenames))
                : [$this->image_filenames];
        }
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
}