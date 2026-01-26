<?php

namespace Cookbook\Router;

use Cookbook\{Output\OutputBuilder, DI\DiResolver, DB\Database, Helpers\UserInfo, Domain\Categories\CategoryListFactory};
use Delight\Auth\{Auth, Role};

class Router
{
    private $builder;
    private $resolver;
    private $auth;
    private $pathStart = 0;
    private $namespace = 'Cookbook\Domain\\';
    private $defaultPage = 'Home\HomeController';
    private $loginPage = 'Login\LoginController';
    private $template = 'index.html';
    private $publicPages = [null, 'Login', 'Register', 'Forgotpw'];
    private $placeholders = [];
    private $superAdminAllowed = ['users', 'login', 'ingredient', 'dietary'];
    private $templateSearchUi = 'Search/search_ui.html';
    
    public $path = [];

    public function __construct(OutputBuilder $builder, DiResolver $resolver)
    {
        $this->builder = $builder;
        $this->resolver = $resolver;
        $this->placeholders = require PATH_TEMPLATES_CONFIG . 'config.php';
        $this->auth = new Auth(Database::genericDsn());
    }

    public function request()
    {
        $className = null;
        $class = null;

        $uri = filter_input(INPUT_GET, 'uri', FILTER_SANITIZE_FULL_SPECIAL_CHARS) . '/';

        if (isset($uri)) {
            $this->path = explode('/', (string) $uri);
            
            if ($this->pathStart < 1)  {
                array_unshift($this->path, '');
            }

            $className = ucfirst($this->path[1]);
            $class = $this->namespace . $className . '\\' . $className . 'Controller';
        }

        if ($this->auth->isLoggedIn()
            && $this->auth->hasRole(Role::SUPER_ADMIN)
            && ! in_array($this->path[1], $this->superAdminAllowed)) {
            header('location: /users/');
            die();
        }

        if (empty($className)) {
            $class = $this->namespace . $this->defaultPage;
        } elseif (! class_exists($class)) {
            $this->notFound();
        }

        if (! in_array($className, $this->publicPages)) {
            if (! $this->auth->isLoggedIn()) {
                $class = $this->namespace . $this->loginPage;
            } else {
                if (! isset($_SESSION['db_name'])) {
                    UserInfo::setSession($this->auth->getUserId());
                }
            }
        }

        $controller = $this->resolver->resolve($class);
        
        if (method_exists($controller, 'inject')) {
            $controller->inject($this->path, $this->auth, $this->builder, $this->resolver);
        }
        
        if (method_exists($controller, 'action')) {
            $controller->action();
        }
        
        return $controller->output();
    }

    public function response($output)
    {
        $aPath = array_filter($this->path);
        $key = $this->pathStart - 1;
        unset($aPath[$key]);
        $path = implode('-', $aPath);
        $output[$path] = ' active';

        if ($this->auth->isLoggedIn()) {
            if (! isset($_SESSION['user_info'])) {
                UserInfo::setSession($this->auth->getUserId());
            }

            if ($this->auth->hasRole(Role::SUPER_ADMIN)) {
                $templateNavigation = 'Navbars/Superadmin.html';
            } elseif ($this->auth->hasRole(Role::CHEF)) {
                $templateNavigation = 'Navbars/Chef.html';
            } elseif ($this->auth->hasRole(Role::COOK)) {
                $templateNavigation = 'Navbars/Cook.html';
            } else {
                $templateNavigation = 'Navbars/Service.html';
            }

            $output['LOGGED_ROLE'] = ucwords(strtolower(str_replace('_', ' ', $_SESSION['user_info']['role'])));
            $output['LOGGED_FULL_NAME'] = $_SESSION['user_info']['first_name'] . '&nbsp;' . $_SESSION['user_info']['last_name'];

            if (! $this->auth->hasRole(Role::SUPER_ADMIN)) {
                $output['CATEGORY_BADGES'] = (new CategoryListFactory())->badgePacker($this->builder)->pack();
                $output['SEARCH_UI'] = $this->builder->setTemplate($this->templateSearchUi)->template;
            }
        } else {
            $templateNavigation = 'Navbars/Home.html';
        }

        $output['NAVIGATION'] = $this->builder
            ->setTemplate($templateNavigation)
            ->addBrackets(array_replace($this->placeholders, $output))->build()->result;

        $page = $this->builder
                ->setTemplate($this->template)
                ->addBrackets(array_replace($this->placeholders, $output))
                ->build();

        die($page->result);
    }

    private function notFound()
    {
        http_response_code(404);
        die();
    }
}
