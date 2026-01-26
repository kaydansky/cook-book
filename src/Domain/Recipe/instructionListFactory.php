<?php
/**
 * @author: AlexK
 * Date: 01-May-19
 * Time: 2:09 PM
 */

namespace Cookbook\Domain\Recipe;

class instructionListFactory
{
    private $modelName = 'Cookbook\Domain\Recipe\RecipeModel';
    private $model;
    private $id;
    private $ingredients;
    private $recipes;
    private $images;

    public function __construct($resolver, $id, $ingredients, $recipes, $images = '')
    {
        $this->id = $id;
        $this->model = $resolver->resolve($this->modelName);
        $this->ingredients = $ingredients;
        $this->recipes = $recipes;
        $this->images = $images;
    }

    public function instructionPacker()
    {
        return new instructionListPacker($this->model->getRecipeSteps($this->id), $this->ingredients, $this->recipes, $this->images);
    }
}