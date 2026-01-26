<?php

namespace Cookbook\Domain\Recipe;

use Cookbook\{Domain\Dietary\DietaryFactory,
    Domain\Image\ImageFactory,
    Domain\Image\ThumbnailBinder,
    Helpers\Format,
    Helpers\Sanitizer,
    Helpers\LastUpdated,
    Helpers\ListOptions,
    Domain\Filter\AutocompleteFactory,
    Domain\Filter\SearchFactory};
use Delight\Auth\Role;

/**
 * Description of Recipe
 *
 * @author AlexK
 */
class RecipeController
{
    protected $modelName = 'Cookbook\Domain\Recipe\RecipeModel';
    protected $model;
    protected $builder;
    protected $resolver;
    protected $notification;
    protected $auth;
    protected $recipe_title;
    protected $path = [];

    private $container;
    private $containerList = 'Recipe/container_list.html';
    private $containerManage = 'Recipe/container_manage.html';
    private $containerCreate = 'Recipe/container_create.html';
    private $containerView = 'Recipe/container_view.html';
    private $templateGrid = 'Recipe/recipe_grid.html';
    private $templateCard = 'Recipe/recipe_card.html';
    private $templateCreate = 'Recipe/recipe_create.html';
    private $content;
    private $pageTitle = 'Recipes';
    private $paginator;
    private $description;
    private $category_id;
    private $still;
    private $wizard_step;
    private $data;
    private $descLimitCharacters = 140;
    private $restrictionOptions;

    public function inject($path, $auth, $builder, $resolver)
    {
        $this->path = array_replace($this->path, $path);
        $this->auth = $auth;
        $this->builder = $builder;
        $this->resolver = $resolver;
        $this->model = $this->resolver->resolve($this->modelName);
        $this->model->inject($auth);
    }

    public function action()
    {
        $yield_type = Sanitizer::sanitize(filter_input(INPUT_POST, 'yield_type', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        $get_step_images = filter_input(INPUT_POST, 'get_step_images', FILTER_SANITIZE_NUMBER_INT);
        $get_step_images_carousel = filter_input(INPUT_POST, 'get_step_images_carousel', FILTER_SANITIZE_NUMBER_INT);
        $current_image = filter_input(INPUT_POST, 'current_image', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $recipe_id = filter_input(INPUT_POST, 'recipe_id', FILTER_SANITIZE_NUMBER_INT);
        $recipe_step_id = filter_input(INPUT_POST, 'recipe_step_id', FILTER_SANITIZE_NUMBER_INT);


        if ($yield_type) {
            die(ListOptions::unit([], $yield_type));
        }

        if ($get_step_images) {
            die((new ImageFactory('recipe', $this->model->getRecipeImages($recipe_id), $this->model->getStepImages($recipe_step_id)))->packSelectStepImages());
        }

        if ($get_step_images_carousel) {
            die((new ImageFactory('recipe', false, false, $this->model->getStepsContent($recipe_id)))->packCarouselSteps($current_image));
        }

        $this->data = new SearchFactory('recipe', $this->model);

        if ($this->path[2] === 'manage') {
            $this->pageTitle = $this->recipe_title = 'Manage Recipes';
            $this->manage();
            return;
        }

        if (intval($this->path[2])) {
            $this->container = $this->containerView;
            $view = new ViewController();
            $view->id = (int)$this->path[2];
            $view->model = $this->model;
            $view->builder = $this->builder;
            $view->resolver = $this->resolver;
            $view->auth = $this->auth;
            $this->content = $view->bindRecipe();
            $this->recipe_title = $view->recipe_title;
            return;
        }

        if ($this->path[2] === 'loggedin') {
            $this->notification = 'notif.Promo("top", "center", "<span>You are logged in</span>", "success");';
        }

        new AutocompleteFactory('recipe', $this->path[2], $this->model);
        $this->container = $this->containerList;
        $this->content = $this->bindRecipeList($this->data->result());
        $this->recipe_title = 'Recipes';

        return;
    }

    private function bindRecipeList($data)
    {
        if (! $data['data']) {
            return;
        }

        $this->paginator = $data['paginator'];
        $this->pageTitle .= '&nbsp;<small>'
            . $data['totalRecords']
            . '&nbsp;' . 'items, page '
            . $data['currentPage']
            . ' of ' . $data['totalPages'] . '</small>';
        $content = '';
        $count = 1;

        foreach ($data['data'] as $item) {
//            if ($count % 4 == 1) {
//                $content .= '<div class="row mb-4">';
//            }

            $thumbnail = ThumbnailBinder::bindThumbnail($item['image_filenames'], 'recipe', $item['recipe_id'], $item['recipe_title']);

            $content .= $this->builder
                ->setTemplate($this->templateCard)
                ->addBrackets([
                    'THUMBNAIL' => $thumbnail,
                    'RECIPE_TITLE' => $item['recipe_title'],
                    'DESCRIPTION' => strlen($item['description'] ?? '') > $this->descLimitCharacters
                        ? '<a class="plus-cursor" data-toggle="popover" data-content="' . htmlspecialchars($item['description']) . '"><p class="card-text">'
                        . substr($item['description'], 0, $this->descLimitCharacters) . '...</p></a>' : ($item['description'] ?: '<p class="card-text text-muted">No description</p>'),
                    'RECIPE_ID' => $item['recipe_id'],
                    'DATE_ADDED' => $item['date_added'] ? '<tr><td align="right" class="card-text py-0 text-muted"><small>Added:</small></td><td class="card-text py-0"><small>' . Format::date($item['date_added']) . '</small></td></tr>' : '',
                    'DATE_MODIFIED' => $item['date_modified'] ? '<tr><td align="right" class="card-text py-0 text-muted"><small>Modified:</small></td><td class="card-text py-0"><small>' . Format::date($item['date_modified']) . '</small></td></tr>' : '',
                    'SOURCE' => $item['source'] ? '<tr><td align="right" class="card-text py-0 text-muted"><small>Source:</small></td><td class="card-text py-0"><small>'
                        . ($item['source_link'] ? '<a href="' . $item['source_link'] . '" target="_blank">' . $item['source'] . '</a>' : $item['source']) . '</small></td></tr>' : '',
                    'AUTHOR' => $item['first_name'] || $item['last_name'] ? '<tr><td align="right" class="card-text py-0 text-muted"><small>Author:</small></td><td class="card-text py-0"><small>' . $item['first_name'] . ' ' . $item['last_name'] . '</small></td></tr>' : '',
                ])
                ->build()
                ->result;

//            if ($count % 4 == 0) {
//                $content .= '</div>';
//            }

            $count++;
        }

        if ($count % 4 != 1) {
            $content .= '</div>';
        }

        return $content;
    }

    private function manage()
    {
        if (! $this->auth->hasAnyRole(Role::CHEF, Role::COOK)) {
            header('location: /recipe');
            die();
        }

        $delete_recipe_id = Sanitizer::sanitize(filter_input(INPUT_POST, 'delete_recipe_id', FILTER_SANITIZE_NUMBER_INT));
        $approve_recipe_id = Sanitizer::sanitize(filter_input(INPUT_POST, 'approve_recipe_id', FILTER_SANITIZE_NUMBER_INT));
        $disapprove_recipe_id = Sanitizer::sanitize(filter_input(INPUT_POST, 'disapprove_recipe_id', FILTER_SANITIZE_NUMBER_INT));
        $clone_recipe_id = Sanitizer::sanitize(filter_input(INPUT_POST, 'clone_recipe_id', FILTER_SANITIZE_NUMBER_INT));

        if ($delete_recipe_id && $this->auth->hasRole(Role::CHEF)) {
            $this->model->deleteRecipe($delete_recipe_id);
            header('location: /recipe/manage/');
            die();
        }

        if ($clone_recipe_id && $this->auth->hasRole(Role::CHEF)) {
            die($this->model->cloneRecipe($clone_recipe_id));
        }

        if ($approve_recipe_id && $this->auth->hasRole(Role::CHEF)) {
            $this->model->approveRecipe($approve_recipe_id);
            die();
        }

        if ($disapprove_recipe_id && $this->auth->hasRole(Role::CHEF)) {
            $this->model->disapproveRecipe($disapprove_recipe_id);
            die();
        }

        $this->container = $this->containerManage;

        if ($this->path[3] === 'new') {
            $this->filterPost();

            if ($this->recipe_title && $this->description) {
                $newId = $this->model->createRecipe($this->recipe_title, $this->description, $this->category_id);
                header('location: /recipe/manage/edit/' . $newId . '/2');
                die();
            }

            $this->pageTitle = $this->recipe_title = 'Create Recipe';
            $this->container = $this->containerCreate;
            $this->content = $this->newRecipe();
            return;
        }

        if ($this->path[3] === 'edit' && intval($this->path[4]) && $this->auth->hasRole(Role::CHEF)) {
            $this->filterPost();

            if ($this->recipe_title && $this->description) {
                $this->model->updateRecipe((int)$this->path[4], $this->recipe_title, $this->description, $this->category_id);

                if ($this->wizard_step == 4) {
                    header('location: ' . ($this->still ? '/recipe/manage/edit/' . $this->path[4] . '/4/#imgs' : '/recipe/' . $this->path[4]));
                    die();
                }

                header('location: /recipe/manage/edit/' . $this->path[4] . '/' . ($this->wizard_step + 1));
                die();
            }

            $dietaryFactory = new DietaryFactory();
            $this->restrictionOptions = $dietaryFactory->optionsPacker()->pack();

            $this->container = $this->containerCreate;
            $edit = new EditController();
            $edit->id = (int)$this->path[4];
            $edit->model = $this->model;
            $edit->builder = $this->builder;
            $edit->path = $this->path;
            $this->content = $edit->editRecipe();
            $this->pageTitle = 'Edit Recipe | <small>' . $edit->recipeTitle . '</small>';
            $this->recipe_title = $edit->recipeTitle;
            return;
        }

        if ($this->auth->hasRole(Role::CHEF)) {
            if ($this->path[3] === 'list') {
                die($this->ajaxRecipeList());
            }

            $this->content = $this->bindRecipeGrid();
        }
    }

    private function ajaxRecipeList()
    {
        $data = $this->model->getRecipeGrid();

        if (!$data) {
            return json_encode(['data' => false]);
        }

        $rows = [];

        foreach ($data as $row) {
            $approved = $row['approved'] == 1 ? ' checked' : '';

            $rows[] = [
                '<a href="/recipe/' . $row['recipe_id'] . '" title="View">' . $row['recipe_title'] . '</a>',
                $row['description'] . ' <span hidden>' . $row['alt_search_terms'] . '</span>',
                '<div class="custom-control custom-checkbox"><input id="'
                . $row['recipe_id']
                . '" class="check-approve custom-control-input" type="checkbox" value="'
                . $row['recipe_id']
                . '" '
                . $approved
                . '><label class="custom-control-label" for="'
                . $row['recipe_id']
                . '">&nbsp;</label><p class="d-none">'
                . $approved
                . '</p>',
                '<a href="/recipe/manage/edit/' . $row['recipe_id'] . '" class="text-secondary mr-2"><i class="fa fa-edit" title="Edit"></i></a>&nbsp;'
                . '<a class="text-secondary mr-2" data-record-id="'
                . $row['recipe_id'] . '" data-record-title="'
                . htmlspecialchars($row['recipe_title'] ?? '') . '" data-toggle="modal" data-target="#confirm-delete" href="#"><i class="fa fa-trash" title="Delete"></i></a>&nbsp;'
                . ($this->auth->hasRole(Role::CHEF) ? '<a class="text-secondary" data-record-id="'
                . $row['recipe_id'] . '" data-record-title="'
                . htmlspecialchars($row['recipe_title'] ?? '') . '" data-toggle="modal" data-target="#confirm-clone" href="#"><i class="fa fa-copy" title="Clone"></i></a>' : '')
            ];
        }

        return json_encode(['data' => $rows]);
    }

    private function bindRecipeGrid()
    {
        $date = date_create(LastUpdated::table('recipes'));
        $updatedDate = date_format($date, 'g:i A \o\n l jS F Y');
        $content = $this->builder
            ->setTemplate($this->templateGrid)
            ->addBrackets(['UPDATED_DATE' => $updatedDate])
            ->build();

        return $content->result;
    }

    private function newRecipe()
    {
        return $this->builder
            ->setTemplate($this->templateCreate)
            ->addBrackets([
                'NEW_UNITS_OPTIONS' => ListOptions::unit(),
                'NEW_EQUIPMENT_OPTIONS' => ListOptions::equipment()
            ])
            ->build()
            ->result;
    }

    private function filterPost()
    {
        $this->recipe_title = Sanitizer::sanitize(filter_input(INPUT_POST, 'recipe_title', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        $this->description = Sanitizer::sanitize(filter_input(INPUT_POST, 'description', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        $this->category_id = filter_input(INPUT_POST, 'category_id', FILTER_SANITIZE_NUMBER_INT, FILTER_REQUIRE_ARRAY);
        $this->still = filter_input(INPUT_POST, 'still', FILTER_SANITIZE_NUMBER_INT);
        $this->wizard_step = filter_input(INPUT_POST, 'wizard_step', FILTER_SANITIZE_NUMBER_INT);
    }

    public function output()
    {
        return [
            'CONTAINER' => $this->builder->setTemplate($this->container)->template,
            'ACTIVE_MANAGE_RECIPES' => ' active',
            'ACTIVE_RECIPES' => ' active',
            'CONTENT' => $this->content,
            'QUERY' => $this->data->query(),
            'PAGE_TITLE' => $this->pageTitle,
            'NOTIFICATION' => $this->notification,
            'CATEGORY_NAME' => $this->data->categoryName(),
            'PAGINATOR' => $this->paginator,
            'QUERY_INGREDIENT' => $this->data->queryIngredient(),
            'IS_INGREDIENT_YES_CHECKED' => $this->data->isIngredientYesChecked(),
            'IS_INGREDIENT_NO_CHECKED' => $this->data->isIngredientNoChecked(),
            'WHOLE_WORD_CHECKED' => $this->data->wholeWordChecked(),
            'MY_ITEMS_CHECKED' => $this->data->isMyItemsChecked(),
            'DAYS_RANGE' => $this->data->daysRange(),
            'META_TITLE' => $this->recipe_title,
            'RANGE_DATE_ADDED_CHECKED' => $this->data->rangeDateAddedChecked(),
            'RANGE_DATE_MODIFIED_CHECKED' => $this->data->rangeDateModifiedChecked(),
            'RANGE_FROM' => $this->data->rangeFrom(),
            'RANGE_TO' => $this->data->rangeTo(),
            'SORT_AZ' => $this->data->sortAz(),
            'SORT_ADDED' => $this->data->sortAdded(),
            'SORT_MODIFIED' => $this->data->sortModified(),
            'FILTER_SOURCE' => $this->data->source(),
            'FILTER_AUTHOR' => $this->data->author(),
            'RECIPE_CHECKED' => $this->data->recipeChecked(),
            'DISH_CHECKED' => $this->data->dishChecked(),
            'RECIPEID' => isset($this->path[4]) ? (int)$this->path[4] : '',
            'RESTRICTION_OPTIONS' => $this->restrictionOptions,
        ];
    }
}