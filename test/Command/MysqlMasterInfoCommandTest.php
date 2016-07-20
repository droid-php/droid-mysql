<?php

namespace Droid\Test\Plugin\Mysql\Command;

use RuntimeException;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

use Droid\Plugin\Mysql\Command\MysqlMasterInfoCommand;
use Droid\Plugin\Mysql\Db\Client;
use Droid\Plugin\Mysql\Db\ClientException;
use Droid\Plugin\Mysql\Db\Config;

class MysqlMasterInfoCommandTest extends \PHPUnit_Framework_TestCase
{
    protected $client;
    protected $config;

    protected function setUp()
    {
        $this->config = $this
            ->getMockBuilder(Config::class)
            ->getMock()
        ;
        $this->client = $this
            ->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $this
            ->client
            ->method('getConfig')
            ->willReturn($this->config)
        ;

        $command = new MysqlMasterInfoCommand($this->client);

        $this->app = new Application;
        $this->app->add($command);

        $this->tester = new CommandTester($command);
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage You must specify both (or neither) --log-name and --log-position
     */
    public function testCommandThrowsRuntimeExceptionWhenLogNameWithoutLogPos()
    {
        $this->tester->execute(array(
            'command' => $this->app->find('mysql:master-info'),
            'url' => 'mysql://db_user:passw0rd@db_host/',
            'master_hostname' => '203.0.113.0',
            'replication_username' => 'repln_user',
            'replication_password' => 's3kr3t',
            '--log-name' => 'mysql-bin.log',
        ));
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage You must specify both (or neither) --log-name and --log-position
     */
    public function testCommandThrowsRuntimeExceptionWhenLogPosWithoutLogName()
    {
        $this->tester->execute(array(
            'command' => $this->app->find('mysql:master-info'),
            'url' => 'mysql://db_user:passw0rd@db_host/',
            'master_hostname' => '203.0.113.0',
            'replication_username' => 'repln_user',
            'replication_password' => 's3kr3t',
            '--log-position' => 174,
        ));
    }

    public function testCommandConfiguresClientWithConnectionUrl()
    {
        $this
            ->config
            ->expects($this->once())
            ->method('setConnectionUrl')
            ->with('mysql://db_user:passw0rd@db_host/')
        ;
        $this
            ->client
            ->method('execute')
            ->willReturn(true)
        ;

        $this->tester->execute(array(
            'command' => $this->app->find('mysql:master-info'),
            'url' => 'mysql://db_user:passw0rd@db_host/',
            'master_hostname' => '203.0.113.0',
            'replication_username' => 'repln_user',
            'replication_password' => 's3kr3t',
        ));
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage I cannot execute the CHANGE MASTER query
     */
    public function testCommandThrowsRuntimeExceptionWhenQueryFailsToExecute()
    {
        $this
            ->config
            ->expects($this->once())
            ->method('setConnectionUrl')
            ->with('mysql://db_user:passw0rd@db_host/')
        ;
        $this
            ->client
            ->method('execute')
            ->willThrowException(new ClientException)
        ;

        $this->tester->execute(array(
            'command' => $this->app->find('mysql:master-info'),
            'url' => 'mysql://db_user:passw0rd@db_host/',
            'master_hostname' => '203.0.113.0',
            'replication_username' => 'repln_user',
            'replication_password' => 's3kr3t',
        ));
    }

    public function testCommandReportsThatItFailedToCompleteSuccessfully()
    {
        $this
            ->config
            ->expects($this->once())
            ->method('setConnectionUrl')
            ->with('mysql://db_user:passw0rd@db_host/')
        ;
        $this
            ->client
            ->method('execute')
            ->willReturn(false)
        ;

        $this->tester->execute(array(
            'command' => $this->app->find('mysql:master-info'),
            'url' => 'mysql://db_user:passw0rd@db_host/',
            'master_hostname' => '203.0.113.0',
            'replication_username' => 'repln_user',
            'replication_password' => 's3kr3t',
        ));

        $this->assertRegExp(
            '/^I cannot configure the slave with the master information/',
            $this->tester->getDisplay()
        );
    }

    public function testCommandReportsThatItCompletedSuccessfully()
    {
        $this
            ->config
            ->expects($this->once())
            ->method('setConnectionUrl')
            ->with('mysql://db_user:passw0rd@db_host/')
        ;
        $this
            ->client
            ->method('execute')
            ->willReturn(true)
        ;

        $this->tester->execute(array(
            'command' => $this->app->find('mysql:master-info'),
            'url' => 'mysql://db_user:passw0rd@db_host/',
            'master_hostname' => '203.0.113.0',
            'replication_username' => 'repln_user',
            'replication_password' => 's3kr3t',
        ));

        $this->assertRegExp(
            '/^I have successfully configured the slave with the master information/',
            $this->tester->getDisplay()
        );
    }
}
