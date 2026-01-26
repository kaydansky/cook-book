<?php
/**
 * @author: AlexK
 * Date: 01-May-19
 * Time: 2:19 PM
 */

namespace Cookbook\Domain\Recipe;

use Cookbook\Output\OutputBuilder;

class instructionListPacker implements instructionPacker
{
    private $templateSteps = 'Recipe/View/recipe_steps.html';
    private $templateStepsService = 'Recipe/View/recipe_steps_service.html';
    private $templateIngredientsContainer = 'Recipe/View/ingredients_container.html';
    private $templateDash = 'Common/dash.html';
    private $placeholder;
    private $steps;
    private $ingredients;
    private $recipes;

    public function __construct($steps = [], $ingredients = [], $recipes = [])
    {
        $this->steps = $steps ?: [];
        $this->ingredients = $ingredients ?: [];
        $this->recipes = $recipes ?: [];
    }

    public function pack()
    {
        if (! $this->steps) {
            return '<span class="text-muted">No Instructions</span>';
        }

        $builder = new OutputBuilder();
        $dash = $builder->setTemplate($this->templateDash)->build();
        $this->placeholder = $dash->result;

        $listSteps = '';
        $count = 0;

        foreach ($this->steps as $value) {
            $count++;
            $ingredient = $this->instructionIngredients($builder, $value['ingredient_array']);

            $listSteps .= $builder
                ->setTemplate($this->templateSteps)
                ->addBrackets([
                    'INGREDIENT_ITEM' => $ingredient,
                    'STEP_CONTENT' => ! empty($value['step_content']) ? $value['step_content'] : $this->placeholder,
                    'COUNT' => $count,
                    'STEP_IMAGES' => $value['step_images'],
                    'STEP_ID' => $value['recipe_step_id']
                ])
                ->build()
                ->result;
        }

        return $listSteps;
    }

    public function packService()
    {
        if (! $this->steps) {
            return '<span class="text-muted">No Instructions</span>';
        }

        $builder = new OutputBuilder();

        $listSteps = '';
        $count = 0;

        foreach ($this->steps as $value) {
            $count++;

            $listSteps .= $builder
                ->setTemplate($this->templateStepsService)
                ->addBrackets([
                    'STEP_CONTENT' => ! empty($value['step_content']) ? $value['step_content'] : $this->placeholder,
                    'COUNT' => $count
                ])
                ->build()
                ->result;
        }

        return substr($listSteps, 0, -13);
    }

    private function instructionIngredients($builder, $a = null)
    {
        if (! $a) {
            return false;
        }

        $ar = explode(',', $a);
        $ingredient = '';

        foreach ($ar as $value) {
            $value = trim($value, 'r');

            if (isset($this->ingredients[$value])) {
                $ingredient .= '<h6 class="m-2"><a href="/ingredient/'
                    . $this->ingredients[$value]['recipe_ingredient_id']
                    . '" title="Ingrdient" target="_blank">'
                    . $this->ingredients[$value]['ingredient']
                    . '</a> &bull; <span id-qty="'
                    . $this->ingredients[$value]['recipe_ingredient_id'] . '">'
                    . $this->ingredients[$value]['quantity'] . '</span> <span id-unit="'
                    . $this->ingredients[$value]['recipe_ingredient_id'] . '">'
                    . $this->ingredients[$value]['unit'] . '</span></h6>';
            } elseif (isset($this->recipes[$value])) {
                $ingredient .= '<h6 class="m-2"><a href="/recipe/'
                    . $this->recipes[$value]['ingredient_recipe_id']
                    . '" title="Recipe" class="text-success" target="_blank">'
                    . $this->recipes[$value]['recipe_title']
                    . '</a> &bull; <span id-qty="'
                    . $this->recipes[$value]['recipe_ingredient_id'] . '" id-qty="'
                    . $this->recipes[$value]['recipe_ingredient_id'] . '">'
                    . $this->recipes[$value]['quantity'] . '</span> <span id-unit="'
                    . $this->recipes[$value]['recipe_ingredient_id'] . '">'
                    . $this->recipes[$value]['unit'] . '</span></h6>';
            }
        }

        if ($ingredient) {
            return $builder
                ->setTemplate($this->templateIngredientsContainer)
                ->addBrackets([
                    'INGREDIENT' => $ingredient
                ])
                ->build()
                ->result;
        }

        return false;
    }
}