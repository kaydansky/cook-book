<?php
/**
 * @author: AlexK
 * Date: 01-May-19
 * Time: 11:22 PM
 */

namespace Cookbook\Domain\Recipe;


class yieldListFactory
{
    private $modelName = 'Cookbook\Domain\Recipe\RecipeModel';
    protected $model;
    private $id;

    public function __construct($resolver, $id)
    {
        $this->id = $id;
        $this->model = $resolver->resolve($this->modelName);
    }

    public function yieldPacker()
    {
        return new yieldListPacker($this->model->getRecipeYields($this->id));
    }
}