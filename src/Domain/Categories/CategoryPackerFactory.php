<?php
/**
 * @author : AlexK
 * Date: 18-Nov-18
 * Time: 3:36 AM
 */

namespace Cookbook\Domain\Categories;


interface CategoryPackerFactory
{
    public function commaListPacker();

    public function autocompleteListPacker();

    public function autocompleteOptionsPacker();

    public function badgePacker($builder);
}