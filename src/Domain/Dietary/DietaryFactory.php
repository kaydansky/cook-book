<?php
/**
 * @author: AlexK
 * Date: 27-Nov-18
 * Time: 1:54 PM
 */

namespace Cookbook\Domain\Dietary;


class DietaryFactory implements DietaryPackerFactory
{

    private $restrictionsIngredient;
    private $restrictions;

    public function __construct($id = null, $product = null)
    {
        $model = new DietaryModel;

        if ($product) {
            switch ($product) {
                case 'recipe':
                    $this->restrictionsIngredient = $model->getRecipeRestrictions($id);
                    break;
                case 'dish':
                    $this->restrictionsIngredient = $model->getDishRestrictions($id);
                    break;
            }
        } else {
            $this->restrictionsIngredient = $model->getIngredientRestrictions($id);
            $this->restrictions = $model->getRestrictions();
        }
    }

    public function commaListPacker()
    {
        return new DietaryPackerList($this->restrictionsIngredient);
    }

    public function autocompleteInputPacker()
    {
        return new DietaryPackerAutocomplete($this, $this->restrictionsIngredient);
    }

    public function optionsPacker(int $id = null)
    {
        return new DietaryPackerOptions($this->restrictions, $id);
    }

    public function recipeListPacker()
    {
        return new DietaryRecipeListPacker($this->restrictionsIngredient);
    }

    public function dishListPacker()
    {
        return new DietaryDishListPacker($this->restrictionsIngredient);
    }

}