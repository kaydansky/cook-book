<?php
/**
 * @author : AlexK
 * Date: 27-Nov-18
 * Time: 1:21 PM
 */

namespace Cookbook\Domain\Dietary;


interface DietaryPackerFactory
{
    public function commaListPacker();

    public function autocompleteInputPacker();

    public function optionsPacker();

    public function recipeListPacker();

    public function dishListPacker();
}