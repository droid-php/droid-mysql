<?php

namespace Droid\Plugin\Mysql;

use Droid\Plugin\Mysql\Command\MysqlAdduserCommand;
use Droid\Plugin\Mysql\Command\MysqlDeluserCommand;
use Droid\Plugin\Mysql\Command\MysqlMasterInfoCommand;
use Droid\Plugin\Mysql\Db\Client;
use Droid\Plugin\Mysql\Db\Config;
use Droid\Plugin\Mysql\Db\ConnectionFactory;

class DroidPlugin
{
    public function __construct($droid)
    {
        $this->droid = $droid;
    }

    public function getCommands()
    {
        $commands = [];
        $commands[] = new MysqlAdduserCommand($this->buildClient());
        $commands[] = new MysqlDeluserCommand($this->buildClient());
        $commands[] = new MysqlMasterInfoCommand($this->buildClient());
        $commands[] = new \Droid\Plugin\Mysql\Command\MysqlDumpCommand();
        $commands[] = new \Droid\Plugin\Mysql\Command\MysqlDumpAllCommand();
        $commands[] = new \Droid\Plugin\Mysql\Command\MysqlLoadCommand();
        return $commands;
    }

    private function buildClient()
    {
        return new Client(new Config, new ConnectionFactory);
    }
}
