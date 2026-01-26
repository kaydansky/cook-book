<?php

namespace Cookbook\Domain\Users;

use Cookbook\{DB\Database, Helpers\TableEmpty, Emailer\Emailer};
use Delight\Auth\EmailNotVerifiedException;
use Delight\Auth\InvalidEmailException;
use Delight\Auth\InvalidPasswordException;
use Delight\Auth\InvalidSelectorTokenPairException;
use Delight\Auth\NotLoggedInException;
use Delight\Auth\ResetDisabledException;
use Delight\Auth\Role;
use Delight\Auth\TokenExpiredException;
use Delight\Auth\TooManyRequestsException;
use Delight\Auth\UnknownIdException;
use Delight\Auth\UserAlreadyExistsException;
use RecursiveArrayIterator;
use RecursiveIteratorIterator;

/**
 * Description of UsersModel
 *
 * @author AlexK
 */
class UsersModel
{
    private $emailer;
    private $gdb;
    private $db;
    private $auth;
    private $selector;
    private $token;

    public $notification;

    public function __construct(Emailer $emailer)
    {
        $this->emailer = $emailer;
        $this->gdb = Database::genericDsn();
        $this->db = Database::dsn();
    }

    public function inject($auth)
    {
        $this->auth = $auth;
    }

    public function checkTableUsers()
    {
        return TableEmpty::tableContent('users');
    }

    public function createNewUser($first_name, $last_name, $email, $level, $db_name = null)
    {   
        if (! $db_name) {
            $db_name = $_SESSION['db_name'];
        }
        
        switch ((int)$level) {
            case 1:
                $role = Role::CHEF;
                break;
            case 2:
                $role = Role::COOK;
                break;
            default:
                $role = Role::SERVICE;
                break;
        }

        $userId = $this->registerUser($email, bin2hex(random_bytes(16)), $role);
        
        if (! $userId) {
            return $this->notification;
        }

        $this->gdb->insert(
            'accounts', [
                'user_id' => $userId,
                'first_name' => $first_name,
                'last_name' => $last_name,
                'db_name' => $db_name
            ]
        );
        
        $this->requestPasswordReset($email, 'new_user_subject', 'new_user_body', 'new_user_alt_body');

        return null;
    }

    public function createSuperadmin($email, $password, $first_name, $last_name)
    {
        if ($this->checkTableUsers() !== null) {
            die('Users table is busy');
        }
        
        try {
            $userId = $this->auth->admin()->createUser($email, $password, null);
        } catch (InvalidEmailException $e) {
            die('Invalid email address');
        } catch (InvalidPasswordException $e) {
            die('Invalid password');
        } catch (UserAlreadyExistsException $e) {
            die('User already exists');
        }

        try {
            $this->auth->admin()->addRoleForUserById($userId, Role::SUPER_ADMIN);
        } catch (UnknownIdException $e) {
            die('Unknown user ID');
        }
        
        $this->gdb->insert(
            'accounts', [
                'user_id' => $userId,
                'first_name' => $first_name,
                'last_name' => $last_name,
                'db_name' => DATABASE_DEFAULT
            ]
        );

        return $userId;
    }

    private function registerUser($email, $password, $role)
    {
        $error = '';
        $userId = 0;

        try {
            $userId = $this->auth->register($email, $password, null);
        } catch (InvalidEmailException $e) {
            $error = 'Invalid email address. ';
        } catch (InvalidPasswordException $e) {
            $error = 'Invalid password. ';
        } catch (UserAlreadyExistsException $e) {
            $error = 'User already exists. ';
        } catch (TooManyRequestsException $e) {
            $error = 'Too many requests. ';
        }

        try {
            $this->auth->admin()->addRoleForUserById($userId, $role);
        } catch (UnknownIdException $e) {
            $error .= 'Unknown user ID';
        }

        if ($error) {
            $this->notification = 'notif.Promo("top", "center", "<span>' . $error . '</span>", "danger");';
            return null;
        }

        return $userId;
    }

    public function requestPasswordReset($email, $subject, $body, $altBody)
    {
        $error = '';

        try {
            $this->auth->forgotPassword($email, function ($selector, $token) {
                $this->selector = $selector;
                $this->token = $token;
            });
            
            $url = $_SERVER['HTTP_ORIGIN'] . '/forgotpw/?selector=' . urlencode($this->selector) . '&token=' . urlencode($this->token);
            
            $this->emailer->to = $email;
            $this->emailer->subject = EMAIL_CONTENT[$subject];
            $this->emailer->body = str_replace('%URL%', $url, EMAIL_CONTENT[$body]);
            $this->emailer->altBody = str_replace('%URL%', $url, EMAIL_CONTENT[$altBody]);
            return $this->emailer->send();
        } catch (InvalidEmailException $e) {
            $error = 'Invalid email address';
        } catch (EmailNotVerifiedException $e) {
            $error = 'Email not verified';
        } catch (ResetDisabledException $e) {
            $error = 'Password reset is disabled';
        } catch (TooManyRequestsException $e) {
            $error = 'Too many requests';
        }

        return $error ? 'notif.Promo("top", "center", "<span>' . $error . '</span>", "danger");' : null;
    }

    public function verifyPasswordReset($selector, $token)
    {
        $error = '';

        try {
            $this->auth->canResetPasswordOrThrow($selector, $token);
        } catch (InvalidSelectorTokenPairException $e) {
            $error = 'Invalid token';
        } catch (TokenExpiredException $e) {
            $error = 'Token expired';
        } catch (ResetDisabledException $e) {
            $error = 'Password reset is disabled';
        } catch (TooManyRequestsException $e) {
            $error = 'Too many requests';
        }

        return $error ? 'notif.Promo("top", "center", "<span>' . $error . '</span>", "danger");' : null;
    }

    public function updatePassword($selector, $token, $password)
    {
        $error = '';

        try {
            $this->auth->resetPassword($selector, $token, $password);
        } catch (InvalidSelectorTokenPairException $e) {
            $error = 'Invalid token';
        } catch (TokenExpiredException $e) {
            $error = 'Token expired';
        } catch (ResetDisabledException $e) {
            $error = 'Password reset is disabled';
        } catch (InvalidPasswordException $e) {
            $error = 'Invalid password';
        } catch (TooManyRequestsException $e) {
            $error = 'Too many requests';
        }

        return $error ? 'notif.Promo("top", "center", "<span>' . $error . '</span>", "danger");' : null;
    }

    public function changeCurrentPassword($password_current, $password, $subject, $body, $altBody)
    {
        $error = '';

        try {
            $this->auth->changePassword($password_current, $password);
            $this->emailer->to = $this->auth->getEmail();
            $this->emailer->subject = EMAIL_CONTENT[$subject];
            $this->emailer->body = EMAIL_CONTENT[$body];
            $this->emailer->altBody = EMAIL_CONTENT[$altBody];
            return $this->emailer->send();
        } catch (NotLoggedInException $e) {
            $error = 'Not logged in';
        } catch (InvalidPasswordException $e) {
            $error = 'Invalid password(s)';
        } catch (TooManyRequestsException $e) {
            $error = 'Too many requests';
        }

        return $error ? 'notif.Promo("top", "center", "<span>' . $error . '</span>", "danger");' : null;
    }

    public function getDatabaseList()
    {
        $r = $this->gdb->select('show DATABASES LIKE \'' . DATABASE_DEFAULT . '%\'');
        $it = new RecursiveIteratorIterator(new RecursiveArrayIterator($r));
        $a = [];

        foreach ($it as $v) {
            if ($v != DATABASE_DEFAULT) {
                $a[] = $v;
            }
        }
        
        return $a;
    }
    
    public function getUserList()
    {   
        return $this->auth->hasRole(Role::SUPER_ADMIN)
                ? $this->gdb->select('SELECT * FROM accounts t1 RIGHT JOIN users t2 ON t1.user_id = t2.id WHERE t2.id != ?',
                        [
                            $this->auth->getUserId()
                        ])
                : $this->gdb->select('SELECT * FROM accounts t1 RIGHT JOIN users t2 ON t1.user_id = t2.id WHERE t1.db_name = ? AND t2.id != ?',
                        [
                            $_SESSION['db_name'],
                            $this->auth->getUserId()
                        ]);
    }
    
    public function deleteUser($id)
    {
        try {
            $this->auth->admin()->deleteUserById($id);
            $this->gdb->delete('accounts', ['user_id' => $id]);
        }
        catch (UnknownIdException $e) {
            die('Unknown ID');
        }
    }

    public function getUserById($userId)
    {
        return $this->gdb->selectRow('SELECT * FROM accounts WHERE user_id = ?', [$userId]);
    }
}