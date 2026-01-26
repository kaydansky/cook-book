<?php

namespace Cookbook\Helpers;

use Cookbook\DB\Database;

/**
 * Description of TableEmpty
 *
 * @author AlexK
 */
class TableEmpty
{

    static function tableContent($tableName)
    {
        return Database::genericDsn()->select('SELECT id FROM ' . $tableName . ' LIMIT 0, 1');
    }

}
