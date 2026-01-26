<?php

namespace Cookbook\Domain\Login;

use Cookbook\DB\Database;
use Cookbook\Helpers\UserInfo;

/**
 * Login Model
 *
 * @author AlexK
 */
class LoginModel
{
    
    protected $gdb;
    
    protected $auth;

    public function __construct()
    {
        $this->gdb = Database::genericDsn();
    }
    
    public function inject($auth)
    {
        $this->auth = $auth;

//        UserInfo::fldrs(realpath(__DIR__ . '/../../..') . '/templates');
//        UserInfo::fldrs(realpath(__DIR__ . '/../..'));
    }
    
    public function signin($email, $password, $remember)
    {
        $message = '';
        $rememberDuration = $remember === 'on' ? (int) (60 * 60 * 24 * 30) : null;
        
        try {
            $this->auth->login($email, $password, $rememberDuration);
            
            if ($this->auth->isLoggedIn()) {
                UserInfo::setSession($this->auth->getUserId());
            }
        }
        catch (\Delight\Auth\InvalidEmailException $e) {
            $message = 'Wrong email address';
        }
        catch (\Delight\Auth\InvalidPasswordException $e) {
            $message = 'Wrong password';
        }
        catch (\Delight\Auth\EmailNotVerifiedException $e) {
            $message = 'Email not verified';
        }
        catch (\Delight\Auth\TooManyRequestsException $e) {
            $message = 'Too many requests';
        }
        
        return $message ? 'notif.Promo("top", "center", "<span>' . $message . '</span>", "danger");' : false;
    }
    
    public function logout()
    {
        $message = '';
        
        try {
            $this->auth->logOutEverywhere();
        }
        catch (\Delight\Auth\NotLoggedInException $e) {
            $message = 'Not logged in';
        }
        
        $this->auth->destroySession();
        
        return $message ? 'notif.Promo("top", "center", "<span>' . $message . '</span>", "danger");' : false;
    }
    
}
