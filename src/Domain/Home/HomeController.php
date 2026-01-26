<?php

namespace Cookbook\Domain\Home;

/**
 * Description of Home
 *
 * @author AlexK
 */
class HomeController
{

    protected $template = 'Home/container.html';
    protected $modelName = 'Cookbook\Domain\Home\HomeModel';
    protected $model;
    protected $builder;
    protected $resolver;
    protected $notification;
    protected $path = [];
    protected $auth;

    public function inject($path, $auth, $builder, $resolver)
    {
        $this->path = array_replace($this->path, $path);
        $this->auth = $auth;
        $this->builder = $builder;
        $this->resolver = $resolver;
//        $this->model = $this->resolver->resolve($this->modelName);
    }

    public function action()
    {
        $loggedout = filter_input(INPUT_GET, 'loggedout');

        if ($loggedout) {
            // alert types: 'info', 'success', 'warning', 'danger', 'rose', 'primary'
            $this->notification = 'notif.Promo("top", "center", "<span>You are logged out</span>", "success");';
        }
    }

    public function output()
    {
        $container = $this->builder->setTemplate($this->template);

        return [
            'CONTAINER' => $container->template,
            'ACTIVE_HOME' => ' active',
            'SIGN_IN_OUT' => $this->auth->isLoggedIn() ? '<a class="btn btn-primary btn-lg" href="/login/logout/">Sign Out</a>' : '<a class="btn btn-primary btn-lg" href="/login/">Sign In</a>',
            'NOTIFICATION' => $this->notification,
            'QUERY' => '',
        ];
    }

}
