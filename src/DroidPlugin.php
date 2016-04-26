<?php

namespace Droid\Plugin\Mysql;

class DroidPlugin
{
    public function __construct($droid)
    {
        $this->droid = $droid;
    }
    
    public function getCommands()
    {
        $commands = [];
        $commands[] = new \Droid\Plugin\Mysql\Command\MysqlDumpCommand();
        return $commands;
    }
}
