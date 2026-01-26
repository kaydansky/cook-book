<?php

use Cookbook\DI\DiResolver;

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../config/config.php';

//csrfProtector::init();

$route = (new DiResolver)->resolve('Cookbook\Router\Router');
$route->response($route->request());
