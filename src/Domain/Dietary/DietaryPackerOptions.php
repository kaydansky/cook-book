<?php
/**
 * @author : AlexK
 * Date: 27-Nov-18
 * Time: 1:24 PM
 */

namespace Cookbook\Domain\Dietary;


class DietaryPackerOptions implements DietaryPacker
{

    private $restrictions;
    private $id;

    public function __construct(array $restrictions = null, int $id = null)
    {
        $this->restrictions = $restrictions;
        $this->id = $id;
    }

    public function pack()
    {
        if (! $this->restrictions) {
            return null;
        }

        $list = '';

        foreach ($this->restrictions as $value) {
            $selected = $value['dietary_restriction_id'] == $this->id ? ' selected' : '';
            $list .= '<option value="' . $value['dietary_restriction_id'] . '"' . $selected . '>' . $value['restriction_name'] . '</option>';
        }

        return $list;
    }

}