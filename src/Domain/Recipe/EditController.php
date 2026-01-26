<?php

namespace Cookbook\Domain\Recipe;

use Cookbook\{Domain\Ingredient\IngredientListFactory,
    Helpers\ListOptions,
    Domain\Categories\CategoryListFactory,
    Domain\Image\ImageFactory};

/**
 * Description of EditController
 *
 * @author AlexK
 */
class EditController extends RecipeController
{
    public $recipeTitle;
    
    protected $model;
    protected $builder;
    protected $path;

    private $templateEditSteps =
        [
            1 => 'Recipe/recipe_edit.html',
            2 => 'Recipe/recipe_edit_step_2.html',
            3 => 'Recipe/recipe_edit_step_3.html',
            4 => 'Recipe/recipe_edit_step_4.html',
        ];
    private $templateIngredient = 'Recipe/recipe_edit_ingredient.html';
    private $templateStep = 'Recipe/recipe_edit_instruction.html';
    private $templateYield = 'Recipe/recipe_edit_yield.html';
    private $templateEquipment = 'Recipe/recipe_edit_equipment.html';
    private $id;
    private $arrayIngredientsRecipes = [];

    public function __set($name, $value)
    {
        $this->$name = $value;
    }
    
    public function editRecipe()
    {
        $generalInfo = $this->model->getRecipe($this->id);
        $ingredientFactory = new IngredientListFactory($this->id);
        $ingredientFactory->recipeIngredientPacker(false, false)->pack();
        $ingredients = $ingredientFactory->ingredients();
        $recipes = $ingredientFactory->recipes();
        $steps = $this->model->getRecipeSteps($this->id);
        $yields = $this->model->getRecipeYields($this->id);
        $equipment = $this->model->getRecipeEquipment($this->id);
        $catList =  (new CategoryListFactory('recipe', $this->id))->autocompleteListPacker()->pack();
        $imgList = (new ImageFactory('recipe', $generalInfo['image_filenames']))->packEditImage();
        $stepImgList = (new ImageFactory('recipe', $generalInfo['step_image_filenames']))->packEditImage();
        $this->recipeTitle = $generalInfo['recipe_title'];
        $ingredients = array_merge($ingredients, $recipes) ;

        $ingList = '';
        $stepList = '';
        $yieldList = '';
        $eqList = '';

        if ($ingredients) {
            $count = 0;
            $arrayIngredients = [];
            $arrayRecipes = [];

            foreach ($ingredients as $key => $value) {
                if ($value['ingredient_id']) {
                    $arrayIngredients[$value['uuid']] =
                        [$value['ingredient_id'] => $value['ingredient']
                            . ' &bull; <span class="show-qty">' . $value['quantity']
                            . '</span> <span class="show-unit">' . $value['unit'] . '</span>'];
                } elseif ($value['ingredient_recipe_id']) {
                    $arrayRecipes[$value['uuid']] =
                        [$value['ingredient_recipe_id'] => 'RECIPE: ' . $value['recipe_title']
                            . ' &bull; <span class="show-qty">' . $value['quantity']
                            . '</span> <span class="show-unit">'. $value['unit'] . '</span>'];
                }

                $ingList .= $this->builder
                    ->setTemplate($this->templateIngredient)
                    ->addBrackets([
                        'INGREDIENT_ID' => $value['ingredient_id'] ?: 'r' . $value['ingredient_recipe_id'],
                        'INGREDIENT' => $value['ingredient'] ?: 'RECIPE: ' . $value['recipe_title'],
                        'ING_QUANTITY' => $value['quantity'],
                        'UNITS_OPTIONS' => ListOptions::unit([$value['unit_id']]),
                        'ING_COMMENT' => $value['comment'],
                        'UUID' => $value['uuid']
                    ])
                    ->build()
                    ->result;

                $count++;
            }

            $a = $arrayIngredients + $arrayRecipes;
            ksort($a);
            $this->arrayIngredientsRecipes = $a;
        }

        if ($steps) {
            $count = 0;
            
            foreach ($steps as $value) {
                $image = '';
                $ingredient = $this->getStepIngredients($value['recipe_step_id'], ($value['ingredient_array'] ? explode(',', $value['ingredient_array']) : null));

                $stepList .= $this->builder
                    ->setTemplate($this->templateStep)
                    ->addBrackets([
                        'STEP_ID' => $value['recipe_step_id'],
                        'IMAGE' => $image,
                        'INGREDIENT_OPTIONS' => $ingredient,
                        'INGREDIENT_ARRAY' => $value['ingredient_array'],
                        'STEP_IMAGES_CURRENT' => $value['step_images'],
                        'STEP_CONTENT' => $value['step_content']
                    ])
                    ->build()
                    ->result;

                $count++;
            }
        }

        if ($yields) {
            foreach ($yields as $value) {
                $yieldList .= $this->builder
                    ->setTemplate($this->templateYield)
                    ->addBrackets([
                        'TYPE_OPTIONS' => ListOptions::yield([$value['unitType']]),
                        'QUANTITY' => $value['quantity'],
                        'UNITS_OPTIONS' => ListOptions::unit([$value['unit_id']], $value['unitType']),
                    ])
                    ->build()
                    ->result;
            }
        }
        
        if ($equipment) {
            foreach ($equipment as $value) {
                $eqList .= $this->builder
                    ->setTemplate($this->templateEquipment)
                    ->addBrackets([
                        'EQUIPMENT_OPTIONS' => ListOptions::equipment([$value['equipment_id']]),
                        'QUANTITY' => $value['quantity'],
                        'COMMENT' => $value['comment'],
                    ])
                    ->build()
                    ->result;
            }
        }

        if (isset($this->templateEditSteps[($this->path[5])])) {
            $template = $this->templateEditSteps[$this->path[5]];
        } else {
            $template = $this->templateEditSteps[1];
        }
        
        return $this->builder
            ->setTemplate($template)
            ->addBrackets([
                'YIELD_UNITS_OPTIONS' => ListOptions::unit([$generalInfo['unit_id']]),
                'NEW_EQUIPMENT_OPTIONS' => ListOptions::equipment(),
                'RECIPE_ID' => $this->id,
                'IMAGE_FILENAMES' => $generalInfo['image_filenames'],
                'STEP_IMAGE_FILENAMES' => $generalInfo['step_image_filenames'],
                'RECIPE_TITLE' => $generalInfo['recipe_title'],
                'DESCRIPTION' => $generalInfo['description'],
                'SOURCE' => $generalInfo['source'],
                'SOURCE_LINK' => $generalInfo['source_link'],
                'ALT_SEARCH_TERMS' => $generalInfo['alt_search_terms'],
                'NOTES' => $generalInfo['notes'],
                'SELECTED_' . str_replace(' ', '', $generalInfo['yield']) => ' selected',
                'YIELD_VALUE' => $generalInfo['yield_value'],
                'prepare_hours' => $generalInfo['prepare_hours'],
                'prepare_min' => $generalInfo['prepare_min'],
                'cook_hours' => $generalInfo['cook_hours'],
                'cook_min' => $generalInfo['cook_min'],
                'CATEGORIES' => $catList,
                'IMAGES' => $imgList,
                'STEP_IMAGES' => $stepImgList,
                'INGREDIENTS' => $ingList,
                'STEPS' => $stepList,
                'EQUIPMENT' => $eqList,
                'YIELDS' => $yieldList,
                'APPROVED_CHECKED' => $generalInfo['approved'] != 0 ? ' checked' : '',
                'APPROVED_HIDDEN' => $generalInfo['approved'] != 0 ? 'on' : '',
                'NEW_UNITS_OPTIONS' => ListOptions::unit(),
                'INGREDIENT_OPTIONS_CLEAR' => $this->getStepIngredients('NewStep', null),
            ])
            ->build()
            ->result;
    }

    private function getStepIngredients($stepId, array $a = null)
    {
        if (! count($this->arrayIngredientsRecipes)) {
            return false;
        }

        $a = $a ?: [];

        if (count($a)) {
            foreach ($a as $key => $value) {
                $a[$key] = trim($value, 'r');
            }
        }
        
        $ingredient = '';
//var_dump($this->arrayIngredientsRecipes);
//var_dump($a);
        foreach ($this->arrayIngredientsRecipes as $key => $value) {
            $id = key($value);
            $title = $value[$id];
            $selected = in_array($key, $a) ? ' checked' : '';

            $ingredient .= '<div class="custom-control custom-switch step_ingredient_'
                . $key . '"><input type="checkbox" class="step-ingredient custom-control-input" uuid="'
                . $key . '" id="'
                . $key . '-' . $stepId .'" value="' . $id . '"' . $selected . '>'
                . '<label class="custom-control-label" uuid="'
                . $key . '" for="'
                . $key . '-' . $stepId .'">' . $title . '</label></div>';
        }
        
        return $ingredient;
    }
}