<?php

namespace Cookbook\Domain\Users;

use Cookbook\Helpers\Sanitizer;
use Cookbook\Helpers\LastUpdated;
use Delight\Auth\Role;

/**
 * Users Controller
 *
 * @author AlexK
 */
class UsersController
{
    private $template = 'Users/container.html';
    private $modelName = 'Cookbook\Domain\Users\UsersModel';
    private $model;
    private $createUserSuperadminTemplate = 'Users/create_user_superadmin.html';
    private $createUserTemplate = 'Users/create_user.html';
    private $userListTemplate = 'Users/user_list.html';
    private $userListHeaderSuperadminTemplate = 'Users/user_list_header_superadmin.html';
    private $userListRowSuperadminTemplate = 'Users/user_list_row_superadmin.html';
    private $userListHeaderTemplate = 'Users/user_list_header.html';
    private $userListRowTemplate = 'Users/user_list_row.html';
    private $noUserPlaceholderTemplate = 'Users/no_user_placeholder.html';
    private $builder;
    private $resolver;
    private $path = [];
    private $auth;
    private $notification;
    private $createUserWidget;
    private $userList;

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
        $first_name = Sanitizer::sanitize(filter_input(INPUT_POST, 'first_name', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        $last_name = Sanitizer::sanitize(filter_input(INPUT_POST, 'last_name', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        $email = Sanitizer::sanitize(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL));
        $level = Sanitizer::sanitize(filter_input(INPUT_POST, 'level', FILTER_SANITIZE_NUMBER_INT));
        $db_name = Sanitizer::sanitize(filter_input(INPUT_POST, 'db_name', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        
        $password_current = Sanitizer::sanitize(filter_input(INPUT_POST, 'password_current', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        $password = Sanitizer::sanitize(filter_input(INPUT_POST, 'password', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        $password_c = Sanitizer::sanitize(filter_input(INPUT_POST, 'password_c', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        
        $delete_user_id = Sanitizer::sanitize(filter_input(INPUT_POST, 'delete_user_id', FILTER_SANITIZE_NUMBER_INT));
        
        if (($password_current && $password && $password_c) && $password === $password_c) {
            $this->notification = $this->model->changeCurrentPassword(
                    $password_current, 
                    $password, 
                    'change_password_subject', 
                    'change_password_body', 
                    'change_password_alt_body');
            
            if (! $this->notification) {
                $this->auth->logOut();
                header('location: /login/reset/');
            }
        }
        
        if ($first_name && $last_name && $email && $level) {
            $this->notification = $this->model->createNewUser($first_name, $last_name, $email, $level, $db_name);

            if (! $this->notification) {
                header('location: /users/user_created/');
            }
        }
        
        if ($this->path[2] === 'user_created') {
            $this->notification = 'notif.Promo("top", "center", "<span>User has been created.<br>Invitation sent to their email.</span>", "success");';
        }
        
        if ($this->auth->hasRole(Role::SUPER_ADMIN)) {
            $this->createUserWidget = $this->widgetCreateUserSuperadmin();
        } elseif ($this->auth->hasRole(Role::CHEF)) {
            $this->createUserWidget = $this->widgetCreateUser();
        }
        
        if ($delete_user_id && $this->auth->hasAnyRole(Role::SUPER_ADMIN, Role::CHEF)) {
            $this->model->deleteUser($delete_user_id);
            die();
        }
        
        $this->userList = $this->userList();
    }
    
    public function widgetCreateUserSuperadmin()
    {
        $options = '';
        $aDatabases = $this->model->getDatabaseList();
        
        foreach ($aDatabases as $value) {
            $options .= "<option value=\"$value\">$value</option>";
        }
        
        $widget = $this->builder
                ->setTemplate($this->createUserSuperadminTemplate)
                ->addBrackets(['DB_NAME_OPTIONS' => $options])
                ->build();

        return $widget->result;
    }
    
    public function widgetCreateUser()
    {
        $widget = $this->builder
                ->setTemplate($this->createUserTemplate)
                ->build();

        return $widget->result;
    }
    
    public function userList()
    {
        $rows = '';
        $roles = Role::getMap();
        $date = date_create(LastUpdated::table('accounts'));
        $updatedDate = date_format($date, 'g:i A \o\n l jS F Y');

        $users = $this->model->getUserList();
        
        if (! $users) {
            return $this->userListPlaceholder();
        }
        
        if ($this->auth->hasRole(Role::SUPER_ADMIN)) {
            $headerTemplate = $this->userListHeaderSuperadminTemplate;
            $rowTemplate = $this->userListRowSuperadminTemplate;
        } elseif ($this->auth->hasRole(Role::CHEF)) {
            $headerTemplate = $this->userListHeaderTemplate;
            $rowTemplate = $this->userListRowTemplate;
        } else {
            return $this->userListPlaceholder();
        }
        
        foreach ($users as $row) {
            $build = $this->builder
                    ->setTemplate($rowTemplate)
                    ->addBrackets([
                        'FIRST_NAME' => $row['first_name'],
                        'LAST_NAME' => $row['last_name'],
                        'EMAIL' => $row['email'],
                        'ROLE' => ucfirst(strtolower($roles[$row['roles_mask']])),
                        'DATABASE' => $row['db_name'],
                        'USER_ID' => $row['user_id']
                    ])
                    ->build();
            
            $rows .= $build->result;
        }
        
        $header = $this->builder->setTemplate($headerTemplate)->build();
        
        $userList = $this->builder
                ->setTemplate($this->userListTemplate)
                ->addBrackets([
                    'USER_LIST_HEADER' => $header->result,
                    'USER_LIST_ROWS' => $rows,
                    'UPDATED_DATE' => $updatedDate
                ])
                ->build();
        
        return $userList->result;
    }
    
    public function userListPlaceholder()
    {
        $btnDisplay = $this->auth->hasAnyRole(Role::SUPER_ADMIN, Role::CHEF)
                ? 'd-block'
                : 'd-none';

        $placeholder = $this->builder
                ->setTemplate($this->noUserPlaceholderTemplate)
                ->addBrackets(['ADD_USER_BTN_DISPLAY' => $btnDisplay])
                ->build();

        return $placeholder->result;
    }

    public function output()
    {
        $container = $this->builder->setTemplate($this->template);

        return [
            'CONTAINER' => $container->template,
            'ACTIVE_MANAGE_USERS' => ' active',
            'NOTIFICATION' => $this->notification,
            'CREATE_USER' => $this->createUserWidget,
            'USER_LIST' => $this->userList,
            'META_TITLE' => 'Manage Users',
        ];
    }
}