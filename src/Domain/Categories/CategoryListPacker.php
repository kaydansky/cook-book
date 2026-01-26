<?php
/**
 * Author: AlexK
 * Date: 18-Nov-18
 * Time: 3:21 AM
 */

namespace Cookbook\Domain\Categories;

class CategoryListPacker implements CategoryPacker
{
    private $product;
    private $categories;

    public function __construct(string $product, array $categories = null)
    {
        $this->product = $product;
        $this->categories = $categories;
    }

    public function pack()
    {
        if (! $this->categories) {
            return false;
        }

        $list = '';

        foreach ($this->categories as $value) {
            $list .= '<a href="/' . $this->product . '/?cat[]=' . $value['category_id'] . '">' . $value['category_name'] . '</a>, ';
        }

        return trim($list, ', ');
    }
}