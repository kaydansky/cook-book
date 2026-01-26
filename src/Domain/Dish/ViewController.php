<?php

namespace Cookbook\Domain\Dish;

use Cookbook\{Domain\Generalinfo\GeneralInfoFactory,
    Domain\Recipe\instructionListFactory,
    Domain\Recipe\yieldListFactory,
    Helpers\Format,
    Domain\Categories\CategoryListFactory,
    Domain\Image\ImageFactory,
    Domain\Ingredient\IngredientListFactory,
    Domain\Dietary\DietaryFactory};

use Delight\Auth\Role;

/**
 * Description of ViewController
 *
 * @author AlexK
 */
class ViewController extends DishController
{
    protected $model;
    protected $builder;
    protected $resolver;
    protected $auth;

    private $id;
    private $dateServed;
    private $templateDash = 'Common/dash.html';
    private $serviceTemplateView = 'Dish/service_dish_view.html';
    private $templateAleternativesCard = 'Dish/View/dish_alternatives_card.html';
    private $templateAleternatives = 'Dish/View/dish_alternatives.html';
    private $templateInstructionsCard = 'Dish/View/dish_instructions_card.html';
    private $templateInstructions = 'Dish/View/dish_instructions.html';
    private $templateRecipesCard = 'Dish/View/dish_recipes_card.html';
    private $templateRecipes = 'Dish/View/dish_recipes.html';
    private $templateIngredientsCard = 'Dish/View/dish_ingredients_card.html';
    private $templateView = 'Dish/View/dish_view.html';
    private $templateDateServed = 'Dish/View/date_served.html';
    private $templateDescription = 'Dish/View/recipe_description.html';
    private $templateRecipeOptions = 'Dish/View/recipe_options.html';
    private $templateRecipeNotes = 'Generalinfo/notes.html';
    private $templatePrint = 'Dish/Print/print_dish.html';
    private $templatePrintServiceDetails = 'Dish/Print/print_dish_service_details.html';
    private $serviceTemplatePrint = 'Dish/Print/service_print_dish.html';
    private $templatePrintPresentationImages = 'Dish/Print/presentation_images.html';
    private $templateRecipesPrint = 'Dish/Print/dish_recipes.html';
    private $templatePrintAleternatives = 'Dish/Print/dish_alternatives_container.html';
    private $templatePrintRecipes = 'Dish/Print/dish_recipes_container.html';
    private $templatePrintIngredients = 'Dish/Print/dish_ingredients_container.html';
    private $templatePrintInstructions = 'Dish/Print/dish_instructions_container.html';
    private $modelUser = 'Cookbook\Domain\Users\UsersModel';
    private $placeholder;
    private $print;

    public function __set($name, $value)
    {
        $this->$name = $value;
    }
    
    public function bindDish()
    {
        $this->print = filter_input(INPUT_GET, 'print', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $presentation_img = filter_input(INPUT_GET, 'presentation_img', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $service_details = filter_input(INPUT_GET, 'service_details', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

        $generalInfo = $this->model->getDish($this->id);

        if (! $generalInfo) {
            return null;
        }

        $dash = $this->builder->setTemplate($this->templateDash)->build();
        $this->placeholder = $dash->result;
        $this->modelUser = $this->resolver->resolve($this->modelUser);
        $imageFactory = new ImageFactory('dish', $generalInfo['image_filenames']);
        $carousel = $imageFactory->packCarousel();
        $catList = (new CategoryListFactory('dish', $this->id))->commaListPacker()->pack();
        $restrictionLinks = (new DietaryFactory($this->id, 'dish'))->dishListPacker()->pack();
        $ingredients = $this->ingredients();
        $alternatives = $this->alternatives();
        $instructions = $this->instructions();
        $recipes = $this->recipes();
        $this->dish_title = $generalInfo['dish_title'];
        $general = new GeneralInfoFactory($this->builder, $this->resolver, $generalInfo, $catList, $restrictionLinks, 'dish', $this->print);

        $dateServed = $this->dateServed
            ? $this->builder->setTemplate($this->templateDateServed)
                ->addBrackets(['DATE' => $this->dateServed])
                ->build()->result
            : '';

        if ($this->print) {
            if ($this->auth->hasAnyRole(Role::CHEF, Role::COOK)) {
                if ($service_details) {
                    $printTemplate = $this->templatePrintServiceDetails;
                } else {
                    $printTemplate = $this->templatePrint;
                }
            } else {
                $printTemplate = $this->serviceTemplatePrint;
            }

            $presentationImages = $presentation_img ? $imageFactory->listImages($this->templatePrintPresentationImages) : '';

            echo $this->builder
                ->setTemplate($printTemplate)
                ->addBrackets([
                    'DISH_ID' => $generalInfo['dish_id'],
                    'DISH_TITLE' => $generalInfo['dish_title'],
                    'DISH_SUBTITLE' => $generalInfo['dish_subtitle'],
                    'DESCRIPTION' => $general->description,
                    'CATEGORIES' => $general->categories,
                    'PRESENTATION_IMAGES' => $presentationImages,
                    'SOURCE' => $general->source,
                    'NOTES' => $general->notes,
                    'AUTHOR' => $general->author,
                    'APPROVED' => $general->approved,
                    'DISH_DATE' => $general->dateDish,
                    'DATE_ADDED' => $general->dateAdded,
                    'DATE_MODIFIED' => $general->dateModified,
                    'THIS_MARKING' => $general->marking,
                    'THIS_WINE_PAIRING' => $general->winePairing,
                    'CHINA' => $general->china,
                    'DIETARY_RESTRICTIONS' => $general->restrictions,
                    'DATE_SERVED' => $dateServed,
                    'FOH_KITCHEN_ASSEMBLY' => $general->kitchenAssembly,
                    'FOH_DINING_ASSEMBLY' => $general->diningAssembly,
                    'FOH_PURVEYORS' => $general->purveyors,
                    'LIST_ALTERNATIVES' => $alternatives,
                    'DISH_INGREDIENTS' => $ingredients,
                    'DISH_ASSEMBLY_STEPS' => $instructions,
                    'LIST_RECIPES' => $recipes
                ])
                ->build()
                ->result;

            exit;
        }

        return $this->builder
            ->setTemplate($this->auth->hasAnyRole(Role::CHEF, Role::COOK) ? $this->templateView : $this->serviceTemplateView)
            ->addBrackets([
                'DISH_ID' => $generalInfo['dish_id'],
                'DISH_TITLE' => $generalInfo['dish_title'],
                'DISH_SUBTITLE' => $generalInfo['dish_subtitle'],
                'DESCRIPTION' => $general->description,
                'CATEGORIES' => $general->categories,
                'CAROUSEL' => $carousel ? '<div class="col-lg-9 mb-3">' . $carousel . '</div>' : '',
                'SOURCE' => $general->source,
                'NOTES' => $general->notes,
                'DISH_DATE' => $general->dateDish,
                'DATE_ADDED' => $general->dateAdded,
                'DATE_MODIFIED' => $general->dateModified,
                'DISH_INGREDIENTS' => $ingredients,
                'AUTHOR' => $general->author,
                'APPROVED' => $general->approved,
                'FOH_KITCHEN_ASSEMBLY' => $general->kitchenAssembly,
                'FOH_DINING_ASSEMBLY' => $general->diningAssembly,
                'FOH_PURVEYORS' => $general->purveyors,
                'LIST_ALTERNATIVES' => $alternatives,
                'DISH_INSTRUCTIONS' => $instructions,
                'LIST_RECIPES' => $recipes,
                'THIS_MARKING' => $general->marking,
                'THIS_WINE_PAIRING' => $general->winePairing,
                'CHINA' => $general->china,
                'DIETARY_RESTRICTIONS' => $general->restrictions,
                'BUTTON_EDIT' => $this->auth->hasRole(Role::CHEF) ? '<a href="/dish/manage/edit/' . $generalInfo['dish_id'] . '/"><button class="btn btn-primary mb-5" type="button">Edit</button></a>' : '',
                'DATE_SERVED' => $dateServed
            ])
            ->build()
            ->result;
    }

    private function alternatives()
    {
        $alternatives = $this->model->getAlternatives($this->id);

        if (! $alternatives) {
            return false;
        }

        $listAlternatives = false;

        foreach ($alternatives as $value) {
            if (empty($value['alternative_title'])) {
                $this->dateServed = Format::dateTime($value['alternative_date']);
                continue;
            }

            $listAlternatives .= $this->builder
                ->setTemplate($this->templateAleternatives)
                ->addBrackets([
                    'alternative_date' => Format::dateTime($value['alternative_date']),
                    'alternative_title' => ! empty($value['alternative_title']) ? $value['alternative_title'] : $this->placeholder,
                    'alternative_subtitle' => ! empty($value['alternative_subtitle']) ? $value['alternative_subtitle'] : $this->placeholder,
                    'marking' => ! empty($value['marking']) ? $value['marking'] : $this->placeholder,
                    'wine_pairing' => ! empty($value['wine_pairing']) ? $value['wine_pairing'] : $this->placeholder,
                    'china' => ! empty($value['china_name'])
                        ? '<a href="/china/'
                        . $value['china_id']
                        . '" target="_blank" '
                        . '" data-toggle="popover" data-content="'
                        . (! empty($value['manufacturer']) ? $value['manufacturer'] : 'No Description')
                        . '" title="Manufacturer" target="_blank">'
                        . $value['china_name'] . '</a>'
                        : $this->placeholder,
                ])
                ->build()
                ->result;
        }

        if ($listAlternatives) {
            return $this->builder
                ->setTemplate($this->print ? $this->templatePrintAleternatives : $this->templateAleternativesCard)
                ->addBrackets(['ROWS_ALTERNATIVES' => $listAlternatives])
                ->build()
                ->result;
        }

        return false;
    }

    private function instructions()
    {
        $steps = $this->model->getDishSteps($this->id);

        if (! $steps) {
            return false;
        }

        $listSteps = '';
        $count = 0;

        foreach ($steps as $value) {
            $count++;

            $listSteps .= $this->builder
                ->setTemplate($this->templateInstructions)
                ->addBrackets([
//                    'STEP_IMAGE' => $value['step_image']
//                        ? '<a title="Enlarge" data-img-id="/images/dish/'
//                        . $value['step_image']
//                        . '.png" data-toggle="modal" data-target="#img-enlarge" href="#">'
//                        . '<img width="100" height="100" class="mr-3 rounded" src="/images/dish/'
//                        . $value['step_image']
//                        . '_tn.png"></a>'
//                        : '',
                    'STEP_IMAGE' => $value['step_image']
                        ? '<a title="View Original" href="/images/dish/'
                        . $value['step_image']
                        . '.jpg" target="_blank">'
                        . '<img width="100" class="mr-3 rounded" src="/images/dish/'
                        . $value['step_image']
                        . '_tn.jpg"></a>'
                        : '',
                    'STEP_CONTENT' => ! empty($value['step_content']) ? $value['step_content'] : $this->placeholder,
                    'COUNT' => $count
                ])
                ->build()
                ->result;
        }

        if ($listSteps) {
            return $this->builder
                ->setTemplate($this->print ? $this->templatePrintInstructions : $this->templateInstructionsCard)
                ->addBrackets(['LIST_INSTRUCTIONS' => substr($listSteps, 0, -13)])
                ->build()
                ->result;
        }
    }

    private function recipes()
    {
        $recipes = $this->model->getDishRecipes($this->id);

        if (! $recipes) {
            return false;
        }

        $listRecipes = '';

        foreach ($recipes as $key => $value) {
            $ingredientFactory = new IngredientListFactory($value['recipe_id']);
            $listIngredients = $ingredientFactory->recipeIngredientPacker(true)->pack();

            $description = $value['description']
                ? $this->builder->setTemplate($this->templateDescription)
                    ->addBrackets(['DESCRIPTION' => $value['description']])
                    ->build()->result
                : '';

            $notes = $value['notes']
                ? $this->builder->setTemplate($this->templateRecipeNotes)
                    ->addBrackets(['NOTE' => $value['notes']])
                    ->build()->result
                : '';

            $recipeOption = $value['recipe_option']
                ? $this->builder->setTemplate($this->templateRecipeOptions)
                    ->addBrackets(['OPTION' => $value['recipe_option']])
                    ->build()->result
                : '';

            $listRecipes .= $this->builder
                ->setTemplate($this->print ? $this->templateRecipesPrint : $this->templateRecipes)
                ->addBrackets([
                    'RECIPE_ID' => $value['recipe_id'],
                    'DESCRIPTION' => $description,
                    'NOTES' => $notes,
                    'OPTIONS' => $recipeOption,
                    'INGREDIENTS' => $listIngredients,
                    'YIELDS' => (new yieldListFactory($this->resolver, $value['recipe_id']))->yieldPacker()->packService(),
                    'STEPS' => (new instructionListFactory($this->resolver, $value['recipe_id'], $ingredientFactory->ingredients(), $ingredientFactory->recipes()))->instructionPacker()->packService(),
                    'RECIPE_TITLE' => ! empty($value['recipe_title']) ? $value['recipe_title'] : $this->placeholder,
                ])
                ->build()
                ->result;

            if ($key !== count($recipes) -1) {
                $listRecipes .= '<hr style="background-color: #fff; border-top: 2px dashed #8c8b8b;" class="mt-0">';
            }
        }

        if ($listRecipes) {
            return $this->builder
                ->setTemplate($this->print ? $this->templatePrintRecipes : $this->templateRecipesCard)
                ->addBrackets(['ROWS_RECIPES' => $listRecipes])
                ->build()
                ->result;
        }
    }

    private function ingredients()
    {
        $ingredientFactory = new IngredientListFactory($this->id);
        $ingredientRows = $ingredientFactory->dishIngredientPacker(true, $this->print)->pack();

        if (! $ingredientRows) {
            return false;
        }

        return $this->builder
            ->setTemplate($this->print ? $this->templatePrintIngredients : $this->templateIngredientsCard)
            ->addBrackets(['LIST_INGREDIENTS' => $ingredientRows])
            ->build()
            ->result;
    }
}