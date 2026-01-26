<?php
/**
 * @author : AlexK
 * Date: 21-Nov-18
 * Time: 1:53 AM
 */

namespace Cookbook\Domain\Categories;

use Cookbook\Output\OutputBuilder;

class CategoryAutocompletePacker implements CategoryPacker
{

    protected $templateCategory = 'Categories/edit_category.html';
    private $categories;

    public function __construct(array $categories = null)
    {
        $this->categories = $categories;
    }

    public function pack()
    {
        $catList = '';

        if ($this->categories) {
            $builder = new OutputBuilder();

            foreach ($this->categories as $value) {
                $cat = $builder
                    ->setTemplate($this->templateCategory)
                    ->addBrackets([
                        'CATEGORY_ID' => $value['category_id'],
                        'CATEGORY_NAME' => $value['category_name']
                    ])
                    ->build();

                $catList .= $cat->result;
            }
        }

        return $catList;
    }

}
