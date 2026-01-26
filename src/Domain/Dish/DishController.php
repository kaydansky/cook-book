<?php

namespace Cookbook\Domain\Dish;

use Cookbook\{Domain\Dietary\DietaryFactory,
    Domain\Image\ThumbnailBinder,
    Helpers\Format,
    Helpers\Sanitizer,
    Helpers\LastUpdated,
    Helpers\ListOptions,
    Domain\Filter\AutocompleteFactory,
    Domain\Filter\SearchFactory};

use Delight\Auth\Role;

/**
 * Description of DishController
 *
 * @author AlexK
 */
class DishController
{
    protected $modelName = 'Cookbook\Domain\Dish\DishModel';
    protected $model;
    protected $builder;
    protected $resolver;
    protected $auth;
    protected $path = [];
    protected $dish_title;

    private $container;
    private $containerList = 'Dish/container_list.html';
    private $containerManage = 'Dish/container_manage.html';
    private $containerCreate = 'Dish/container_create.html';
    private $containerView = 'Dish/container_view.html';
    private $templateGrid = 'Dish/dish_grid.html';
    private $templateCard = 'Dish/dish_card.html';
    private $templateCreate = 'Dish/dish_create.html';
    private $content;
    private $notification;
    private $pageTitle = 'Dishes';
    private $paginator;
    private $description;
    private $category_id;
    private $data;
    private $descLimitCharacters = 140;
    private $wizard_step;
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
        $this->data = new SearchFactory('dish', $this->model);

        if ($this->path[2] === 'manage') {
            $this->pageTitle = $this->dish_title = 'Manage Dishes';
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
            $this->content = $view->bindDish();
            $this->dish_title = $view->dish_title;
            return;
        }

        if ($this->path[2] === 'loggedin') {
            $this->notification = 'notif.Promo("top", "center", "<span>You are logged in</span>", "success");';
        }

        new AutocompleteFactory('dish', $this->path[2], $this->model);
        $this->container = $this->containerList;
        $this->content = $this->bindDishList($this->data->result());
        $this->dish_title = 'Dishes';

        return;
    }

    private function bindDishList($data)
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

            $thumbnail = ThumbnailBinder::bindThumbnail($item['image_filenames'], 'dish', $item['dish_id'], $item['dish_title']);

            $content .= $this->builder
                ->setTemplate($this->templateCard)
                ->addBrackets([
                    'THUMBNAIL' => $thumbnail,
                    'DISH_TITLE' => $item['dish_title'],
                    'SUBTITLE' => $item['dish_subtitle'],
                    'DESCRIPTION' => strlen($item['description']) > $this->descLimitCharacters
                        ? '<a class="plus-cursor" data-toggle="popover" data-content="' . htmlspecialchars($item['description']) . '"><p class="card-text">'
                        . substr($item['description'], 0, $this->descLimitCharacters) . '...</p></a>' : ($item['description'] ?: '<p class="card-text text-muted">No description</p>'),
                    'DISH_ID' => $item['dish_id'],
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
            header('location: /dish');
            die();
        }

        $delete_dish_id = Sanitizer::sanitize(filter_input(INPUT_POST, 'delete_dish_id', FILTER_SANITIZE_NUMBER_INT));
        $approve_dish_id = Sanitizer::sanitize(filter_input(INPUT_POST, 'approve_dish_id', FILTER_SANITIZE_NUMBER_INT));
        $disapprove_dish_id = Sanitizer::sanitize(filter_input(INPUT_POST, 'disapprove_dish_id', FILTER_SANITIZE_NUMBER_INT));

        if ($approve_dish_id && $this->auth->hasRole(Role::CHEF)) {
            $this->model->approveDish($approve_dish_id);
            die();
        }

        if ($disapprove_dish_id && $this->auth->hasRole(Role::CHEF)) {
            $this->model->disapproveDish($disapprove_dish_id);
            die();
        }

        if ($delete_dish_id && $this->auth->hasRole(Role::CHEF)) {
            $this->model->deleteDish($delete_dish_id);
            header('location: /dish/manage/');
            die();
        }

        $this->container = $this->containerManage;

        if ($this->path[3] === 'new') {
            $this->filterPost();

            if ($this->dish_title && $this->description) {
                $newId = $this->model->createDish($this->dish_title, $this->description, $this->category_id);
                header('location: /dish/manage/edit/' . $newId . '/2');
                die();
            }

            $this->pageTitle = $this->dish_title = 'Create Dish';
            $this->container = $this->containerCreate;
            $this->content = $this->newDish();
            return;
        }

        if ($this->path[3] === 'edit' && intval($this->path[4]) && $this->auth->hasRole(Role::CHEF)) {
            $this->filterPost();

            if ($this->dish_title && $this->description) {
                $this->model->updateDish((int)$this->path[4], $this->dish_title, $this->description, $this->category_id);

                if ($this->wizard_step == 4) {
                    header('location: /dish/' . $this->path[4]);
                    die();
                }

                header('location: /dish/manage/edit/' . $this->path[4] . '/' . ($this->wizard_step + 1));
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
            $this->content = $edit->editDish();
            $this->pageTitle = 'Edit Dish | <small>' . $edit->dishTitle . '</small>';
            $this->dish_title = $edit->dishTitle;
            return;
        }

        if ($this->auth->hasRole(Role::CHEF)) {
            if ($this->path[3] === 'list') {
                die($this->ajaxDishList());
            }

            $this->content = $this->bindDishGrid();
        }
    }

    public function ajaxDishList()
    {
        $data = $this->model->getDishGrid();

        if (!$data) {
            return json_encode(['data' => false]);
        }

        $rows = [];

        foreach ($data as $row) {
            $approved = $row['approved'] == 1 ? ' checked' : '';

            $rows[] = [
                '<a href="/dish/' . $row['dish_id'] . '" title="View">' . $row['dish_title'] . '</a>',
                $row['dish_subtitle'] . ' <span hidden>' . $row['alt_search_terms'] . '</span>',
                $row['description'],
                '<div class="custom-control custom-checkbox"><input id="'
                . $row['dish_id']
                . '" class="check-approve custom-control-input" type="checkbox" value="'
                . $row['dish_id']
                . '" '
                . $approved
                . '><label class="custom-control-label" for="'
                . $row['dish_id']
                . '">&nbsp;</label><p class="d-none">'
                . $approved
                . '</p>',
                '<a href="/dish/manage/edit/' . $row['dish_id'] . '" class="pull-left text-secondary"><i class="fa fa-edit" title="Edit"></i></a> '
                . '<a class="pull-right text-secondary" data-record-id="'
                . $row['dish_id'] . '" data-record-title="'
                . htmlspecialchars($row['dish_title'] ?? '') . '" data-toggle="modal" data-target="#confirm-delete" href="#"><i class="fa fa-trash" title="Delete"></i></a>'
            ];
        }

        return json_encode(['data' => $rows]);
    }

    private function bindDishGrid()
    {
        $date = date_create(LastUpdated::table('dishes'));
        $updatedDate = date_format($date, 'g:i A \o\n l jS F Y');
        $content = $this->builder
            ->setTemplate($this->templateGrid)
            ->addBrackets(['UPDATED_DATE' => $updatedDate])
            ->build();

        return $content->result;
    }

    private function newDish()
    {
        $content = $this->builder
            ->setTemplate($this->templateCreate)
            ->addBrackets([
                'NEW_UNITS_OPTIONS' => ListOptions::unit(),
                'NEW_EQUIPMENT_OPTIONS' => ListOptions::equipment(),
            ])
            ->build();

        return $content->result;
    }

    private function filterPost()
    {
        $this->dish_title = Sanitizer::sanitize(filter_input(INPUT_POST, 'dish_title', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        $this->description = Sanitizer::sanitize(filter_input(INPUT_POST, 'description', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        $this->category_id = filter_input(INPUT_POST, 'category_id', FILTER_SANITIZE_NUMBER_INT, FILTER_REQUIRE_ARRAY);
        $this->wizard_step = filter_input(INPUT_POST, 'wizard_step', FILTER_SANITIZE_NUMBER_INT);
    }

    public function output()
    {
        $container = $this->builder->setTemplate($this->container);

        return [
            'CONTAINER' => $container->template,
            'ACTIVE_MANAGE_DISHES' => ' active',
            'ACTIVE_DISHES' => ' active',
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
            'META_TITLE' => $this->dish_title,
            'RANGE_DATE_ADDED_CHECKED' => $this->data->rangeDateAddedChecked(),
            'RANGE_DATE_MODIFIED_CHECKED' => $this->data->rangeDateModifiedChecked(),
            'RANGE_DISH_DATE_CHECKED' => $this->data->rangeDishDateChecked(),
            'RANGE_FROM' => $this->data->rangeFrom(),
            'RANGE_TO' => $this->data->rangeTo(),
            'SORT_AZ' => $this->data->sortAz(),
            'SORT_ADDED' => $this->data->sortAdded(),
            'SORT_MODIFIED' => $this->data->sortModified(),
            'SORT_DISH' => $this->data->sortDishDate(),
            'FILTER_SOURCE' => $this->data->source(),
            'FILTER_AUTHOR' => $this->data->author(),
            'RESTRICTION_OPTIONS' => $this->restrictionOptions,
        ];
    }
}