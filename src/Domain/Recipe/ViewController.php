<?php

namespace Cookbook\Domain\Recipe;

use Cookbook\{Domain\Generalinfo\GeneralInfoFactory,
    Domain\Categories\CategoryListFactory,
    Domain\Image\ImageFactory,
    Domain\Ingredient\IngredientListFactory,
    Domain\Dietary\DietaryFactory,
    Helpers\Format};

use Delight\Auth\Role;

/**
 * Description of ViewController
 *
 * @author AlexK
 */
class ViewController extends RecipeController
{
    protected $model;
    protected $builder;
    protected $resolver;
    protected $auth;

    private $id;
    private $templateConverter = 'Common/converter.html';
    private $templateView = 'Recipe/View/recipe_view.html';
    private $serviceTemplateView = 'Recipe/View/service_recipe_view.html';
    private $templateEquipment = 'Recipe/View/recipe_equipment.html';
    private $templateEquipmentContainer = 'Recipe/View/equipment_container.html';
    private $templatePrint = 'Recipe/Print/print_recipe.html';
    private $serviceTemplatePrint = 'Recipe/Print/service_print_recipe.html';
    private $templatePrintPresentationImages = 'Recipe/Print/presentation_images.html';
    private $templatePrintStepImages = 'Recipe/Print/step_images.html';
    private $templatePrintIngredients = 'Recipe/Print/recipe_ingredients_container.html';
    private $templatePrintEquipmentContainer = 'Recipe/Print/recipe_equipment_container.html';
    private $templatePrintInstructions = 'Recipe/Print/recipe_instructions_container.html';
    private $modelUser = 'Cookbook\Domain\Users\UsersModel';

    public function __set($name, $value)
    {
        $this->$name = $value;
    }
    
    public function bindRecipe()
    {
        $print = filter_input(INPUT_GET, 'print', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $presentation_img = filter_input(INPUT_GET, 'presentation_img', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $step_img = filter_input(INPUT_GET, 'step_img', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

        $generalInfo = $this->model->getRecipe($this->id);
        
        if (! $generalInfo) {
            return null;
        }

        $this->recipe_title = $generalInfo['recipe_title'];
        $this->modelUser = $this->resolver->resolve($this->modelUser);
        $imageFactoryPresentation = new ImageFactory('recipe', $generalInfo['image_filenames']);
        $imageFactoryStepImages = new ImageFactory('recipe', $generalInfo['step_image_filenames']);
        $carousel = $imageFactoryPresentation->packCarousel();
        $stepsImages = $imageFactoryStepImages->packStepImages($this->id);
        $catList = (new CategoryListFactory('recipe', $this->id))->commaListPacker()->pack();
        $restrictionLinks = (new DietaryFactory($this->id, 'recipe'))->recipeListPacker()->pack();
        $ingredientFactory = new IngredientListFactory($this->id);
        $listIngredients = $this->auth->hasAnyRole(Role::CHEF, Role::COOK)
            ? $ingredientFactory->recipeIngredientPacker(false, $print)->pack()
            : $ingredientFactory->serviceIngredientPacker()->pack();
//        var_dump($ingredientFactory->ingredients());
//        var_dump($ingredientFactory->recipes());
        $listSteps = (new instructionListFactory($this->resolver, $this->id, $ingredientFactory->ingredients(), $ingredientFactory->recipes(), $generalInfo['image_filenames']))->instructionPacker()->pack();
        $yieldFactory = (new yieldListFactory($this->resolver, $this->id))->yieldPacker();
        $yield = $yieldFactory->pack();
        $listEquipment = $this->equipment($print);
        $converter = $this->builder->setTemplate($this->templateConverter)->build()->result;
        $general = new GeneralInfoFactory($this->builder, $this->resolver, $generalInfo, $catList, $restrictionLinks, 'recipe', $print);

        if ($print) {
            $presentationImages = $presentation_img ? $imageFactoryPresentation->listImages($this->templatePrintPresentationImages) : '';
            $stepsImages = $step_img ? $imageFactoryStepImages->listImages($this->templatePrintStepImages) : '';
            $yield = $yieldFactory->packPrint();
            $ingredients = $this->ingredients($listIngredients);
            $instructions = $this->instructions($listSteps);

            echo $this->builder
                ->setTemplate($this->auth->hasAnyRole(Role::CHEF, Role::COOK) ? $this->templatePrint : $this->serviceTemplatePrint)
                ->addBrackets([
                    'RECIPE_ID' => $generalInfo['recipe_id'],
                    'RECIPE_TITLE' => $generalInfo['recipe_title'],
                    'META_TITLE' => $generalInfo['recipe_title'],
                    'ALT_SEARCH_TERM' => $generalInfo['alt_search_terms'],
                    'DESCRIPTION' => $general->description,
                    'PRESENTATION_IMAGES' => $presentationImages,
                    'PREPARATION_TIMES' => $general->times,
                    'SOURCE' => $general->source,
                    'AUTHOR' => $general->author,
                    'APPROVED' => $general->approved,
                    'NOTES' => $general->data['notes'] ?: '--',
                    'DATE_ADDED' => $general->dateAdded,
                    'DATE_MODIFIED' => $general->dateModified,
                    'YIELD' => $yield,
                    'CATEGORIES' => $general->categories,
                    'DIETARY_RESTRICTIONS' => $general->restrictions,
                    'INGREDIENTS' => $ingredients,
                    'EQUIPMENT' => $listEquipment,
                    'INSTRUCTIONS' => $instructions,
                    'STEPS_IMAGES' => $stepsImages
                ])
                ->build()
                ->result;

            exit;
        }

        return $this->builder
            ->setTemplate($this->auth->hasAnyRole(Role::CHEF, Role::COOK) ? $this->templateView : $this->serviceTemplateView)
            ->addBrackets([
                'RECIPE_ID' => $generalInfo['recipe_id'],
                'RECIPE_TITLE' => $general->titleRecipe,
                'DESCRIPTION' => $general->description,
                'CAROUSEL' => $carousel ? '<div class="col-lg-9 mb-3">' . $carousel . '</div>' : '',
                'SOURCE' => $general->source,
                'NOTES' => $general->notes,
                'TIME' => $general->times,
                'DATE_ADDED' => $general->dateAdded,
                'DATE_MODIFIED' => $general->dateModified,
                'LIST_INGREDIENTS' => $listIngredients ?: '<span class="text-muted">No Ingredients</span>',
                'LIST_STEPS' => $listSteps,
                'STEPS_IMAGES' => $stepsImages,
                'LIST_EQUIPMENT' => $listEquipment,
                'AUTHOR' => $general->author,
                'APPROVED' => $general->approved,
                'YIELD' => $yield,
                'CONVERTER' => $converter,
                'CATEGORIES' => $general->categories,
                'RESTRICTIONS' => $general->restrictions,
                'BUTTON_EDIT' => $this->auth->hasRole(Role::CHEF) ? '<a href="/recipe/manage/edit/' . $generalInfo['recipe_id'] . '/"><button class="btn btn-primary mb-5" type="button">Edit</button></a>' : ''
            ])
            ->build()
            ->result;
    }

    private function equipment($print)
    {
        $equipment = $this->model->getRecipeEquipment($this->id);

        if (! $equipment) {
            return false;
        }

        $listEquipment = '';

        foreach ($equipment as $value) {
            $listEquipment .= $this->builder
                ->setTemplate($this->templateEquipment)
                ->addBrackets([
                    'QUANTITY' => $value['quantity'] ?: 'QS',
                    'EQUIPMENT_ID' => $value['equipment_id'],
                    'EQUIPMENT' => $value['equipment'],
                    'DESCRIPTION_EQUIPMENT' => ! empty($value['description']) ? $value['description'] : 'No Description',
                    'COMMENT' => $value['comment']
                ])
                ->build()
                ->result;
        }

        return $this->builder
            ->setTemplate($print ? $this->templatePrintEquipmentContainer : $this->templateEquipmentContainer)
            ->addBrackets(['LIST_EQUIPMENTS' => $listEquipment])
            ->build()
            ->result;
    }

    private function ingredients($listIngredients)
    {
        if (! $listIngredients) {
            return false;
        }

        return $this->builder
            ->setTemplate($this->templatePrintIngredients)
            ->addBrackets(['LIST_INGREDIENTS' => $listIngredients])
            ->build()
            ->result;
    }

    private function instructions($listSteps)
    {
        if (! $listSteps) {
            return false;
        }

        return $this->builder
            ->setTemplate($this->templatePrintInstructions)
            ->addBrackets(['LIST_INSTRUCTIONS' => $listSteps])
            ->build()
            ->result;
    }
}