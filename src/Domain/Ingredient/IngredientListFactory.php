<?php
/**
 * @author : AlexK
 * Date: 18-Nov-18
 * Time: 7:39 PM
 */

namespace Cookbook\Domain\Ingredient;

use Cookbook\DI\DiResolver;

class IngredientListFactory implements IngredientPackerFactory
{
    private $resolver;
    private $modelName = 'Cookbook\Domain\Ingredient\IngredientModel';
    private $model;
    private $id;
    private $packer;

    public $ingredients;
    public $recipes;

    public function __construct($id)
    {
        $this->id = $id;
        $this->resolver = new DiResolver();
        $this->model = $this->resolver->resolve($this->modelName);
    }

    public function recipeIngredientPacker($disabled = false, $print = false)
    {
        $this->packer = new IngredientListPacker($this->model->getRecipeIngredients($this->id), $disabled, false, $print);

        return $this->packer;
    }

    public function dishIngredientPacker($disabled = false, $print = false)
    {
        $this->packer = new IngredientListPacker($this->model->getDishIngredients($this->id), $disabled, false, $print);

        return $this->packer;
    }

    public function serviceIngredientPacker()
    {
        $this->packer = new IngredientListPacker($this->model->getRecipeIngredients($this->id), false, true);

        return $this->packer;
    }

    public function ingredients()
    {
        return $this->packer->ingredients();
    }

    public function recipes()
    {
        return $this->packer->recipes();
    }
}