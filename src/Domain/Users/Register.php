<?php

namespace Cookbook\Domain\Users;

use Cookbook\DB\Database;

/**
 * Description of Register
 *
 * @author AlexK
 */
class Register
{
    public function __construct(Database $db)
    {
        $this->db = $db->dsn;
    }
}
