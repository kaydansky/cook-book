<?php
/**
 * @author: AlexK
 * Date: 28-Nov-18
 * Time: 3:16 PM
 */

namespace Cookbook\Domain\Dietary;

class DietaryRecipeListPacker implements DietaryPacker
{
    private $restrictions;

    public function __construct(array $restrictions = null)
    {
        $this->restrictions = $restrictions;
    }

    public function pack()
    {
        if (! $this->restrictions) {
            return false;
        }

        $list = '';
        $a = [];
        $b = [];

        foreach ($this->restrictions as $id => $restriction) {
            foreach ($restriction as $value) {
                $a[$value['restriction_name']][] = $id;
                $b[$value['restriction_name']] = $value['description'];
            }
        }

        foreach ($a as $key => $value) {
            $list .= '<a class="dietary" recipes="'
                . implode(',', $value)
                . '" data-toggle="popover" data-content="'
                . $b[$key]
                . '" href="#">'
                . $key
                . '</a>, ';
        }

        return trim($list, ', ');
    }
}