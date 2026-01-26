<?php
/**
 * @author: AlexK
 * Date: 18-Nov-18
 * Time: 3:38 AM
 */

namespace Cookbook\Domain\Categories;


class CategoryListFactory implements CategoryPackerFactory
{

    private $product;
    private $categories;

    public function __construct($product = null, $id = null, $term = null)
    {
        $model = new CategoriesModel;

        if ($term) {
            $this->categories = $model->getAutoComplete($term);
        } elseif ($product) {
            $this->product = $product;
            $function = 'get' . ucfirst($this->product) . 'Categories';
            $this->categories = $model->$function($id);
        } else {
            $this->categories = $model->getCategoryList();
        }
    }

    public function commaListPacker()
    {
        return new CategoryListPacker($this->product, $this->categories);
    }

    public function autocompleteListPacker()
    {
        return new CategoryAutocompletePacker($this->categories);
    }

    public function autocompleteOptionsPacker()
    {
        return new CategoryOptionsPacker($this->categories);
    }

    public function badgePacker($builder)
    {
        return new CategoryBadgePacker($builder, $this->categories);
    }
}