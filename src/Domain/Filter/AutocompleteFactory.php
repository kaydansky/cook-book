<?php
/**
 * @author : AlexK
 * Date: 24-Nov-18
 * Time: 6:34 PM
 */

namespace Cookbook\Domain\Filter;

use Cookbook\{Helpers\Sanitizer, Domain\Categories\CategoryListFactory};

class AutocompleteFactory implements JsonFactory
{
    private $product;
    private $model;
    private $term;
    private $is_ingredient;
    private $whole_word;
    private $id;
    private $value;

    public function __construct($product, $request, $model)
    {
        $this->product = $product;
        $this->model = $model;

        $this->term = Sanitizer::sanitize(filter_input(INPUT_GET, 'term', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        $this->is_ingredient = Sanitizer::sanitize(filter_input(INPUT_GET, 'is_ingredient', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        $this->whole_word = Sanitizer::sanitize(filter_input(INPUT_GET, 'whole_word', FILTER_SANITIZE_FULL_SPECIAL_CHARS));

        switch ($product) {
            case 'recipe':
                $this->id = 'recipe_id';
                $this->value = 'recipe_title';
                break;
            case 'dish':
                $this->id = 'dish_id';
                $this->value = 'dish_title';
                break;
            case 'ingredient':
                $this->id = 'ingredient_id';
                $this->value = 'ingredient';
                break;
            case 'catalog':
                $this->id = 'id';
                $this->value = 'title';
                break;
        }

        switch ($request) {
            case 'src': $this->search()->output(); break;
            case 'ing': $this->isIngredient()->output(); break;
            case 'ac_ing_rec': $this->recipeIngredient()->output(); break;
            case 'ac_rec': $this->recipe()->output(); break;
            case 'cat': $this->category(); break;
            case 'source': $this->source()->output(); break;
            case 'author': $this->author()->output(); break;
            case 'ac_ingredient': $this->ingredient()->output(); break;
            default: return null;
        }
    }

    public function search()
    {
        return new AutocompleteOutput($this->id, $this->value, $this->model->getAutoComplete($this->term, $this->whole_word));
    }

    public function category()
    {
        $categoryFactory = new CategoryListFactory(null, null, $this->term);
        die($categoryFactory->autocompleteOptionsPacker()->pack());
    }

    public function isIngredient()
    {
        return new AutocompleteOutput($this->id, $this->value, $this->model->getAutoCompleteIsIngredient($this->term, $this->is_ingredient, $this->whole_word));
    }

    public function recipeIngredient()
    {
        $dataIngredient = $this->model->getAutoCompleteIngredients($this->term);
        $dataRecipe = $this->model->getAutoComplete($this->term);

        return new AutocompleteOutput($this->id, $this->value, null, $dataIngredient, $dataRecipe);
    }

    public function ingredient()
    {
        return new AutocompleteOutput(null, null, null, null, null, $this->model->getAutoComplete($this->term));
    }

    public function recipe()
    {
        return new AutocompleteOutput($this->id, $this->value, null, null, $this->model->getAutoComplete($this->term, $this->whole_word));
    }

    public function source()
    {
        return new AutocompleteOutput(null, null, null, null, null, $this->model->getAutoCompleteSource($this->term));
    }

    public function author()
    {
        return new AutocompleteOutput(null, null, null, null, null, $this->model->getAutoCompleteUser($this->term));
    }
}