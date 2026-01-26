<?php
/**
 * @author: AlexK
 * Date: 28-Nov-18
 * Time: 8:03 PM
 */

namespace Cookbook\Domain\Dietary;

class DietaryDishListPacker
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

        foreach ($this->restrictions as $id => $restrictions) {
            if ($restrictions) {
                foreach ($restrictions as $item) {
                    foreach ($item as $value) {
                        $a[$value['restriction_name']][] = $id;
                        $b[$value['restriction_name']] = $value['description'];
                    }

                }
            }
        }

        foreach ($a as $key => $value) {
            $list .= '<a class="dietary" recipes="'
                . implode(',', array_unique($value))
                . '" data-toggle="popover" data-content="'
                . $b[$key]
                . '" href="#">'
                . $key
                . '</a>, ';
        }

        return trim($list, ', ');
    }
}