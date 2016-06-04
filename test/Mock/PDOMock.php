<?php

namespace Droid\Test\Plugin\Mysql\Mock;

use \PDO;

/*
 * Work around PDOException: You cannot serialize or unserialize PDO instances
 */
class PDOMock extends PDO
{
    public function __construct()
    {
    }
}
