<?php
/**
 * @author: AlexK
 * Date: 03-Apr-19
 * Time: 3:30 PM
 */

namespace Cookbook\Domain\Dietary;

use Cookbook\{Helpers\ListOptions, Helpers\Sanitizer, Helpers\LastUpdated};
use Delight\Auth\Role;

class DietaryController
{
    private $template = 'Dietary/container.html';
    private $templateList = 'Dietary/restriction_list.html';
    private $templateEdit = 'Dietary/restriction_edit.html';
    private $modelName = 'Cookbook\Domain\Dietary\DietaryModel';
    private $model;
    private $builder;
    private $resolver;
    private $path = [];
    private $auth;
    private $notification;
    private $content;
    private $pageTitle = 'Manage Dietary Restrictions';
    private $restriction_name = 'Manage Dietary Restrictions';

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
        if (! $this->auth->hasRole(Role::SUPER_ADMIN)) {
            header('location: /');
            die();
        }

        $new_restriction_name = Sanitizer::sanitize(filter_input(INPUT_POST, 'new_restriction_name', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        $new_description = Sanitizer::sanitize(filter_input(INPUT_POST, 'new_description', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        $new_type = Sanitizer::sanitize(filter_input(INPUT_POST, 'new_type', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        $update_restriction_name = Sanitizer::sanitize(filter_input(INPUT_POST, 'update_restriction_name', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        $update_description = Sanitizer::sanitize(filter_input(INPUT_POST, 'update_description', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        $update_type = Sanitizer::sanitize(filter_input(INPUT_POST, 'update_type', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        $update_restriction_id = Sanitizer::sanitize(filter_input(INPUT_POST, 'update_restriction_id', FILTER_SANITIZE_NUMBER_INT));
        $delete_restriction_id = Sanitizer::sanitize(filter_input(INPUT_POST, 'delete_restriction_id', FILTER_SANITIZE_NUMBER_INT));

        if ($this->path[3] === 'edit' && intval($this->path[4])) {
            $this->pageTitle = 'Edit Dietery Restriction';
            $this->content = $this->bindRestriction((int)$this->path[4]);
            return;
        }

        if ($new_restriction_name) {
            if ($this->model->createRestriction($new_restriction_name, $new_description, $new_type)) {
                $_SESSION['notification']['text'] = 'New Restriction created';
                $_SESSION['notification']['type'] = 'success';
                header('location: /dietary/manage/');
                die();
            }
        }

        if ($update_restriction_id) {
            if ($this->model->updateRestriction($update_restriction_id, $update_restriction_name, $update_description, $update_type)) {
                $_SESSION['notification']['text'] = 'Restriction updated';
                $_SESSION['notification']['type'] = 'success';
                header('location: /dietary/manage/');
                die();
            }
        }

        if ($delete_restriction_id) {
            die($this->model->deleteRestriction($delete_restriction_id));
        }

        if ($this->path[3] === 'list') {
            die($this->ajaxRestrictionList());
        }

        $this->content = $this->bindRestrictionList();
    }

    public function ajaxRestrictionList()
    {
        $restrictions = $this->model->getRestrictions();

        if (! $restrictions) {
            return json_encode(['data' => false]);
        }

        $rows = [];

        foreach ($restrictions as $row) {
            $description = $row['description'] ? preg_replace('/[ \t]+/', ' ', preg_replace('/\s*$^\s*/m', "\n", $row['description'])) : '';

            $rows[] = [
                $row['restriction_name'],
                '<a class="plus-cursor" data-toggle="popover" data-content="' . $description . '">' . substr($description, 0, 40) . '</a>...',
                $row['type'],
                '<a href="/dietary/manage/edit/' . $row['dietary_restriction_id'] . '" class="pull-left text-secondary"><i class="fa fa-edit" title="Edit"></i></a> '
                . '<a class="pull-right text-secondary" data-record-id="' . $row['dietary_restriction_id'] . '" data-record-title="' . htmlspecialchars($row['restriction_name']) . '" data-toggle="modal" data-target="#confirm-delete" href="#"><i class="fa fa-trash" title="Delete"></i></a>'
            ];
        }

        return json_encode(['data' => $rows]);
    }

    private function bindRestrictionList()
    {
        $date = date_create(LastUpdated::table('china'));
        $updatedDate = date_format($date, 'g:i A \o\n l jS F Y');
        $content = $this->builder
            ->setTemplate($this->templateList)
            ->addBrackets(['UPDATED_DATE' => $updatedDate])
            ->build();

        return $content->result;
    }

    private function bindRestriction($id)
    {
        $restriction = $this->model->getRestriction($id);
        $this->pageTitle .= ' <small>' . $restriction['restriction_name'] . '</small>';
        $this->restriction_name = $restriction['restriction_name'];
        $content = $this->builder
            ->setTemplate($this->templateEdit)
            ->addBrackets([
                'RESTRICTION_NAME' => $restriction['restriction_name'],
                'DESCRIPTION' => $restriction['description'],
                'TYPE' => ListOptions::restrictionType([$restriction['type']]),
                'RESTRICTION_ID' => $restriction['dietary_restriction_id'],
                'BTN_EDIT_DISPALY' => $this->auth->hasRole(Role::SUPER_ADMIN)
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
            'TYPE_OPTIONS' => ListOptions::restrictionType(),
            'META_TITLE' => $this->restriction_name,
        ];
    }
}