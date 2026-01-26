<?php
/**
 * @author : AlexK
 * Date: 24-Nov-18
 * Time: 5:30 PM
 */

namespace Cookbook\Domain\Catalog;

use Cookbook\{DB\Database, Pagination\Pagination};

class CatalogModel
{

    protected $db;
    protected $gdb;
    protected $auth;

    private $my_items;
    private $patternWhereMy;
    private $patternAndMy;

    public function __construct()
    {
        $this->db = Database::dsn();
        $this->gdb = Database::genericDsn();
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

    public function getCatalogList($dishes = '', $recipes = '')
    {
        $page = new Pagination();
        $page->db = $this->db;
        $page->queryTotal = 'SELECT (SELECT COUNT(dish_id) FROM dishes t1' . $this->patternWhereMy . ') + (SELECT COUNT(recipe_id) FROM recipes t1' . $this->patternWhereMy . ') as total';
        $page->queryPage = '
(SELECT t1.dish_id as id, t1.dish_title as title, t1.source, t1.description, t1.image_filenames, t1.date_added as DateAdded, t1.dish_subtitle as recipe_id, t2.first_name as AuthorFirstName, t2.last_name as AuthorLastName 
FROM dishes t1 
LEFT JOIN ' . DATABASE_DEFAULT . '.accounts t2 USING (user_id)' . $this->patternWhereMy . ') 
UNION ALL 
(SELECT t1.recipe_id as id, t1.recipe_title as title, t1.source, t1.description, t1.image_filenames, t1.date_added, t1.recipe_id, t2.first_name as AuthorFirstName, t2.last_name as AuthorLastName 
FROM recipes t1 
LEFT JOIN ' . DATABASE_DEFAULT . '.accounts t2 USING (user_id)' . $this->patternWhereMy . ') 
ORDER BY DateAdded DESC';

        return [
            'data' => $page->getData(),
            'paginator' => $page->getPaginator(),
            'totalRecords' => $page->totalRecords,
            'totalPages' => $page->totalPages,
            'currentPage' => $page->currentPage
        ];
    }

    public function getCatalogListSearch($q, $whole_word)
    {
        if ($whole_word !== 'on') {
            $pattern = 'LIKE ?';
            $queryData = ["%$q%", "%$q%", "%$q%", "%$q%", "%$q%"];
        } else {
            $pattern = 'REGEXP \'[[:<:]]' . $q . '[[:>:]]\'';
            $queryData = null;
        }

        $patternUser = $this->userPattern($q, $whole_word);

        $page = new Pagination();
        $page->db = $this->db;
        $page->queryTotal = 'SELECT (SELECT COUNT(dish_id) FROM dishes t1 WHERE (dish_title ' . $pattern . ' OR description ' . $pattern . ' OR dish_subtitle ' . $pattern . $patternUser . ')' . $this->patternAndMy . ') + (SELECT COUNT(recipe_id) FROM recipes t1 WHERE (recipe_title ' . $pattern . ' OR description ' . $pattern . $patternUser . ')' . $this->patternAndMy . ') as total';
        $page->queryPage = 'SELECT dish_id as id, dish_title as title, description, image_filenames, date_added, dish_subtitle as recipe_id FROM dishes t1 WHERE (dish_title ' . $pattern . ' OR description ' . $pattern . ' OR dish_subtitle ' . $pattern . $patternUser . ')' . $this->patternAndMy . ' UNION ALL SELECT recipe_id, recipe_title, description, image_filenames, date_added, recipe_id FROM recipes t1 WHERE (recipe_title ' . $pattern . ' OR description ' . $pattern . $patternUser . ')' . $this->patternAndMy . ' ORDER BY title';
        $page->queryData = $queryData;

        return [
            'data' => $page->getData(),
            'paginator' => $page->getPaginator(),
            'totalRecords' => $page->totalRecords,
            'totalPages' => $page->totalPages,
            'currentPage' => $page->currentPage
        ];
    }

    public function getCatalogListIsIngredient($q_ingredient, $is_ingredient, $whole_word) {
        if ($whole_word !== 'on') {
            $pattern = $is_ingredient === 'no' ? 'NOT LIKE ?' : 'LIKE ?';
            $queryData = ["%$q_ingredient%", "%$q_ingredient%"];
        } else {
            $pattern = $is_ingredient === 'no' ? 'NOT REGEXP \'[[:<:]]' . $q_ingredient . '[[:>:]]\'' : 'REGEXP \'[[:<:]]' . $q_ingredient . '[[:>:]]\'';
            $queryData = null;
        }

        $page = new Pagination();
        $page->db = $this->db;
        $page->queryTotal = 'SELECT (SELECT COUNT(DISTINCT t1.dish_id) FROM dishes t1 JOIN dish_ingredients t2 USING(dish_id) JOIN ' . DATABASE_DEFAULT . '.ingredients t3 ON t2.ingredient_id = t3.ingredient_id WHERE t3.ingredient ' . $pattern . $this->patternAndMy . ') + (SELECT COUNT(DISTINCT t1.recipe_id) FROM recipes t1 JOIN recipe_ingredients t2 USING(recipe_id) JOIN ' . DATABASE_DEFAULT . '.ingredients t3 ON t2.ingredient_id = t3.ingredient_id WHERE t3.ingredient ' . $pattern . $this->patternAndMy . ') as total';
        $page->queryPage = 'SELECT DISTINCT t1.dish_id as id, t1.dish_title as title, t1.description, t1.image_filenames, t1.date_added, t1.dish_subtitle as recipe_id FROM dishes t1 JOIN dish_ingredients t2 USING(dish_id) JOIN ' . DATABASE_DEFAULT . '.ingredients t3 ON t2.ingredient_id = t3.ingredient_id WHERE t3.ingredient ' . $pattern . $this->patternAndMy . ' UNION ALL SELECT DISTINCT t1.recipe_id, t1.recipe_title, t1.description, t1.image_filenames, t1.date_added, t1.recipe_id FROM recipes t1 JOIN recipe_ingredients t2 USING(recipe_id) JOIN ' . DATABASE_DEFAULT . '.ingredients t3 ON t2.ingredient_id = t3.ingredient_id WHERE t3.ingredient ' . $pattern . $this->patternAndMy . ' ORDER BY title';
        $page->queryData = $queryData;

        return [
            'data' => $page->getData(),
            'paginator' => $page->getPaginator(),
            'totalRecords' => $page->totalRecords,
            'totalPages' => $page->totalPages,
            'currentPage' => $page->currentPage
        ];
    }

    public function getCatalogListCategory($id)
    {
        $page = new Pagination();
        $page->db = $this->db;
        $page->queryTotal = 'SELECT (SELECT DISTINCT COUNT(DISTINCT dish_id) FROM dishes t1 JOIN dish_categories t2 USING(dish_id) WHERE t2.category_id = ?' . $this->patternAndMy . ') + (SELECT DISTINCT COUNT(DISTINCT recipe_id) FROM recipes t1 JOIN recipe_categories t2 USING(recipe_id) WHERE t2.category_id = ?' . $this->patternAndMy . ') as total';
        $page->queryPage = 'SELECT DISTINCT dish_id as id, dish_title as title, description, image_filenames, date_added, dish_subtitle as recipe_id FROM dishes t1 JOIN dish_categories t2 USING(dish_id) WHERE t2.category_id = ?' . $this->patternAndMy . ' UNION ALL SELECT DISTINCT recipe_id, recipe_title, description, image_filenames, date_added, recipe_id FROM recipes t1 JOIN recipe_categories t2 USING(recipe_id) WHERE t2.category_id = ?' . $this->patternAndMy . ' ORDER BY title';
        $page->queryData = [$id, $id];

        return [
            'data' => $page->getData(),
            'paginator' => $page->getPaginator(),
            'totalRecords' => $page->totalRecords,
            'totalPages' => $page->totalPages,
            'currentPage' => $page->currentPage
        ];
    }

    public function getCatalogListDaysRange($days) {
        $page = new Pagination();
        $page->db = $this->db;
        $page->queryTotal = 'SELECT (SELECT COUNT(dish_id) FROM dishes t1 WHERE date_added >= DATE_ADD(CURDATE(), INTERVAL -? DAY)' . $this->patternAndMy . ') + (SELECT COUNT(recipe_id) FROM recipes t1 WHERE date_added >= DATE_ADD(CURDATE(), INTERVAL -? DAY)' . $this->patternAndMy . ') as total';
        $page->queryPage = 'SELECT dish_id as id, dish_title as title, description, image_filenames, date_added, dish_subtitle as recipe_id FROM dishes t1 WHERE date_added >= DATE_ADD(CURDATE(), INTERVAL -? DAY)' . $this->patternAndMy . ' UNION ALL SELECT recipe_id, recipe_title, description, image_filenames, date_added, recipe_id FROM recipes t1 WHERE date_added >= DATE_ADD(CURDATE(), INTERVAL -? DAY)' . $this->patternAndMy . ' ORDER BY title';
        $page->queryData = [$days, $days];

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
            $queryData = ["%$term%", "%$term%", "%$term%", "%$term%", "%$term%"];
        } else {
            $pattern = 'REGEXP \'[[:<:]]' . $term . '[[:>:]]\'';
            $queryData = null;
        }

        $patternUser = $this->userPattern($term, $whole_word);

        $this->db->exec('SET NAMES \'utf8\'');

        return $this->db->select('SELECT dish_id as id, dish_title as title, dish_subtitle as recipe_id FROM dishes t1 WHERE (dish_title ' . $pattern . ' OR description ' . $pattern . ' OR dish_subtitle ' . $pattern . $patternUser . ')' . $this->patternAndMy . ' UNION ALL SELECT recipe_id, recipe_title, recipe_id FROM recipes t1 WHERE (recipe_title ' . $pattern . ' OR description ' . $pattern . $patternUser . ')' . $this->patternAndMy . ' ORDER BY title', $queryData);
    }

    public function getAutoCompleteIsIngredient($term, $is_ingredient, $whole_word) {
        if ($is_ingredient === 'no') {
            return [['id' => null, 'title' => 'Autocomplete disabled for excluded search. Insert your query and press Enter.']];
        }

        $this->db->exec('SET NAMES \'utf8\'');

        if ($whole_word !== 'on') {
            $pattern = $is_ingredient === 'no' ? 'NOT LIKE ?' : 'LIKE ?';

            return $this->db->select('SELECT DISTINCT t1.dish_id as id, t1.dish_title as title, t1.dish_subtitle as recipe_id FROM dishes t1 JOIN dish_ingredients t2 USING(dish_id) JOIN ' . DATABASE_DEFAULT . '.ingredients t3 ON t2.ingredient_id = t3.ingredient_id WHERE t3.ingredient ' . $pattern . $this->patternAndMy . ' UNION ALL SELECT DISTINCT t1.recipe_id, t1.recipe_title, t1.recipe_id FROM recipes t1 JOIN recipe_ingredients t2 USING(recipe_id) JOIN ' . DATABASE_DEFAULT . '.ingredients t3 ON t2.ingredient_id = t3.ingredient_id WHERE t3.ingredient ' . $pattern . $this->patternAndMy . ' ORDER BY title', ["%$term%", "%$term%"]);
        } else {
            $pattern = $is_ingredient === 'no' ? 'NOT REGEXP \'[[:<:]]' . $term . '[[:>:]]\'' : 'REGEXP \'[[:<:]]' . $term . '[[:>:]]\'';
            return $this->db->select('SELECT DISTINCT t1.dish_id as id, t1.dish_title as title, t1.dish_subtitle as recipe_id FROM dishes t1 JOIN dish_ingredients t2 USING(dish_id) JOIN ' . DATABASE_DEFAULT . '.ingredients t3 ON t2.ingredient_id = t3.ingredient_id WHERE t3.ingredient ' . $pattern . $this->patternAndMy . ' UNION ALL SELECT DISTINCT t1.recipe_id, t1.recipe_title, t1.recipe_id FROM recipes t1 JOIN recipe_ingredients t2 USING(recipe_id) JOIN ' . DATABASE_DEFAULT . '.ingredients t3 ON t2.ingredient_id = t3.ingredient_id WHERE t3.ingredient ' . $pattern . $this->patternAndMy . ' ORDER BY title');
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