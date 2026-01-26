<?php

namespace Cookbook\Domain\Ingredient;

use Cookbook\{Helpers\Format,
    Helpers\Sanitizer,
    Helpers\LastUpdated,
    Domain\Filter\AutocompleteFactory,
    Domain\Filter\SearchFactory,
    Domain\Dietary\DietaryFactory};
use Delight\Auth\Role;

/**
 * Description of IngredientController
 *
 * @author AlexK
 */
class IngredientController
{
    private $template;
    private $templateList = 'Ingredient/container_list.html';
    private $templateManage = 'Ingredient/container_manage.html';
    private $templateGrid = 'Ingredient/ingredient_grid.html';
    private $containerView = 'Ingredient/container_view.html';
    private $templateView = 'Ingredient/ingredient_view.html';
    private $templateEdit = 'Ingredient/ingredient_edit.html';
    private $templateCard = 'Ingredient/ingredient_card.html';
    private $templateNotice = 'Ingredient/ingredient_notice.html';
    private $modelName = 'Cookbook\Domain\Ingredient\IngredientModel';
    private $model;
    private $builder;
    private $resolver;
    private $path = [];
    private $auth;
    private $notification;
    private $content;
    private $pageTitle = 'Ingredient List';
    private $totalPages = 0;
    private $paginator;
    private $data;
    private $restrictionOptions;
    private $ingredient_title;
    
    public function __construct()
    {
        if (isset($_SESSION['notification'])) {
            $this->notification = 'notif.Promo("top", "center", "<span>' . $_SESSION['notification']['text'] . '</span>", "' . $_SESSION['notification']['type'] . '");';
            unset($_SESSION['notification']);
        }
    }

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
        $this->data = new SearchFactory('ingredient', $this->model);

        if ($this->path[2] === 'manage') {
            $this->pageTitle = $this->ingredient_title = 'Manage Ingredients';
            $this->manage();
            return;
        }
        
        if (intval($this->path[2])) {
            $this->template = $this->containerView;
            $this->pageTitle = 'Ingredient';
            $this->content = $this->bindIngredient((int)$this->path[2], $this->templateView);
            return;
        }

        new AutocompleteFactory('ingredient', $this->path[2], $this->model);
        $this->template = $this->templateList;
        $this->content = $this->bindIngredientList($this->data->result());
    }
    
    private function manage()
    {
        if (! $this->auth->hasAnyRole(Role::CHEF, Role::COOK, Role::SUPER_ADMIN)) {
            header('location: /ingredient');
            die();
        }
        
        $this->template = $this->templateManage;
        $dietaryFactory = new DietaryFactory();
        $this->restrictionOptions = $dietaryFactory->optionsPacker()->pack();
        
        $new_ingredient = Sanitizer::sanitize(filter_input(INPUT_POST, 'new_ingredient', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        $new_dietary_restriction_id = filter_input(INPUT_POST, 'new_dietary_restriction_id', FILTER_SANITIZE_NUMBER_INT, FILTER_REQUIRE_ARRAY);
        $new_alt_search_terms = Sanitizer::sanitize(filter_input(INPUT_POST, 'new_alt_search_terms', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        $new_description = Sanitizer::sanitize(filter_input(INPUT_POST, 'new_description', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        $delete_ingredient_id = Sanitizer::sanitize(filter_input(INPUT_POST, 'delete_ingredient_id', FILTER_SANITIZE_NUMBER_INT));
        $update_ingredient = Sanitizer::sanitize(filter_input(INPUT_POST, 'update_ingredient', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        $update_dietary_restriction_id = filter_input(INPUT_POST, 'update_dietary_restriction_id', FILTER_SANITIZE_NUMBER_INT, FILTER_REQUIRE_ARRAY);
        $update_alt_search_terms = Sanitizer::sanitize(filter_input(INPUT_POST, 'update_alt_search_terms', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        $update_description = Sanitizer::sanitize(filter_input(INPUT_POST, 'update_description', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        $remove_image = Sanitizer::sanitize(filter_input(INPUT_POST, 'remove_image', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        $update_ingredient_id = Sanitizer::sanitize(filter_input(INPUT_POST, 'update_ingredient_id', FILTER_SANITIZE_NUMBER_INT));
        $approved = Sanitizer::sanitize(filter_input(INPUT_POST, 'approved', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        $approve_ingredient_id = Sanitizer::sanitize(filter_input(INPUT_POST, 'approve_ingredient_id', FILTER_SANITIZE_NUMBER_INT));
        $disapprove_ingredient_id = Sanitizer::sanitize(filter_input(INPUT_POST, 'disapprove_ingredient_id', FILTER_SANITIZE_NUMBER_INT));

        if ($approve_ingredient_id) {
            $this->model->approveIngredient($approve_ingredient_id);
            die();
        }

        if ($disapprove_ingredient_id) {
            $this->model->disapproveIngredient($disapprove_ingredient_id);
            die();
        }
        
        if ($this->path[3] === 'edit' && intval($this->path[4]) && $this->auth->hasRole(Role::SUPER_ADMIN)) {
            $this->pageTitle = 'Edit Ingredient';
            $this->content = $this->bindIngredient((int)$this->path[4], $this->templateEdit);
            return;
        }
        
        if ($this->path[3] === 'approve' && intval($this->path[4]) && $this->auth->hasRole(Role::SUPER_ADMIN)) {
            $this->model->approveIngredient((int)$this->path[4]);
            $_SESSION['notification']['text'] = 'Ingredient approved';
            $_SESSION['notification']['type'] = 'success';
            header('location: /ingredient/manage/');
            die();
        }

        if ($new_ingredient) {
            $newId = $this->model->createIngredient($new_ingredient, $new_dietary_restriction_id, $new_alt_search_terms, $new_description);
            $_SESSION['notification']['text'] = 'New Ingredient created';
            $_SESSION['notification']['type'] = 'success';
            header('location: /ingredient/' . $newId);
            die();
        }
        
        if ($update_ingredient_id && $this->auth->hasRole(Role::SUPER_ADMIN)) {
            $this->model->updateIngredient($update_ingredient_id, $update_dietary_restriction_id, $update_ingredient, $update_alt_search_terms, $update_description, $remove_image, $approved);
            $_SESSION['notification']['text'] = 'Ingredient updated';
            $_SESSION['notification']['type'] = 'success';
            header('location: /ingredient/' . $update_ingredient_id);
            die();
        }
        
        if ($delete_ingredient_id && $this->auth->hasRole(Role::SUPER_ADMIN)) {
            $this->model->deleteIngredient($delete_ingredient_id);
            header('location: /ingredient/manage/');
            die();
        }

        if ($this->auth->hasRole(Role::SUPER_ADMIN)) {
            if ($this->path[3] === 'list') {
                die($this->ajaxIngredientList());
            }

            $this->content = $this->bindIngredientGrid();
        } else {
            $this->content = $this->builder->setTemplate($this->templateNotice)->template;
        }
    }
    
    public function ajaxIngredientList()
    {
        $ingrdients = $this->model->getIngredientGrid();
        
        if (! $ingrdients) {
            return json_encode(['data' => false]);
        }
        
        $rows = [];

        foreach ($ingrdients as $row) {
            $approved = $row['approved'] == 1 ? ' checked' : '';

            $rows[] = [
                '<a href="/ingredient/' . $row['ingredient_id'] . '" title="View">' . $row['ingredient'] . '</a>',
                '<a data-toggle="popover" data-content="' . $row['description'] . '">' . $row['description'] . '</a><span hidden>' . $row['alt_search_terms'] . '</span>',
                Format::dateTime($row['date_added']),
                '<div class="custom-control custom-checkbox"><input id="'
                . $row['ingredient_id']
                . '" class="check-approve custom-control-input" type="checkbox" value="'
                . $row['ingredient_id']
                . '" '
                . $approved
                . '><label class="custom-control-label" for="'
                . $row['ingredient_id']
                . '">&nbsp;</label><p class="d-none">'
                . $approved
                . '</p>',
                '<a href="/ingredient/manage/edit/' . $row['ingredient_id'] . '" class="pull-left text-secondary"><i class="fa fa-edit" title="Edit"></i></a> '
                . '<a class="pull-right text-secondary" data-record-id="' 
                . $row['ingredient_id'] .  '" data-record-title="' 
                . htmlspecialchars($row['ingredient']) . '" data-toggle="modal" data-target="#confirm-delete" href="#"><i class="fa fa-trash" title="Delete"></i></a>'
            ];
        }
        
        return json_encode(['data' => $rows]);
    }
    
    private function bindIngredientGrid()
    {
        $date = date_create(LastUpdated::table('ingredient'));
        $updatedDate = date_format($date, 'g:i A \o\n l jS F Y');
        $content = $this->builder
                ->setTemplate($this->templateGrid)
                ->addBrackets(['UPDATED_DATE' => $updatedDate])
                ->build();
        
        return $content->result;
    }
    
    private function bindIngredient($id, $template)
    {
        $ingredient = $this->model->getIngredient($id);
        
        if (!$ingredient) {
            return null;
        }

        $dietaryFactory = new DietaryFactory($id);
        $restrictionLinks = $dietaryFactory->commaListPacker()->pack();
        $restrictionList = $dietaryFactory->autocompleteInputPacker()->pack();
        $this->ingredient_title = $ingredient['ingredient'];

        $this->pageTitle .= ' <small>' . $ingredient['ingredient'] . '</small>';
        $content = $this->builder
                ->setTemplate($template)
                ->addBrackets([
                    'INGREDIENT' => $ingredient['ingredient'],
                    'IMAGE_FILENAME' => isset($ingredient['image_filename'])
                        ? '<a title="View Original" href="/images/ingredient/' . $ingredient['image_filename'] . '.jpg" target="_blank"><img class="d-block w-100 rounded align-middle img-fluid" src="/images/ingredient/' . $ingredient['image_filename'] . '.jpg" alt="View Original"></a><hr>'
                        : '',
                    'DESCRIPTION' => $ingredient['description'],
                    'ALT_SEARCH_TERMS' => $ingredient['alt_search_terms'],
                    'INGREDIENT_ID' => $ingredient['ingredient_id'],
                    'DISABLED' => $this->auth->hasRole(Role::SUPER_ADMIN) ? '' : ' disabled',
                    'CHECKED' => $ingredient['approved'] == 1 ? ' checked' : '',
                    'BTN_EDIT_DISPALY' => $this->auth->hasRole(Role::SUPER_ADMIN)
                        ? ' d-inline' : ' d-none',
                    'BTN_APPROVE_DISPALY' => $this->auth->hasRole(Role::SUPER_ADMIN)
                        && $ingredient['approved'] == 0
                        ? ' d-inline' : ' d-none',
                    'RESTRICTIONS' => $restrictionList,
                    'RESTRICTIONS_LINKS' => $restrictionLinks,
                ])
                ->build();
        
        return $content->result;
    }
    
    private function bindIngredientList($data)
    {
        if (!$data['data']) {
            return;
        }

        $this->paginator = $data['paginator'];
        $this->pageTitle .= '&nbsp;<small>'
            . $data['totalRecords']
            . '&nbsp;' . 'items, page '
            . $data['currentPage']
            . ' of ' . $data['totalPages'] . '</small>';
        $content = '';

        foreach ($data['data'] as $item) {
            if ($item['image_filename']) {
                $file = '<a href="/ingredient/'
                    . $item['ingredient_id']
                    . '"><img class="card-img-top" src="/images/ingredient/'
                    .  $item['image_filename'] . '_tn.jpg" alt="' . $item['ingredient'] . '"></a>';
            } else {
                $file = '';
            }

            $row = $this->builder
                ->setTemplate($this->templateCard)
                ->addBrackets([
                    'IMAGE' => $file,
                    'INGREDIENT_TITLE' => $item['ingredient'],
                    'DESCRIPTION' => $item['description'],
                    'INGREDIENT_ID' => $item['ingredient_id'],
                ])
                ->build();

            $content .= $row->result;
        }

        return $content;
    }
    
    public function output()
    {
        $container = $this->builder->setTemplate($this->template);

        return [
            'CONTAINER' => $container->template,
            'PAGE_TITLE' => $this->pageTitle,
            'CONTENT' => $this->content,
            'ACTIVE_MANAGE_INGREDIENTS' => ' active',
            'ACTIVE_INGREDIENTS' => ' active',
            'NOTIFICATION' => $this->notification,
            'TOTAL_PAGES' => $this->totalPages,
            'CATEGORY_NAME_SEL' => $this->data->categoryName(),
            'QUERY' => $this->data->query(),
            'PAGINATOR' => $this->paginator,
            'RESTRICTION_OPTIONS' => $this->restrictionOptions,
            'META_TITLE' => $this->ingredient_title,
        ];
    }
}