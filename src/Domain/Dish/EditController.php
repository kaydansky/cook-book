<?php

namespace Cookbook\Domain\Dish;

use Cookbook\{
    Helpers\ListOptions,
    Domain\Categories\CategoryListFactory,
    Domain\Image\ImageFactory
};

/**
 * Description of EditController
 *
 * @author AlexK
 */
class EditController extends DishController
{
    public $dishTitle;
    
    protected $model;
    protected $builder;
    protected $path;

    private $templateEditSteps =
        [
            1 => 'Dish/dish_edit.html',
            2 => 'Dish/dish_edit_step_2.html',
            3 => 'Dish/dish_edit_step_3.html',
            4 => 'Dish/dish_edit_step_4.html',
        ];
    private $id;
    private $templateAlternative = 'Dish/dish_edit_alternative.html';
    private $templateImage = 'Image/edit_image.html';
    private $templateIngredient = 'Dish/dish_edit_ingredient.html';
    private $templateRecipes = 'Dish/dish_edit_recipes.html';
    private $templateStep = 'Dish/dish_edit_instruction.html';

    public function __set($name, $value)
    {
        $this->$name = $value;
    }
    
    public function editDish()
    {
        $generalInfo = $this->model->getDish($this->id);
        $this->dishTitle = $generalInfo['dish_title'];
        $alternatives = $this->model->getAlternatives($this->id);
        $ingredients = $this->model->getDishIngredients($this->id);
        $steps = $this->model->getDishSteps($this->id);
        $recipes = $this->model->getDishRecipes($this->id);

        $categoryFactory = new CategoryListFactory('dish', $this->id);
        $catList = $categoryFactory->autocompleteListPacker()->pack();

        $imageFactory = new ImageFactory('dish', $generalInfo['image_filenames']);
        $imgList = $imageFactory->packEditImage();

        $alternativeList = '';
        $stepList = '';
        $ingList = '';
        $recipeList = '';

        if ($alternatives) {
            foreach ($alternatives as $value) {
                $al = $this->builder
                        ->setTemplate($this->templateAlternative)
                        ->addBrackets([
                            'DATE' => substr($value['alternative_date'], 0, 10),
                            'alternative_title' => $value['alternative_title'],
                            'alternative_subtitle' => $value['alternative_subtitle'],
                            'marking' => $value['marking'],
                            'wine_pairing' => $value['wine_pairing'],
                            'china_name' => $value['china_name'],
                            'china_id' => $value['china_id'],
                        ])
                        ->build();

                $alternativeList .= $al->result;
            }
        }

        if ($steps) {
            foreach ($steps as $value) {
                $image = '';

                if ($value['step_image']) {
                    $img = $this->builder
                        ->setTemplate($this->templateImage)
                        ->addBrackets([
                            'IMG_FILENAME' => $value['step_image'],
                            'PRODUCT' => 'dish'
                        ])
                        ->build();

                    $image = $img->result;
                }

                $step = $this->builder
                    ->setTemplate($this->templateStep)
                    ->addBrackets([
                        'IMAGE' => $image,
                        'STEP_IMAGE_CURRENT' => $value['step_image'],
                        'step_content' => $value['step_content'],
                    ])
                    ->build();

                $stepList .= $step->result;
            }
        }
        
        if ($ingredients) {
            foreach ($ingredients as $value) {
                $ing = $this->builder
                        ->setTemplate($this->templateIngredient)
                        ->addBrackets([
                            'INGREDIENT_ID' => $value['ingredient_id'] ? $value['ingredient_id'] : 'r' . $value['ingredient_recipe_id'],
                            'INGREDIENT' => $value['ingredient'] ?? 'RECIPE: ' . $value['recipe_title'],
                            'ING_QUANTITY' => $value['quantity'],
                            'UNITS_OPTIONS' => ListOptions::unit([$value['unit_id']]),
                            'ING_COMMENT' => $value['comment'],
                        ])
                        ->build();
                
                $ingList .= $ing->result;
            }
        }

        if ($recipes) {
            foreach ($recipes as $value) {
                $rec = $this->builder
                    ->setTemplate($this->templateRecipes)
                    ->addBrackets([
                        'recipe_title' => $value['recipe_title'],
                        'recipe_id' => $value['recipe_id'],
                        'recipe_option' => $value['recipe_option']
                    ])
                    ->build();

                $recipeList .= $rec->result;
            }
        }

        if (isset($this->templateEditSteps[($this->path[5])])) {
            $template = $this->templateEditSteps[$this->path[5]];
        } else {
            $template = $this->templateEditSteps[1];
        }
        
        $content = $this->builder
                ->setTemplate($template)
                ->addBrackets([
                    'NEW_UNITS_OPTIONS' => ListOptions::unit(),
                    'DISH_ID' => $this->id,
                    'IMAGE_FILENAMES' => $generalInfo['image_filenames'],
                    'DISH_TITLE' => $generalInfo['dish_title'],
                    'DISH_SUBTITLE' => $generalInfo['dish_subtitle'],
                    'DESCRIPTION' => $generalInfo['description'],
                    'SOURCE' => $generalInfo['source'],
                    'SOURCE_LINK' => $generalInfo['source_link'],
                    'NOTES' => $generalInfo['notes'],
                    'FOH_KITCHEN_ASSEMBLY' => $generalInfo['foh_kitchen_assembly'],
                    'FOH_DINING_ASSEMBLY' => $generalInfo['foh_dining_assembly'],
                    'FOH_PURVEYORS' => $generalInfo['foh_purveyors'],
                    'CATEGORIES' => $catList,
                    'ALTERNATIVES' => $alternativeList,
                    'IMAGES' => $imgList,
                    'INGREDIENTS' => $ingList,
                    'STEPS' => $stepList,
                    'RECIPES' => $recipeList,
                    'APPROVED_CHECKED' => $generalInfo['approved'] != 0 ? ' checked' : '',
                    'APPROVED_HIDDEN' => $generalInfo['approved'] != 0 ? 'on' : '',
                    'THIS_MARKING' => $generalInfo['marking'],
                    'THIS_WINE_PAIRING' => $generalInfo['wine_pairing'],
                    'THIS_CHINA' => $generalInfo['china_name'],
                    'THIS_CHINA_ID' => $generalInfo['china_id'],
                    'DISH_DATE' => $generalInfo['dish_date'],
                ])
                ->build();
        
        return $content->result;
    }
}