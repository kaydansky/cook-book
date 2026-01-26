<?php
/**
 * @author : AlexK
 * Date: 18-Nov-18
 * Time: 7:41 PM
 */

namespace Cookbook\Domain\Ingredient;

use Cookbook\Output\OutputBuilder;

class IngredientListPacker implements IngredientPacker
{
    private $templateIngredients = 'Recipe/View/recipe_ingredients.html';
    private $ingredientItems;
    private $disabled;

    public $ingredients = [];
    public $recipes = [];

    public function __construct(array $ingredientItems = null, $disabled = false, $service = false, $print = false)
    {
        $this->disabled = $disabled;

        if ($ingredientItems) {
            $this->ingredientItems = $ingredientItems;

            foreach ($this->ingredientItems as $key => $value) {
                if (empty($this->ingredientItems[$key]['uuid'])) {
                    continue;
                }

                if ($value['ingredient_id']) {
                    $this->ingredients[$this->ingredientItems[$key]['uuid']] = $this->ingredientItems[$key];
                } elseif ($value['ingredient_recipe_id']) {
                    $this->recipes[$this->ingredientItems[$key]['uuid']] = $this->ingredientItems[$key];
                }
            }
        }

        if ($service) {
            $this->templateIngredients = 'Recipe/View/service_recipe_ingredients.html';
        }

        if ($print) {
            $this->templateIngredients = 'Recipe/Print/print_recipe_ingredients.html';
        }
    }

    public function pack()
    {
        if (! $this->ingredientItems) {
            return false;
        }

        $builder = new OutputBuilder();
        $listIngredients = '';
        $ingrCount = 0;
        $frameHolder = [
            'ingredient_id' => null,
            'ingredient' => null,
            'IngDesc' => null,
            'ingredient_recipe_id' => null,
            'recipe_title' => null,
            'RecDesc' => null
        ];

        foreach ($this->ingredientItems as $value) {
            $a = array_replace($frameHolder, $value);

            $ingredient = $this->packIngredient(
                $a['ingredient_id'],
                $a['ingredient'],
                $a['IngDesc'],
                $a['ingredient_recipe_id'],
                $a['recipe_title'],
                $a['RecDesc'],
                $a['quantity'],
                $a['unit']);

            if (! $ingredient) {
                continue;
            }

            if ($value['quantity']) {
                $quantity = $value['quantity'];
                $fieldType = 'number';
                $fieldClass = 'ingr-quantity ';
            } else {
                $quantity = 'QS';
                $fieldType = 'text';
                $fieldClass = '';
            }

            $listIngredients .= $builder
                ->setTemplate($this->templateIngredients)
                ->addBrackets([
                    'QUANTITY_VALUE' => $quantity,
                    'FIELD_TYPE' => $fieldType,
                    'FIELD_CLASS' => $fieldClass,
                    'UNIT' => $value['unit'],
                    'INGREDIENT_ITEM' => $ingredient,
                    'COMMENT' => $value['comment'],
                    'INGR_COUNT' => $ingrCount++,
                    'YIELD_CODE' => $value['mimecode'],
                    'MEASUREMENT_SYSTEM' => $value['measurement_system'],
                    'CONVERSION' => $value['conversion'],
                    'CONVERSION_UNIT' => $value['conversion_unit'],
                    'YIELD_TYPE' => $value['type'],
                    'DISABLED' => $this->disabled ? 'disabled' : '',
                    'INGREDIENT_ID' => $value['recipe_ingredient_id']
                ])
                ->build()
                ->result;
        }

        return $listIngredients;
    }

    public function ingredients()
    {
        return $this->ingredients;
    }

    public function recipes()
    {
        return $this->recipes;
    }

    private function packIngredient(
        $ingId = null,
        $ingName = null,
        $ingDesc = null,
        $recId = null,
        $recName = null,
        $recDesc = null,
        $quantity = null,
        $unit = null)
    {
        if ($ingId) {
            $ingredient = '<a id="'
                . $ingId
                .'" href="/ingredient/'
                . $ingId
                . '" data-toggle="popover" data-content="'
                . (! empty($ingDesc) ? $ingDesc : 'No Description')
                . '" title="Ingrdient ' . $ingName . '" target="_blank">'
                . $ingName . '</a>';
        } elseif ($recId) {
            $ingredient = '<a id="'
                . $recId
                .'" href="/recipe/'
                . $recId
                . '" data-toggle="popover" data-content="'
                . (! empty($recDesc) ? $recDesc : 'No Description')
                . '" title="Recipe ' . $recName . '" class="text-success" target="_blank">'
                . $recName . '</a>';
        } else {
            $ingredient = false;
        }

        return $ingredient;
    }
}