<?php

namespace Droid\Test\Plugin\Mysql\Command;

use RuntimeException;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

use Droid\Plugin\Mysql\Command\MysqlDeluserCommand;
use Droid\Plugin\Mysql\Db\Client;
use Droid\Plugin\Mysql\Db\ClientException;
use Droid\Plugin\Mysql\Db\Config;

class MysqlDeluserCommandTest extends \PHPUnit_Framework_TestCase
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

        $command = new MysqlDeluserCommand($this->client);

        $this->app = new Application;
        $this->app->add($command);

        $this->tester = new CommandTester($command);
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
            ->method('getSingleResult')
            ->willReturn(array('0'))
        ;

        $this->tester->execute(array(
            'command' => $this->app->find('mysql:deluser'),
            'url' => 'mysql://db_user:passw0rd@db_host/',
            'username' => 'some_user',
            'host' => '127.0.0.1',
            '--check' => true,
        ));
    }

    public function testCommandWillNotDeleteUserIfNotExists()
    {
        $this
            ->client
            ->method('getSingleResult')
            ->willReturn(array('0'))
        ;

        $this->tester->execute(array(
            'command' => $this->app->find('mysql:deluser'),
            'url' => 'mysql://db_user:passw0rd@db_host/',
            'username' => 'not_a_user',
            'host' => '127.0.0.1',
        ));

        $this->assertRegExp(
            '/^I will not delete user "not_a_user" @host "127.0.0.1" because one does not exist/',
            $this->tester->getDisplay()
        );
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage I cannot delete user "some_user" @host "127.0.0.1"
     */
    public function testCommandThrowsRuntimeExceptionWhenFailsToDeleteUser()
    {
        $this
            ->client
            ->method('getSingleResult')
            ->willReturn(array('1'))
        ;
        $this
            ->client
            ->method('execute')
            ->willThrowException(new ClientException)
        ;

        $this->tester->execute(array(
            'command' => $this->app->find('mysql:deluser'),
            'url' => 'mysql://db_user:passw0rd@db_host/',
            'username' => 'some_user',
            'host' => '127.0.0.1',
        ));
    }

    public function testCommandWillDeleteUser()
    {
        $this
            ->client
            ->method('getSingleResult')
            ->willReturn(array('1'))
        ;
        $this
            ->client
            ->method('execute')
            ->willReturn(true)
        ;

        $this->tester->execute(array(
            'command' => $this->app->find('mysql:deluser'),
            'url' => 'mysql://db_user:passw0rd@db_host/',
            'username' => 'some_user',
            'host' => '127.0.0.1',
        ));

        $this->assertRegExp(
            '/^I have successfully deleted the user "some_user" @host "127.0.0.1/',
            $this->tester->getDisplay()
        );
    }

    public function testCommandWillNotDeleteUserInCheckMode()
    {
        $this
            ->client
            ->method('getSingleResult')
            ->willReturn(array('1'))
        ;

        $this->tester->execute(array(
            'command' => $this->app->find('mysql:deluser'),
            'url' => 'mysql://db_user:passw0rd@db_host/',
            'username' => 'some_user',
            'host' => '127.0.0.1',
            '--check' => true,
        ));

        $this->assertRegExp(
            '/^I would delete the user "some_user" @host "127.0.0.1/',
            $this->tester->getDisplay()
        );
    }
}
