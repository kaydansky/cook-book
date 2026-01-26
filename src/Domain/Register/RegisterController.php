<?php

namespace Cookbook\Domain\Register;

use Cookbook\Helpers\Sanitizer;

class RegisterController
{
    
    protected $template = 'Register/container.html';
    protected $builder;
    protected $resolver;
    protected $modelName = 'Cookbook\Domain\Users\UsersModel';
    protected $model;
    protected $path = [];
    protected $auth;

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
        if ($this->model->checkTableUsers() !== null) {
            header('location: /login/');
        }
        
        $first_name = Sanitizer::sanitize(filter_input(INPUT_POST, 'first_name', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        $last_name = Sanitizer::sanitize(filter_input(INPUT_POST, 'last_name', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        $email = Sanitizer::sanitize(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL));
        $password = Sanitizer::sanitize(filter_input(INPUT_POST, 'password', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        $password_c = Sanitizer::sanitize(filter_input(INPUT_POST, 'password_c', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        
        if (($first_name && $last_name && $email && $password && $password_c) 
                && $password === $password_c) {
            if ($this->model->createSuperadmin($email, $password, $first_name, $last_name)) {
                header('location: /login/superadmin/');
            }
        }
    }

    public function output()
    {
        $container = $this->builder->setTemplate($this->template);

        return [
            'CONTAINER' => $container->template,
            'BODY_STYLE' => ' style="background-color: #686868;"',
            'NOTIFICATION' => $this->model->notification,
        ];
    }
    
}
