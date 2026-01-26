<?php

namespace Cookbook\Helpers;

use Cookbook\DB\Database;

/**
 * Description of LastUpdated
 *
 * @author AlexK
 */
class LastUpdated
{
    static function table($table)
    {
        if (! isset($_SESSION['db_name'])) {
            return '1970-01-01 00:00:00';
        }

        $a = Database::genericDsn()->selectValue('SELECT UPDATE_TIME FROM information_schema.tables WHERE TABLE_SCHEMA = \'' . $_SESSION['db_name'] . '\' AND TABLE_NAME = \'' . $table . '\'');
        
        if (! $a) {
            return '1970-01-01 00:00:00';
        }
        
        return $a;
    }
}
