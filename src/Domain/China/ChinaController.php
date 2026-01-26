<?php

namespace Cookbook\Domain\China;

use Cookbook\{Helpers\Sanitizer, Helpers\LastUpdated};
use Delight\Auth\Role;

/**
 * Description of IngredientController
 *
 * @author AlexK
 */
class ChinaController
{
    private $template = 'China/container.html';
    private $templateList = 'China/china_list.html';
    private $templateView = 'China/china_view.html';
    private $templateEdit = 'China/china_edit.html';
    private $modelName = 'Cookbook\Domain\China\ChinaModel';
    private $model;
    private $builder;
    private $resolver;
    private $path = [];
    private $auth;
    private $notification;
    private $content;
    private $pageTitle = 'Manage China';
    private $china_title = 'Manage China';
    
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
        $term = Sanitizer::sanitize(filter_input(INPUT_GET, 'term', FILTER_SANITIZE_FULL_SPECIAL_CHARS));

        if ($this->path[2] === 'ac') {
            die($this->autoComplete($term));
        }

        if ($this->path[2] === 'manage') {
            $this->manage();
        } else if (intval($this->path[2])) {
            $this->pageTitle = 'China';
            $this->content = $this->bindChina((int)$this->path[2], $this->templateView);
            return;
        }
    }

    private function autoComplete($term)
    {
        $data = $this->model->getAutoComplete($term);
        $list = [];

        if ($data) {
            foreach ($data as $item) {
                $list[] = [
                    'id' => $item['china_id'],
                    'value' => $item['china_name']
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
        
        $new_china_name = Sanitizer::sanitize(filter_input(INPUT_POST, 'new_china_name', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        $new_manufacturer = Sanitizer::sanitize(filter_input(INPUT_POST, 'new_manufacturer', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        $update_china_name = Sanitizer::sanitize(filter_input(INPUT_POST, 'update_china_name', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        $update_manufacturer = Sanitizer::sanitize(filter_input(INPUT_POST, 'update_manufacturer', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        $remove_image = Sanitizer::sanitize(filter_input(INPUT_POST, 'remove_image', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        $update_china_id = Sanitizer::sanitize(filter_input(INPUT_POST, 'update_china_id', FILTER_SANITIZE_NUMBER_INT));
        $delete_china_id = Sanitizer::sanitize(filter_input(INPUT_POST, 'delete_china_id', FILTER_SANITIZE_NUMBER_INT));
        
        if ($this->path[3] === 'edit' && intval($this->path[4])) {
            $this->pageTitle = 'Edit China';
            $this->content = $this->bindChina((int)$this->path[4], $this->templateEdit);
            return;
        }
        
        if ($new_china_name) {
            $newId = $this->model->createChina($new_china_name, $new_manufacturer);
            $_SESSION['notification']['text'] = 'New China created';
            $_SESSION['notification']['type'] = 'success';
            header('location: /china/' . $newId);
            die();
        }
        
        if ($update_china_id) {
            $this->model->updateChina($update_china_id, $update_china_name, $update_manufacturer, $remove_image);
            $_SESSION['notification']['text'] = 'China updated';
            $_SESSION['notification']['type'] = 'success';
            header('location: /china/' . $update_china_id);
            die();
        }
        
        if ($delete_china_id) {
            $this->model->deleteChina($delete_china_id);
            header('location: /china/manage/');
            die();
        }
        
        if ($this->path[3] === 'list') {
            echo $this->ajaxChinaList();
            die();
        }
        
        $this->content = $this->bindChinaList();
    }
    
    public function ajaxChinaList()
    {
        $china = $this->model->getChinaList();
        
        if (! $china) {
            return json_encode(['data' => false]);
        }
        
        $rows = [];

        foreach ($china as $row) {
            $rows[] = [
                '<a href="/china/' . $row['china_id'] . '" title="View">' . \htmlspecialchars($row['china_name']) . '</a>',
                $row['manufacturer'],
                '<a href="/china/manage/edit/' . $row['china_id'] . '" class="pull-left text-secondary"><i class="fa fa-edit" title="Edit"></i></a> '
                . '<a class="pull-right text-secondary" data-record-id="' . $row['china_id'] . '" data-record-title="' . htmlspecialchars($row['china_name']) . '" data-toggle="modal" data-target="#confirm-delete" href="#"><i class="fa fa-trash" title="Delete"></i></a>'
            ];
        }
        
        return json_encode(['data' => $rows]);
    }
    
    private function bindChinaList()
    {
        $date = date_create(LastUpdated::table('china'));
        $updatedDate = date_format($date, 'g:i A \o\n l jS F Y');
        $content = $this->builder
                ->setTemplate($this->templateList)
                ->addBrackets(['UPDATED_DATE' => $updatedDate])
                ->build();
        
        return $content->result;
    }
    
    private function bindChina($id, $template)
    {
        $china = $this->model->getChina($id);
        $this->pageTitle .= ' <small>' . $china['china_name'] . '</small>';
        $this->china_title = htmlspecialchars($china['china_name']);
        $content = $this->builder
                ->setTemplate($template)
                ->addBrackets([
                    'CHINA_NAME' => $this->china_title,
                    'IMAGE_FILENAME' => $china['image_filename']
                        ? '/images/china/' . $china['image_filename'] . '.jpg'
                        : '/images/placeholders/900x400.png',
                    'MANUFACTURER' => \htmlspecialchars($china['manufacturer']),
                    'CHINA_ID' => $china['china_id'],
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
            'ACTIVE_MANAGE_CHINA' => ' active',
            'NOTIFICATION' => $this->notification,
            'META_TITLE' => $this->china_title,
        ];
    }
}