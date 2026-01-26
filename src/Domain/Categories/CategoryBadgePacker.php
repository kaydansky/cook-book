<?php
/**
 * @author: AlexK
 * Date: 16-Apr-19
 * Time: 9:09 PM
 */

namespace Cookbook\Domain\Categories;


class CategoryBadgePacker implements CategoryPacker
{
    private $template = 'Search/category_badge.html';
    private $builder;
    private $categories;
    private $cat;

    public function __construct($builder, $categories)
    {
        $this->builder = $builder;
        $this->categories = $categories;
        $this->cat = filter_input(INPUT_GET, 'cat', FILTER_SANITIZE_NUMBER_INT, FILTER_REQUIRE_ARRAY) ?: [];
    }

    public function pack()
    {
        if (! $this->categories) {
            return false;
        }

        $list = '';

        foreach ($this->categories as $value) {
            $list .= $this->builder->setTemplate($this->template)
                ->addBrackets([
                    'CATEGORY_ID' => $value['category_id'],
                    'CATEGORY_NAME' => $value['category_name'],
                    'CHECKED' => in_array($value['category_id'], $this->cat) ? 'checked' : ''
                ])
                ->build()
                ->result;
        }

        return $list;
    }
}