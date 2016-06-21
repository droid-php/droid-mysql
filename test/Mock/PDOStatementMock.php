<?php

namespace Droid\Test\Plugin\Mysql\Mock;

use \PDOStatement;

/*
 * Work around PDOException: You cannot serialize or unserialize PDOStatement
 * instances
 */
class PDOStatementMock extends PDOStatement
{
    public function __construct()
    {
    }
}
