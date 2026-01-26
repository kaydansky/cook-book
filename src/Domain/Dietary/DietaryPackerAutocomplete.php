<?php
/**
 * @author : AlexK
 * Date: 27-Nov-18
 * Time: 1:23 PM
 */

namespace Cookbook\Domain\Dietary;

use Cookbook\Output\OutputBuilder;

class DietaryPackerAutocomplete implements DietaryPacker
{

    private $template = 'Dietary/edit_restriction.html';
    private $restrictions;
    private $factory;

    public function __construct($factory, array $restrictions = null)
    {
        $this->factory = $factory;
        $this->restrictions = $restrictions;
    }

    public function pack()
    {
        $list = '';

        if ($this->restrictions) {
            $builder = new OutputBuilder();

            foreach ($this->restrictions as $value) {
                $row = $builder
                    ->setTemplate($this->template)
                    ->addBrackets([
                        'RESTRICTION_ID' => $value['dietary_restriction_id'],
                        'RESTRICTION_LIST' => $this->factory->optionsPacker($value['dietary_restriction_id'])->pack()
                    ])
                    ->build();

                $list .= $row->result;
            }
        }

        return $list;
    }

}