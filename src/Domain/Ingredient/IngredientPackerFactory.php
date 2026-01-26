<?php
/**
 * Created by PhpStorm.
 * User: AlexK
 * Date: 18-Nov-18
 * Time: 7:38 PM
 */

namespace Cookbook\Domain\Ingredient;


interface IngredientPackerFactory
{
    public function recipeIngredientPacker();

    public function dishIngredientPacker();

    public function serviceIngredientPacker();

    public function ingredients();

    public function recipes();
}