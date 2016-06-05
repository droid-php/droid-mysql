<?php

namespace Droid\Test\Plugin\Mysql\Command;

use RuntimeException;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

use Droid\Plugin\Mysql\Command\MysqlAdduserCommand;
use Droid\Plugin\Mysql\Db\Client;
use Droid\Plugin\Mysql\Db\ClientException;
use Droid\Plugin\Mysql\Db\Config;

class MysqlAdduserCommandTest extends \PHPUnit_Framework_TestCase
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

        $command = new MysqlAdduserCommand($this->client);

        $this->app = new Application;
        $this->app->add($command);

        $this->tester = new CommandTester($command);
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage You must specify --grant_level when using --grant
     */
    public function testCommandThrowsRuntimeExceptionWhenGrantWithoutGrantLevel()
    {
        $this->tester->execute(array(
            'command' => $this->app->find('mysql:adduser'),
            'url' => 'mysql://db_user:passw0rd@db_host/',
            'username' => 'new_dbuser',
            'password' => 's3kr3t',
            'allowed-host' => '127.0.0.1',
            '--grant' => 'all',
            '--check' => true,
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
            ->method('getSingleResult')
            ->willReturn(array('1'))
        ;

        $this->tester->execute(array(
            'command' => $this->app->find('mysql:adduser'),
            'url' => 'mysql://db_user:passw0rd@db_host/',
            'username' => 'new_dbuser',
            'password' => 's3kr3t',
            'allowed-host' => '127.0.0.1',
            '--check' => true,
        ));
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage User name cannot exceed 16 characters in length
     */
    public function testCommandThrowsRuntimeExceptionWhenUsernameTooLong()
    {
        $this->tester->execute(array(
            'command' => $this->app->find('mysql:adduser'),
            'url' => 'mysql://db_user:passw0rd@db_host/',
            'username' => 'a_much_too_long_username',
            'password' => 's3kr3t',
            'allowed-host' => '127.0.0.1',
        ));
    }

    public function testCommandWillNotCreateUserIfExists()
    {
        $this
            ->client
            ->method('getSingleResult')
            ->willReturn(array('1'))
        ;

        $this->tester->execute(array(
            'command' => $this->app->find('mysql:adduser'),
            'url' => 'mysql://db_user:passw0rd@db_host/',
            'username' => 'existing_user',
            'password' => 's3kr3t',
            'allowed-host' => '127.0.0.1',
        ));

        $this->assertRegExp(
            '/^I will not create user "existing_user" @host "127.0.0.1" because one already exists/',
            $this->tester->getDisplay()
        );
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage I cannot create user "new_dbuser"
     */
    public function testCommandThrowsRuntimeExceptionWhenFailsToCreateUser()
    {
        $this
            ->client
            ->method('getSingleResult')
            ->willReturn(array('0'))
        ;
        $this
            ->client
            ->method('execute')
            ->willThrowException(new ClientException)
        ;

        $this->tester->execute(array(
            'command' => $this->app->find('mysql:adduser'),
            'url' => 'mysql://db_user:passw0rd@db_host/',
            'username' => 'new_dbuser',
            'password' => 's3kr3t',
            'allowed-host' => '127.0.0.1',
        ));
    }

    public function testCommandWillCreateUser()
    {
        $this
            ->client
            ->method('getSingleResult')
            ->willReturn(array('0'))
        ;
        $this
            ->client
            ->method('execute')
            ->willReturn(true)
        ;

        $this->tester->execute(array(
            'command' => $this->app->find('mysql:adduser'),
            'url' => 'mysql://db_user:passw0rd@db_host/',
            'username' => 'new_dbuser',
            'password' => 's3kr3t',
            'allowed-host' => '127.0.0.1',
        ));

        $this->assertRegExp(
            '/^I have successfully created the user "new_dbuser"/',
            $this->tester->getDisplay()
        );
    }

    public function testCommandWillNotCreateUserInCheckMode()
    {
        $this
            ->client
            ->method('getSingleResult')
            ->willReturn(array('0'))
        ;
        $this
            ->client
            ->method('execute')
            ->willReturn(true)
        ;

        $this->tester->execute(array(
            'command' => $this->app->find('mysql:adduser'),
            'url' => 'mysql://db_user:passw0rd@db_host/',
            'username' => 'new_dbuser',
            'password' => 's3kr3t',
            'allowed-host' => '127.0.0.1',
            '--check' => true,
        ));

        $this->assertRegExp(
            '/^I would create the user "new_dbuser"/',
            $this->tester->getDisplay()
        );
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage I cannot grant privileges to the user "existing_user"
     */
    public function testCommandThrowsRuntimeExceptionWhenFailsToGrantPrivileges()
    {
        $this
            ->client
            ->method('getSingleResult')
            ->willReturn(array('1'))
        ;
        $this
            ->client
            ->expects($this->once())
            ->method('execute')
            ->willThrowException(new ClientException)
        ;

        $this->tester->execute(array(
            'command' => $this->app->find('mysql:adduser'),
            'url' => 'mysql://db_user:passw0rd@db_host/',
            'username' => 'existing_user',
            'password' => 's3kr3t',
            'allowed-host' => '127.0.0.1',
            '--grant' => 'all',
            '--grant-level' => '*.*',
        ));
    }

    public function testCommandWillGrantPrivileges()
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
            'command' => $this->app->find('mysql:adduser'),
            'url' => 'mysql://db_user:passw0rd@db_host/',
            'username' => 'existing_user',
            'password' => 's3kr3t',
            'allowed-host' => '127.0.0.1',
            '--grant' => 'all',
            '--grant-level' => '*.*',
        ));

        $this->assertRegExp(
            '/I have successfully granted privileges to the user "existing_user"/',
            $this->tester->getDisplay()
        );
    }

    public function testCommandWillNotGrantPrivilegesInCheckMode()
    {
        $this
            ->client
            ->method('getSingleResult')
            ->willReturn(array('1'))
        ;

        $this->tester->execute(array(
            'command' => $this->app->find('mysql:adduser'),
            'url' => 'mysql://db_user:passw0rd@db_host/',
            'username' => 'existing_user',
            'password' => 's3kr3t',
            'allowed-host' => '127.0.0.1',
            '--grant' => 'all',
            '--grant-level' => '*.*',
            '--check' => true,
        ));

        $this->assertRegExp(
            '/I would grant privileges to the user "existing_user"/',
            $this->tester->getDisplay()
        );
    }
}
