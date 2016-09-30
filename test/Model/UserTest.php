<?php

namespace Droid\Test\Plugin\Mysql\Db;

use UnexpectedValueException;

use Droid\Plugin\Mysql\Db\Client;
use Droid\Plugin\Mysql\Model\User;

class UserTest extends \PHPUnit_Framework_TestCase
{
    protected $client;
    protected $user;

    protected function setUp()
    {
        $this->client = $this
            ->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $this->user = new User($this->client);
    }

    protected function populateUser($properties)
    {
        if (isset($properties['name'])) {
            $this->user->setName($properties['name']);
        }
        if (isset($properties['pass'])) {
            $this->user->setPassword($properties['pass']);
        }
        if (isset($properties['host'])) {
            $this->user->setHost($properties['host']);
        }
        if (isset($properties['lvl'])) {
            $this->user->setGrantLevel($properties['lvl']);
        }
        if (isset($properties['grant'])) {
            $this->user->setGrants($properties['grant']);
        }
        if (isset($properties['cangrant'])) {
            $this->user->setCanGrant($properties['cangrant']);
        }
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testSetNameThrowsExceptionWhenUsernameIsTooLong()
    {
        $this->user->setName('+0123456789abcdef');
    }

    public function testSetNameAllowsSixteenCharacterUsername()
    {
        $this->user->setName('0123456789abcdef');
    }

    /**
     * @dataProvider getUserDataForExists
     */
    public function testExists($properties, $statement, $params, $except)
    {
        $this->populateUser($properties);

        if ($except) {
            $this->setExpectedException($except);
            $this->user->exists();
            return;
        }

        $this
            ->client
            ->expects($this->once())
            ->method('getSingleResult')
            ->with($statement, $params)
        ;

        $this->user->exists();
    }

    public function getUserDataForExists()
    {
        return array(
            'Insufficient user data to check if one exists' => array(
                array(),
                '',
                array(),
                UnexpectedValueException::class,
            ),
            'Requires a host to check if a user exists' => array(
                array('name' => 'some_name'),
                '',
                array(),
                UnexpectedValueException::class,
            ),
            'Check if user exists' => array(
                array('name' => 'some_name', 'host' => 'some_host'),
                'SELECT EXISTS(SELECT 1 FROM mysql.user WHERE user = :user AND host = :host)',
                array(':user' => 'some_name', ':host' => 'some_host'),
                null,
            ),
            'Check if user exists with host set to any' => array(
                array('name' => 'some_name', 'host' => 'any'),
                'SELECT EXISTS(SELECT 1 FROM mysql.user WHERE user = :user AND host = :host)',
                array(':user' => 'some_name', ':host' => '%'),
                null,
            ),
        );
    }

    /**
     * @dataProvider getUserDataForCreate
     */
    public function testCreate($properties, $statement, $params, $except)
    {
        $this->populateUser($properties);

        if ($except) {
            $this->setExpectedException($except);
            $this->user->create();
            return;
        }

        $this
            ->client
            ->expects($this->exactly(3))
            ->method('execute')
            ->withConsecutive(
                array('FLUSH PRIVILEGES'),
                array($statement, $params),
                array('FLUSH PRIVILEGES')
            )
        ;

        $this->user->create();
    }

    public function getUserDataForCreate()
    {
        return array(
            'Insufficient user data to create' => array(
                array(),
                '',
                array(),
                UnexpectedValueException::class,
            ),
            'Create a user with no password' => array(
                array('name' => 'some_name'),
                'CREATE USER :user',
                array(':user' => 'some_name'),
                null,
            ),
            'Create a user@host with no password' => array(
                array('name' => 'some_name', 'host' => 'some_host'),
                'CREATE USER :user @:host',
                array(':user' => 'some_name', ':host' => 'some_host'),
                null,
            ),
            'Create a user with a passw0rd' => array(
                array('name' => 'some_name', 'pass' => '53kr3t'),
                'CREATE USER :user IDENTIFIED BY :password',
                array(':user' => 'some_name', ':password' => '53kr3t'),
                null,
            ),
            'Create a user @ host with a password' => array(
                array(
                    'name' => 'some_name',
                    'host' => 'some_host',
                    'pass' => '53kr3t',
                ),
                'CREATE USER :user @:host IDENTIFIED BY :password',
                array(
                    ':user' => 'some_name',
                    ':host' => 'some_host',
                    ':password' => '53kr3t',
                ),
                null,
            ),
            'Create a user @ any host with a password' => array(
                array(
                    'name' => 'some_name',
                    'host' => 'any',
                    'pass' => '53kr3t',
                ),
                'CREATE USER :user @:host IDENTIFIED BY :password',
                array(
                    ':user' => 'some_name',
                    ':host' => '%',
                    ':password' => '53kr3t',
                ),
                null,
            ),
        );
    }

    /**
     * @dataProvider getUserDataForDelete
     */
    public function testDelete($properties, $statement, $params, $except)
    {
        $this->populateUser($properties);

        if ($except) {
            $this->setExpectedException($except);
            $this->user->delete();
            return;
        }

        $this
            ->client
            ->expects($this->exactly(3))
            ->method('execute')
            ->withConsecutive(
                array('FLUSH PRIVILEGES'),
                array($statement, $params),
                array('FLUSH PRIVILEGES')
            )
        ;

        $this->user->delete();
    }

    public function getUserDataForDelete()
    {
        return array(
            'Insufficient user data to delete' => array(
                array(),
                '',
                array(),
                UnexpectedValueException::class,
            ),
            'Delete a user with no host' => array(
                array('name' => 'some_name'),
                'DROP USER :user',
                array(':user' => 'some_name'),
                null,
            ),
            'Delete a user@host' => array(
                array('name' => 'some_name', 'host' => 'some_host'),
                'DROP USER :user @:host',
                array(':user' => 'some_name', ':host' => 'some_host'),
                null,
            ),
            'Delete a user @ any' => array(
                array('name' => 'some_name', 'host' => 'any'),
                'DROP USER :user @:host',
                array(':user' => 'some_name', ':host' => '%'),
                null,
            ),
        );
    }

    /**
     * @dataProvider getUserDataForGrant
     */
    public function testGrant($properties, $statement, $params, $except)
    {
        $this->populateUser($properties);

        if ($except) {
            $this->setExpectedException($except);
            $this->user->grant();
            return;
        }

        $this
            ->client
            ->expects($this->exactly(3))
            ->method('execute')
            ->withConsecutive(
                array('FLUSH PRIVILEGES'),
                array($statement, $params),
                array('FLUSH PRIVILEGES')
            )
        ;

        $this->user->grant();
    }

    public function getUserDataForGrant()
    {
        return array(
            'Cannot grant without username, grants and grantLevel' => array(
                array(),
                '',
                array(),
                UnexpectedValueException::class,
            ),
            'Cannot grant without grants and grantLevel' => array(
                array('name' => 'some_name'),
                '',
                array(),
                UnexpectedValueException::class,
            ),
            'Cannot grant without grantLevel' => array(
                array('name' => 'some_name', 'grant' => 'USAGE'),
                '',
                array(),
                UnexpectedValueException::class,
            ),
            'Grant None on *.* to user' => array(
                array(
                    'name' => 'some_name',
                    'grant' => 'USAGE',
                    'lvl' => '*.*',
                ),
                'GRANT USAGE ON *.* TO :user',
                array(':user' => 'some_name'),
                null,
            ),
            'Grant None on *.* to user@host' => array(
                array(
                    'name' => 'some_name',
                    'host' => 'some_host',
                    'grant' => 'USAGE',
                    'lvl' => '*.*',
                ),
                'GRANT USAGE ON *.* TO :user @:host',
                array(':user' => 'some_name', ':host' => 'some_host'),
                null,
            ),
            'Grant None on *.* to user @ any host' => array(
                array(
                    'name' => 'some_name',
                    'host' => 'any',
                    'grant' => 'USAGE',
                    'lvl' => '*.*',
                ),
                'GRANT USAGE ON *.* TO :user @:host',
                array(':user' => 'some_name', ':host' => '%'),
                null,
            ),
            'Grant ALL on *.* to user @ any host with GRANT OPTION' => array(
                array(
                    'name' => 'some_name',
                    'host' => 'any',
                    'grant' => 'all',
                    'lvl' => '*.*',
                    'cangrant' => true,
                ),
                'GRANT ALL PRIVILEGES ON *.* TO :user @:host WITH GRANT OPTION',
                array(':user' => 'some_name', ':host' => '%'),
                null,
            ),
        );
    }
}
