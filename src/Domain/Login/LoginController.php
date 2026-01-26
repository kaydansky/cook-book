<?php

namespace Cookbook\Domain\Login;

use Cookbook\{Helpers\TableEmpty, Helpers\Sanitizer};

/**
 * Login Controller
 *
 * @author AlexK
 */
class LoginController
{
    
    protected $templateLogin = 'Login/container.html';
    protected $templateRegister = 'Register/container.html';
    protected $modelName = 'Cookbook\Domain\Login\LoginModel';
    protected $model;
    protected $template;
    protected $builder;
    protected $resolver;
    protected $notification;
    protected $path = [1,2,3];
    protected $auth;

    public function __construct()
    {
        $this->template = TableEmpty::tableContent('users') === null
                ? $this->templateRegister 
                : $this->templateLogin;
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
        $email = Sanitizer::sanitize(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL));
        $password = Sanitizer::sanitize(filter_input(INPUT_POST, 'password', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        $remember = Sanitizer::sanitize(filter_input(INPUT_POST, 'remember'));
        
        if ($email && $password) {
            $this->notification = $this->model->signin($email, $password, $remember);
            
            if (! $this->notification) {
                header('location: /recipe/');
            }
        }
        
        if ($this->path[2] === 'logout') {
            $this->notification = $this->model->logout();
            
            if (! $this->notification) {
                header('location: /?loggedout=true');
            }
        }
        
        if ($this->path[2] === 'superadmin') {
            $this->notification = 'notif.Promo("top", "center", "<span>'
                    . 'You are registered as SuperAdmin. '
                    . 'This is one time action allowed if there is no user in '
                    . 'database only. Please sign in.</span>", "success");';
        }
        
        if ($this->path[2] === 'reset') {
            $this->notification = 'notif.Promo("top", "center", "<span>Password has been reset.<br>Please sign in.</span>", "success");';
        }
    }

    public function output()
    {
        $container = $this->builder->setTemplate($this->template);

        return [
            'CONTAINER' => $container->template,
            'BODY_STYLE' => ' style="background-color: #686868;"',
            'NOTIFICATION' => $this->notification,
            'QUERY' => '',
        ];
    }
    
}
