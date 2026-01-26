<?php

namespace Cookbook\Domain\Categories;

use Cookbook\{Helpers\Sanitizer, Helpers\LastUpdated};
use Delight\Auth\Role;

/**
 * Description of CategoriesController
 *
 * @author AlexK
 */
class CategoriesController
{
    private $template = 'Categories/container.html';
    private $templateList = 'Categories/categories_list.html';
    private $templateEdit = 'Categories/categories_edit.html';
    private $modelName = 'Cookbook\Domain\Categories\CategoriesModel';
    private $model;
    private $builder;
    private $resolver;
    private $path = [];
    private $auth;
    private $notification;
    private $content;
    private $pageTitle = 'Manage Categories';
    private $category_title = 'Manage Categories';
    
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
        if ($this->path[2] === 'manage') {
            $this->manage();
        }
        
        if ($this->path[2] === 'src') {
            die($this->autoComplete());
        }
    }
    
    private function autoComplete()
    {
        $term = Sanitizer::sanitize(filter_input(INPUT_GET, 'term', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        $data = $this->model->getAutoComplete($term);
        $list = [];
        
        if ($data) {
            foreach ($data as $item) {
                $list[] = [
                    'id' => $item['category_id'],
                    'value' => $item['category_name']
                ];
            }
        }
        
        return json_encode($list);
    }
    
    private function manage()
    {
        if (! $this->auth->hasRole(Role::CHEF)) {
            header('location: /');
            die();
        }
        
        $new_category_name = Sanitizer::sanitize(filter_input(INPUT_POST, 'new_category_name', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        $new_description = Sanitizer::sanitize(filter_input(INPUT_POST, 'new_description', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        $update_category_name = Sanitizer::sanitize(filter_input(INPUT_POST, 'update_category_name', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        $update_description = Sanitizer::sanitize(filter_input(INPUT_POST, 'update_description', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        $update_category_id = Sanitizer::sanitize(filter_input(INPUT_POST, 'update_category_id', FILTER_SANITIZE_NUMBER_INT));
        $delete_category_id = Sanitizer::sanitize(filter_input(INPUT_POST, 'delete_category_id', FILTER_SANITIZE_NUMBER_INT));
        
        if ($this->path[3] === 'edit' && intval($this->path[4])) {
            $this->pageTitle = 'Edit Category';
            $this->content = $this->bindCategory((int)$this->path[4], $this->templateEdit);
            return;
        }
        
        if ($new_category_name) {
            $this->model->createCategory($new_category_name, $new_description);
            $_SESSION['notification']['text'] = 'New Category created';
            $_SESSION['notification']['type'] = 'success';
            header('location: /categories/manage/');
            die();
        }
        
        if ($update_category_id) {
            $this->model->updateCategory($update_category_id, $update_category_name, $update_description);
            $_SESSION['notification']['text'] = 'Category updated';
            $_SESSION['notification']['type'] = 'success';
            header('location: /categories/manage/');
            die();
        }
        
        if ($delete_category_id) {
            $this->model->deleteCategory($delete_category_id);
            header('location: /categories/manage/');
            die();
        }
        
        if ($this->path[3] === 'list') {
            echo $this->ajaxCategoryList();
            die();
        }
        
        $this->content = $this->bindCategoryList();
    }
    
    public function ajaxCategoryList()
    {
        $categories = $this->model->getCategoryList();
        
        if (! $categories) {
            return json_encode(['data' => false]);
        }
        
        $rows = [];

        foreach ($categories as $row) {
            $rows[] = [
                $row['category_name'],
                $row['description'] ?? '',
                '<a href="/categories/manage/edit/' . $row['category_id'] . '" class="pull-left text-secondary"><i class="fa fa-edit" title="Edit"></i></a> '
                . '<a class="pull-right text-secondary" data-record-id="' . $row['category_id'] . '" data-record-title="' . htmlspecialchars($row['category_name']) . '" data-toggle="modal" data-target="#confirm-delete" href="#"><i class="fa fa-trash" title="Delete"></i></a>'
            ];
        }
        
        return json_encode(['data' => $rows]);
    }
    
    private function bindCategoryList()
    {
        $date = date_create(LastUpdated::table('china'));
        $updatedDate = date_format($date, 'g:i A \o\n l jS F Y');
        $content = $this->builder
                ->setTemplate($this->templateList)
                ->addBrackets(['UPDATED_DATE' => $updatedDate])
                ->build();
        
        return $content->result;
    }
    
    private function bindCategory($id, $template)
    {
        $category = $this->model->getCategory($id);
        $this->pageTitle .= ' <small>' . $category['category_name'] . '</small>';
        $this->category_title = $category['category_name'];
        $content = $this->builder
                ->setTemplate($template)
                ->addBrackets([
                    'CATEGORY_NAME' => $category['category_name'],
                    'DESCRIPTION' => $category['description'],
                    'CATEGORY_ID' => $category['category_id'],
                    'BTN_EDIT_DISPALY' => $this->auth->hasRole(Role::CHEF)
                        ? ' d-inline' : ' d-none'
                ])
                ->build();
        
        return $content->result;
    }
    
    public function output()
    {
        $container = $this->builder->setTemplate($this->template);

        return [
            'CONTAINER' => $container->template,
            'PAGE_TITLE' => $this->pageTitle,
            'CONTENT' => $this->content,
            'ACTIVE_MANAGE_CATEGORIES' => ' active',
            'NOTIFICATION' => $this->notification,
            'META_TITLE' => $this->category_title,
        ];
    }
}