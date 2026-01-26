<?php
/**
 * @author : AlexK
 * Date: 21-Nov-18
 * Time: 8:44 PM
 */

namespace Cookbook\Domain\Categories;


class CategoryOptionsPacker implements CategoryPacker
{

    private $categories;

    public function __construct(array $categories = null)
    {
        $this->categories = $categories;
    }

    public function pack()
    {
        if (! $this->categories) {
            return null;
        }

        $list = [];

        foreach ($this->categories as $value) {
            $list[] = [
                'id' => $value['category_id'],
                'value' => $value['category_name']
            ];
        }

        return json_encode($list);
    }

}