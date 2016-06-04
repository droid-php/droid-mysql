<?php

namespace Droid\Test\Plugin\Mysql\Db;

use \UnexpectedValueException;

use Droid\Plugin\Mysql\Db\Config;

class ConfigTest extends \PHPUnit_Framework_TestCase
{
    protected $config;

    protected function setUp()
    {
        $this->config = new Config();
    }

    public function getConnectionData()
    {
        return array(
            array(
                null,
                UnexpectedValueException::class,
            ),
            array(
                'there://::was-a-crow-sat-on-a-bough',
                UnexpectedValueException::class,
            ),
            array(
                'mysql://some_user:some_pass@some_host/',
                array(
                    'dsn' => 'mysql:host=some_host',
                    'user' => 'some_user',
                    'pass' => 'some_pass',
                )
            ),
            array(
                'mysql://some_user:some_pass@some_host:33306/',
                array(
                    'dsn' => 'mysql:host=some_host:port=33306',
                    'user' => 'some_user',
                    'pass' => 'some_pass',
                )
            ),
            array(
                'mysql://some_host/some_db',
                array(
                    'dsn' => 'mysql:host=some_host:dbname=some_db',
                )
            ),
        );
    }

    /**
     * @dataProvider getConnectionData
     */
    public function testConnectionStringIsCorrectlyParsed($url, $expected)
    {
        $this->config->setConnectionUrl($url);

        if (is_string($expected)) {
            $this->setExpectedException($expected);
            $this->config->getDsn();
            return;
        }

        $this->assertSame($expected['dsn'], $this->config->getDsn());

        $this->assertSame(
            isset($expected['user']) ? $expected['user'] : null,
            $this->config->getUserName()
        );

        $this->assertSame(
            isset($expected['pass']) ? $expected['pass'] : null,
            $this->config->getUserPassword()
        );
    }
}
