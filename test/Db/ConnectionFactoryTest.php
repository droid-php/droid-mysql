<?php

namespace Droid\Test\Plugin\Mysql\Db;

use Droid\Plugin\Mysql\Db\Config;
use Droid\Plugin\Mysql\Db\ConnectionFactory;

class ConnectionFactoryTest extends \PHPUnit_Framework_TestCase
{
    protected $config;
    protected $fac;

    protected function setUp()
    {
        $this->config = $this
            ->getMockBuilder(Config::class)
            ->getMock()
        ;
        $this->fac = new ConnectionFactory;
        $this->fac->setConfig($this->config);
    }

    public function testGetConnectionParams()
    {
        $this
            ->config
            ->expects($this->at(0))
            ->method('getDsn')
            ->willReturn('some_dsn')
        ;
        $this
            ->config
            ->expects($this->at(1))
            ->method('getUserName')
            ->willReturn('some_name')
        ;
        $this
            ->config
            ->expects($this->at(2))
            ->method('getUserPassword')
            ->willReturn('some_password')
        ;
        $this->assertSame(
            array('some_dsn', 'some_name', 'some_password'),
            $this->fac->getConnectionParams()
        );
    }
}
