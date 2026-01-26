<?php

namespace Cookbook\Domain\Forgotpw;

use Cookbook\Helpers\Sanitizer;

/**
 * Description of ForgotpwController
 *
 * @author AlexK
 */
class ForgotpwController
{
    
    protected $templateRequest = 'Forgotpw/request_container.html';
    protected $templateReset = 'Forgotpw/reset_container.html';
    protected $modelName= 'Cookbook\Domain\Users\UsersModel';
    protected $model;
    protected $template;
    protected $builder;
    protected $resolver;
    protected $path = [];
    protected $auth;
    protected $notification;
    protected $selector;
    protected $token;

    public function __construct()
    {
        $this->template = $this->templateRequest;
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
        if ($this->auth->isLoggedIn()) {
            header('location: /users/');
        }
        
        $email = Sanitizer::sanitize(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL));
        $this->selector = Sanitizer::sanitize(filter_input(INPUT_GET, 'selector', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        $this->token = Sanitizer::sanitize(filter_input(INPUT_GET, 'token', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        $selectorPost = Sanitizer::sanitize(filter_input(INPUT_POST, 'selector', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        $tokenPost = Sanitizer::sanitize(filter_input(INPUT_POST, 'token', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        $password = Sanitizer::sanitize(filter_input(INPUT_POST, 'password', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        $password_c = Sanitizer::sanitize(filter_input(INPUT_POST, 'password_c', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        
        if ($email) {
            $this->notification = $this->model->requestPasswordReset($email, 
                'reset_password_subject', 
                'reset_password_body', 
                'reset_password_alt_body') 
                    ?? 'notif.Promo("top", "center", "<span>Email has been sent.<br>Check your inbox for further instructions.</span>", "success");';
        }
        
        if ($this->selector && $this->token) {
            $this->notification = $this->model->verifyPasswordReset($this->selector, $this->token);
            
            if (! $this->notification) {
                $this->template = $this->templateReset;
            }
        }
        
        if (($selectorPost && $tokenPost && $password) && $password === $password_c) {
            $this->notification = $this->model->updatePassword($selectorPost, $tokenPost, $password);
            
            if (! $this->notification) {
                header('location: /login/reset/');
            }
        }
    }
    
    public function output()
    {
        $container = $this->builder->setTemplate($this->template);

        return [
            'CONTAINER' => $container->template,
            'BODY_STYLE' => ' style="background-color: #686868;"',
            'NOTIFICATION' => $this->notification,
            'SELECTOR' => $this->selector,
            'TOKEN' => $this->token,
        ];
    }
    
}
