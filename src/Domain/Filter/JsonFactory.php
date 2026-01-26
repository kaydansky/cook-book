<?php
/**
 * @author : AlexK
 * Date: 24-Nov-18
 * Time: 6:02 PM
 */

namespace Cookbook\Domain\Filter;


interface JsonFactory
{
    public function search();

    public function category();

    public function isIngredient();

    public function recipeIngredient();

    public function recipe();

    public function source();
}