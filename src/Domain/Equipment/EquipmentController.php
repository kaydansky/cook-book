<?php

namespace Cookbook\Domain\Equipment;

use Cookbook\{Helpers\Sanitizer, Helpers\LastUpdated};
use Delight\Auth\Role;

/**
 * Description of EquipmentController
 *
 * @author AlexK
 */
class EquipmentController
{
    private $template = 'Equipment/container.html';
    private $templateList = 'Equipment/equipment_list.html';
    private $templateView = 'Equipment/equipment_view.html';
    private $templateEdit = 'Equipment/equipment_edit.html';
    private $modelName = 'Cookbook\Domain\Equipment\EquipmentModel';
    private $model;
    private $builder;
    private $resolver;
    private $path = [];
    private $auth;
    private $notification;
    private $content;
    private $pageTitle = 'Manage Equipment';
    private $equipment_title = 'Manage Equipment';
    
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
        } else if (intval($this->path[2])) {
            $this->pageTitle = 'Equipment';
            $this->content = $this->bindEquipment((int)$this->path[2], $this->templateView);
            return;
        }
    }
    
    private function manage()
    {
        if (! $this->auth->hasRole(Role::CHEF)) {
            header('location: /');
            die();
        }
        
        $new_equipment = Sanitizer::sanitize(filter_input(INPUT_POST, 'new_equipment', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        $new_manufacturer = Sanitizer::sanitize(filter_input(INPUT_POST, 'new_manufacturer', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        $new_description = Sanitizer::sanitize(filter_input(INPUT_POST, 'new_description', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        $update_equipment = Sanitizer::sanitize(filter_input(INPUT_POST, 'update_equipment', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        $update_manufacturer = Sanitizer::sanitize(filter_input(INPUT_POST, 'update_manufacturer', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        $update_description = Sanitizer::sanitize(filter_input(INPUT_POST, 'update_description', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        $remove_image = Sanitizer::sanitize(filter_input(INPUT_POST, 'remove_image', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        $update_equipment_id = Sanitizer::sanitize(filter_input(INPUT_POST, 'update_equipment_id', FILTER_SANITIZE_NUMBER_INT));
        $delete_equipment_id = Sanitizer::sanitize(filter_input(INPUT_POST, 'delete_equipment_id', FILTER_SANITIZE_NUMBER_INT));
        
        if ($this->path[3] === 'edit' && intval($this->path[4])) {
            $this->pageTitle = 'Edit Equipment';
            $this->content = $this->bindEquipment((int)$this->path[4], $this->templateEdit);
            return;
        }
        
        if ($new_equipment) {
            $newId = $this->model->createEquipment($new_equipment, $new_manufacturer, $new_description);
            $_SESSION['notification']['text'] = 'New Equipment created';
            $_SESSION['notification']['type'] = 'success';
            header('location: /equipment/' . $newId);
            die();
        }
        
        if ($update_equipment_id) {
            $this->model->updateEquipment($update_equipment_id, $update_equipment, $update_manufacturer, $update_description, $remove_image);
            $_SESSION['notification']['text'] = 'Equipment updated';
            $_SESSION['notification']['type'] = 'success';
            header('location: /equipment/' . $update_equipment_id);
            die();
        }
        
        if ($delete_equipment_id) {
            $this->model->deleteEquipment($delete_equipment_id);
            header('location: /equipment/manage/');
            die();
        }
        
        if ($this->path[3] === 'list') {
            echo $this->ajaxEquipmentList();
            die();
        }
        
        $this->content = $this->bindEquipmentList();
    }
    
    public function ajaxEquipmentList()
    {
        $equipment = $this->model->getEquipmentList();
        
        if (! $equipment) {
            return json_encode(['data' => false]);
        }
        
        $rows = [];

        foreach ($equipment as $row) {
            $rows[] = [
                '<a href="/equipment/' . $row['equipment_id'] . '" title="View">' . $row['equipment'] . '</a>',
                $row['description'],
                '<a href="/equipment/manage/edit/' . $row['equipment_id'] . '" class="pull-left text-secondary"><i class="fa fa-edit" title="Edit"></i></a> '
                . '<a class="pull-right text-secondary" data-record-id="' . $row['equipment_id'] . '" data-record-title="' . htmlspecialchars($row['equipment']) . '" data-toggle="modal" data-target="#confirm-delete" href="#"><i class="fa fa-trash" title="Delete"></i></a>'
            ];
        }
        
        return json_encode(['data' => $rows]);
    }
    
    private function bindEquipmentList()
    {
        $date = date_create(LastUpdated::table('china'));
        $updatedDate = date_format($date, 'g:i A \o\n l jS F Y');
        $content = $this->builder
                ->setTemplate($this->templateList)
                ->addBrackets(['UPDATED_DATE' => $updatedDate])
                ->build();
        
        return $content->result;
    }
    
    private function bindEquipment($id, $template)
    {
        $equipment = $this->model->getEquipment($id);
        $this->pageTitle .= ' <small>' . $equipment['equipment'] . '</small>';
        $this->equipment_title = $equipment['equipment'];
        $content = $this->builder
                ->setTemplate($template)
                ->addBrackets([
                    'EQUIPMENT' => $equipment['equipment'],
                    'IMAGE_FILENAME' => $equipment['image_filename']
                        ? '/images/equipment/' . $equipment['image_filename'] . '.jpg'
                        : '/images/placeholders/900x400.png',
                    'MANUFACTURER' => $equipment['supplier'],
                    'DESCRIPTION' => $equipment['description'],
                    'EQUIPMENT_ID' => $equipment['equipment_id'],
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
            'ACTIVE_MANAGE_EQUIPMENT' => ' active',
            'NOTIFICATION' => $this->notification,
            'META_TITLE' => $this->equipment_title,
        ];
    }
}