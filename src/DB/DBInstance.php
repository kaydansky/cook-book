<?php

namespace Cookbook\DB;

use Delight\Db\PdoDatabase;
use Delight\Db\PdoDsn;

/**
 * Description of DBInstance
 *
 * @author AlexK
 */
trait DBInstance
{
    
    static private $genericInstance;
    
    static private $instance;

    static function genericDsn()
    {
        if (empty(static::$genericInstance)) {
            static::$genericInstance = PdoDatabase::fromDsn(
                new PdoDsn(
                    'mysql:dbname=' . DATABASE_DEFAULT . ';host=' . DATABASE_CREDENTIALS['hostname'],
                    DATABASE_CREDENTIALS['username'],
                    DATABASE_CREDENTIALS['password']
                )
            );
        }

        return static::$genericInstance;
    }
    
    static function dsn()
    {
        if (! isset($_SESSION['db_name'])) {
            return null;
        }
        
        if (empty(static::$instance)) {
            static::$instance = PdoDatabase::fromDsn(
                new PdoDsn(
                    'mysql:dbname=' . $_SESSION['db_name'] . ';host=' . DATABASE_CREDENTIALS['hostname'],
                    DATABASE_CREDENTIALS['username'],
                    DATABASE_CREDENTIALS['password']
                )
            );
        }

        return static::$instance;
    }


    private function __construct(){}
    
    private function __clone(){}
    
    public function __wakeup(){}
}
