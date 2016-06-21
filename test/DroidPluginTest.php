<?php

namespace Droid\Test\Plugin\Mysql;

use Droid\Plugin\Mysql\DroidPlugin;

class DroidPluginTest extends \PHPUnit_Framework_TestCase
{
    protected $plugin;

    protected function setUp()
    {
        $this->plugin = new DroidPlugin('droid');
    }

    public function testGetCommandsReturnsAllCommands()
    {
        $this->assertSame(
            array(
                'Droid\Plugin\Mysql\Command\MysqlAdduserCommand',
                'Droid\Plugin\Mysql\Command\MysqlDeluserCommand',
                'Droid\Plugin\Mysql\Command\MysqlDumpCommand',
                'Droid\Plugin\Mysql\Command\MysqlDumpAllCommand',
                'Droid\Plugin\Mysql\Command\MysqlLoadCommand',
            ),
            array_map(
                function ($x) {
                    return get_class($x);
                },
                $this->plugin->getCommands()
            )
        );
    }
}
