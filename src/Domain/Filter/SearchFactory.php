<?php
/**
 * @author : AlexK
 * Date: 25-Nov-18
 * Time: 12:51 AM
 */

namespace Cookbook\Domain\Filter;

use Cookbook\Helpers\Sanitizer;

class SearchFactory implements SearchResult
{
    private $q;
    private $q_ingredient;
    private $is_ingredient;
    private $whole_word;
    private $c;
    private $cat;
    private $days;
    private $categoryName;
    private $product;
    private $model;
    private $my_items;
    private $source;
    private $author_id;
    private $author;
    private $range_date;
    private $range_from;
    private $range_to;
    private $sort_az;
    private $sort_added;
    private $sort_modified;
    private $sort_dish_date;
    private $p;

    public function __construct($product, $model)
    {
        $this->model = $model;
        $this->product = ucfirst($product);

        $this->p = Sanitizer::sanitize(filter_input(INPUT_GET, 'p', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        $this->q = Sanitizer::sanitize(filter_input(INPUT_GET, 'q', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        $this->q_ingredient = Sanitizer::sanitize(filter_input(INPUT_GET, 'q_ingredient', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        $this->is_ingredient = Sanitizer::sanitize(filter_input(INPUT_GET, 'is_ingredient', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        $this->whole_word = Sanitizer::sanitize(filter_input(INPUT_GET, 'whole_word', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        $this->c = filter_input(INPUT_GET, 'c', FILTER_SANITIZE_NUMBER_INT);
        $this->cat = filter_input(INPUT_GET, 'cat', FILTER_SANITIZE_NUMBER_INT, FILTER_REQUIRE_ARRAY);
        $this->categoryName = Sanitizer::sanitize(filter_input(INPUT_GET, 'category', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        $this->days = filter_input(INPUT_GET, 'days', FILTER_SANITIZE_NUMBER_INT);
        $this->my_items = Sanitizer::sanitize(filter_input(INPUT_GET, 'my_items', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        $this->source = Sanitizer::sanitize(filter_input(INPUT_GET, 'source', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        $this->author_id = filter_input(INPUT_GET, 'author_id', FILTER_SANITIZE_NUMBER_INT);
        $this->author = Sanitizer::sanitize(filter_input(INPUT_GET, 'author', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        $this->range_date = Sanitizer::sanitize(filter_input(INPUT_GET, 'range_date', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        $this->range_from = Sanitizer::sanitize(filter_input(INPUT_GET, 'range_from', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        $this->range_to = Sanitizer::sanitize(filter_input(INPUT_GET, 'range_to', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        $this->sort_az = Sanitizer::sanitize(filter_input(INPUT_GET, 'sort_az', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        $this->sort_added = Sanitizer::sanitize(filter_input(INPUT_GET, 'sort_added', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        $this->sort_modified = Sanitizer::sanitize(filter_input(INPUT_GET, 'sort_modified', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        $this->sort_dish_date = Sanitizer::sanitize(filter_input(INPUT_GET, 'sort_dish_date', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
    }

    public function result()
    {
        $product = strtolower($this->product);

        if ($this->source) {
            $this->model->patternSource = " AND source LIKE '$this->source'";
        }

        if ($this->author_id) {
            $this->model->patternAuthor = ' AND user_id = ' . $this->author_id;
        }

        if ($this->range_date && ($this->range_from || $this->range_to)) {
            switch ($this->range_date) {
                default: $dateField = 'date_added'; break;
                case 'modified': $dateField = 'date_modified'; break;
                case 'dish': $dateField = 'dish_date'; break;
            }

            $this->model->patternRange = ' AND ' . $dateField . ' BETWEEN STR_TO_DATE('
                . ($this->range_from ? "'" . $this->range_from . "'" : 'NOW()') .
                ", '%Y-%m-%d') AND STR_TO_DATE("
                . ($this->range_to ? "'" . $this->range_to . "'" : 'NOW()') .
                ", '%Y-%m-%d')";
        }

        if ($this->sort_az) {
            $this->model->patternSort = $product . '_title ' . $this->sort_az;
        }

        if ($this->sort_added) {
            $this->model->patternSort = 'date_added ' . $this->sort_added;
        }

        if ($this->sort_modified) {
            $this->model->patternSort = 'date_modified ' . $this->sort_modified;
        }

        if ($this->sort_dish_date) {
            $this->model->patternSort = 'dish_date ' . $this->sort_dish_date;
        }

        if ($this->cat) {
            $catPattern = '';

            foreach ($this->cat as $value) {
                $catPattern .= ' OR t3.category_id = ' . $value;
            }

            $this->model->patternCategoryJoin = ' RIGHT JOIN ' . $product . '_categories t3 USING(' . $product . '_id)';
            $this->model->patternCategoryWhere = ' AND (' . trim($catPattern, ' OR ') . ')';
        }

        if ($this->q) {
            $this->model->patternQuery = ' AND (' . $product . "_title LIKE '%" . $this->q . "%' OR t1.description LIKE '%" . $this->q . "%' OR t1.alt_search_terms LIKE '%" . $this->q . "%')";
        }

        if ($this->q_ingredient && $this->is_ingredient) {
            $this->model->patternIsIngredientJoin = ' RIGHT JOIN ' . $product . '_ingredients t4 USING(' . $product . '_id) JOIN ' . DATABASE_DEFAULT . '.ingredients t5 ON t4.ingredient_id = t5.ingredient_id';
            $this->model->patternIsIngredientWhere = ' AND t5.ingredient ' . ($this->is_ingredient === 'no' ? 'NOT LIKE' : 'LIKE') . " '%" . $this->q_ingredient . "%'";
        }

//        if ($this->q) {
//            $function = 'get' . $this->product . 'ListSearch';
//            $data = $this->model->$function($this->q, $this->whole_word);
//        } elseif ($this->q_ingredient && $this->is_ingredient) {
//            $function = 'get' . $this->product . 'ListIsIngredient';
//            $data = $this->model->$function($this->q_ingredient, $this->is_ingredient, $this->whole_word);
//        } elseif ($this->c) {
//            $function = 'get' . $this->product . 'ListCategory';
//            $data = $this->model->$function($this->c);
//        } elseif ($this->days) {
//            $function = 'get' . $this->product . 'ListDaysRange';
//            $data = $this->model->$function($this->days);
//        } else {
            $function = 'get' . $this->product . 'List';
            $data = $this->model->$function();
//        }

        return $data;
    }

    public function recipeChecked()
    {
        return $this->p !== 'dish' ? 'checked' : '';
    }

    public function dishChecked()
    {
        return $this->p === 'dish' ? 'checked' : '';
    }

    public function wholeWordChecked()
    {
        return $this->whole_word === 'on' ? 'checked' : '';
    }

    public function isIngredientNoChecked()
    {
        return $this->is_ingredient === 'no' ? 'checked' : '';
    }

    public function isIngredientYesChecked()
    {
        return $this->is_ingredient !== 'no' ? 'checked' : '';
    }

    public function isMyItemsChecked()
    {
        return $this->my_items === 'on' ? 'checked' : '';
    }

    public function query()
    {
        return $this->q;
    }

    public function queryIngredient()
    {
        return $this->q_ingredient && $this->is_ingredient ? $this->q_ingredient : '';
    }

    public function daysRange()
    {
        return $this->days;
    }

    public function categoryName()
    {
        return $this->categoryName;
    }

    public function source()
    {
        return urldecode($this->source ?? '');
    }

    public function rangeDateAddedChecked()
    {
        return $this->range_date == 'modified' ? '' : 'checked';
    }

    public function rangeDateModifiedChecked()
    {
        return $this->range_date == 'modified' ? 'checked' : '';
    }

    public function rangeDishDateChecked()
    {
        return $this->range_date == 'dish' ? 'checked' : '';
    }

    public function rangeFrom()
    {
        return $this->range_from;
    }

    public function rangeTo()
    {
        return $this->range_to;
    }

    public function sortAz()
    {
        if ($this->sort_az == 'asc') {
            return '<i class="fa fa-sort-up"></i>';
        } elseif ($this->sort_az == 'desc') {
            return '<i class="fa fa-sort-down"></i>';
        } else {
            return '<i class="fa fa-sort"></i>';
        }
    }

    public function sortAdded()
    {
        if ($this->sort_added == 'asc') {
            return '<i class="fa fa-sort-up"></i>';
        } elseif ($this->sort_added == 'desc') {
            return '<i class="fa fa-sort-down"></i>';
        } else {
            return '<i class="fa fa-sort"></i>';
        }
    }

    public function sortModified()
    {
        if ($this->sort_modified == 'asc') {
            return '<i class="fa fa-sort-up"></i>';
        } elseif ($this->sort_modified == 'desc') {
            return '<i class="fa fa-sort-down"></i>';
        } else {
            return '<i class="fa fa-sort"></i>';
        }
    }

    public function sortDishDate()
    {
        if ($this->sort_dish_date == 'asc') {
            return '<i class="fa fa-sort-up"></i>';
        } elseif ($this->sort_dish_date == 'desc') {
            return '<i class="fa fa-sort-down"></i>';
        } else {
            return '<i class="fa fa-sort"></i>';
        }
    }

    public function author()
    {
        return urldecode($this->author ?? '');
    }
}