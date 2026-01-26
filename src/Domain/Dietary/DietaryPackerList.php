<?php
/**
 * @author : AlexK
 * Date: 27-Nov-18
 * Time: 1:22 PM
 */

namespace Cookbook\Domain\Dietary;

use Cookbook\Output\OutputBuilder;

class DietaryPackerList implements DietaryPacker
{

    private $templateDash = 'Common/dash.html';
    private $restrictions;

    public function __construct(array $restrictions = null)
    {
        $this->restrictions = $restrictions;
    }

    public function pack()
    {
        if (! $this->restrictions) {
            $builder = new OutputBuilder();
            $dash = $builder->setTemplate($this->templateDash)->build();
            return $dash->result;
        }

        $list = '';

        foreach ($this->restrictions as $value) {
            $list .= '<a id="' . $value['dietary_restriction_id'] . '" href="#">' . $value['restriction_name'] . '</a>, ';
        }

        return trim($list, ', ');
    }

}