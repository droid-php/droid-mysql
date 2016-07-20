<?php

namespace Droid\Test\Plugin\Mysql\Db;

use PDO;
use PDOException;

use Droid\Plugin\Mysql\Db\Client;
use Droid\Plugin\Mysql\Db\Config;
use Droid\Plugin\Mysql\Db\ConnectionFactory;

use Droid\Test\Plugin\Mysql\Mock\PDOMock;
use Droid\Test\Plugin\Mysql\Mock\PDOStatementMock;

class ClientTest extends \PHPUnit_Framework_TestCase
{
    protected $client;
    protected $config;
    protected $connection;
    protected $fac;
    protected $pdoStatement;

    protected function setUp()
    {
        $this->pdoStatement = $this
            ->getMockBuilder(PDOStatementMock::class)
            ->getMock()
        ;
        $this->connection = $this
            ->getMockBuilder(PDOMock::class)
            ->getMock()
        ;
        $this
            ->connection
            ->method('prepare')
            ->willReturn($this->pdoStatement)
        ;
        $this->fac = $this
            ->getMockBuilder(ConnectionFactory::class)
            ->getMock()
        ;
        $this->config = $this->getMock(Config::class);
        $this->client = new Client($this->config, $this->fac);
    }

    public function testGetConfigErmGetsConfigDamnYouPeskyCoverageReport()
    {
        $this->assertSame($this->config, $this->client->getConfig());
    }

    /**
     * @expectedException \Droid\Plugin\Mysql\Db\ClientException
     * @expectedExceptionMessage Failed to create a MySQL connection
     */
    public function testGetConnectionThrowsClientExceptionWhenConnectionCreationFails()
    {
        $this
            ->fac
            ->method('create')
            ->willThrowException(new PDOException)
        ;

        $this->client->getConnection();
    }

    public function testGetConnectionConfiguresAndCreatesConnection()
    {
        $this
            ->fac
            ->expects($this->once())
            ->method('setConfig')
            ->with($this->config)
            ->willReturnSelf()
        ;
        $this
            ->fac
            ->expects($this->once())
            ->method('create')
            ->willReturn($this->connection)
        ;

        $this->assertSame($this->connection, $this->client->getConnection());
    }

    /**
     * @expectedException \Droid\Plugin\Mysql\Db\ClientException
     */
    public function testExecuteThrowsExceptionWhenStatementPreparationFails()
    {
        $statement = 'SELECT :something';
        $params = array(':something' => 'all the things');

        $this
            ->fac
            ->method('create')
            ->willReturn($this->connection)
        ;
        $this
            ->connection
            ->method('prepare')
            ->willThrowException(new PDOException)
        ;

        $this->client->execute($statement, $params);
    }

    /**
     * @expectedException \Droid\Plugin\Mysql\Db\ClientException
     */
    public function testExecuteThrowsExceptionWhenStatementExecutionFails()
    {
        $statement = 'SELECT :something';
        $params = array(':something' => 'all the things');

        $this
            ->fac
            ->method('create')
            ->willReturn($this->connection)
        ;
        $this
            ->connection
            ->expects($this->once())
            ->method('prepare')
            ->with($statement)
        ;
        $this
            ->pdoStatement
            ->method('execute')
            ->willThrowException(new PDOException)
        ;

        $this->client->execute($statement, $params);
    }

    public function testExecuteReturnsTrueOnSuccess()
    {
        $statement = 'SELECT :something';
        $params = array(':something' => 'all the things');

        $this
            ->fac
            ->method('create')
            ->willReturn($this->connection)
        ;
        $this
            ->connection
            ->expects($this->once())
            ->method('prepare')
            ->with($statement)
        ;
        $this
            ->pdoStatement
            ->expects($this->once())
            ->method('bindValue')
            ->with(':something', 'all the things', $this->client->typeStr())
            ->willReturn(true)
        ;
        $this
            ->pdoStatement
            ->expects($this->once())
            ->method('execute')
            ->willReturn(true)
        ;

        $this->assertTrue($this->client->execute($statement, $params));
    }

    public function testExecuteParamsCanIncludeDataType()
    {
        $statement = 'SELECT :something';
        $params = array(
            ':something' => array(1, $this->client->typeInt())
        );

        $this
            ->fac
            ->method('create')
            ->willReturn($this->connection)
        ;
        $this
            ->connection
            ->method('prepare')
            ->with($statement)
        ;
        $this
            ->pdoStatement
            ->expects($this->once())
            ->method('bindValue')
            ->with(':something', 1, $this->client->typeInt())
            ->willReturn(true)
        ;

        $this->client->execute($statement, $params);
    }

    /**
     * @expectedException \Droid\Plugin\Mysql\Db\ClientException
     */
    public function testGetResultsThrowsExceptionWhenStatementPreparationFails()
    {
        $statement = 'SELECT :something';
        $params = array(':something' => 'all the things');

        $this
            ->fac
            ->method('create')
            ->willReturn($this->connection)
        ;
        $this
            ->connection
            ->method('prepare')
            ->willThrowException(new PDOException)
        ;

        $this->client->getResults($statement, $params);
    }

    /**
     * @expectedException \Droid\Plugin\Mysql\Db\ClientException
     */
    public function testGetResultsThrowsExceptionWhenStatementExecutionFails()
    {
        $statement = 'SELECT :something';
        $params = array(':something' => 'all the things');

        $this
            ->fac
            ->method('create')
            ->willReturn($this->connection)
        ;
        $this
            ->connection
            ->expects($this->once())
            ->method('prepare')
            ->with($statement)
        ;
        $this
            ->pdoStatement
            ->method('execute')
            ->willThrowException(new PDOException)
        ;

        $this->client->getResults($statement, $params);
    }

    /**
     * @expectedException \Droid\Plugin\Mysql\Db\ClientException
     */
    public function testGetResultsThrowsExceptionWhenFecthFails()
    {
        $statement = 'SELECT :something';
        $params = array(':something' => 'all the things');

        $this
            ->fac
            ->method('create')
            ->willReturn($this->connection)
        ;
        $this
            ->connection
            ->expects($this->once())
            ->method('prepare')
            ->with($statement)
        ;
        $this
            ->pdoStatement
            ->expects($this->once())
            ->method('bindValue')
            ->with(':something', 'all the things', $this->client->typeStr())
            ->willReturn(true)
        ;
        $this
            ->pdoStatement
            ->expects($this->once())
            ->method('execute')
            ->willReturn(true)
        ;
        $this
            ->pdoStatement
            ->method('fetchAll')
            ->willThrowException(new PDOException)
        ;

        $this->client->getResults($statement, $params);
    }

    public function testGetResultsReturnsResults()
    {
        $statement = 'SELECT :something';
        $params = array(':something' => 'all the things');
        $result = array(array('r1c1-value'), array('r2c1-value'));

        $this
            ->fac
            ->method('create')
            ->willReturn($this->connection)
        ;
        $this
            ->connection
            ->expects($this->once())
            ->method('prepare')
            ->with($statement)
        ;
        $this
            ->pdoStatement
            ->expects($this->once())
            ->method('bindValue')
            ->with(':something', 'all the things', $this->client->typeStr())
            ->willReturn(true)
        ;
        $this
            ->pdoStatement
            ->expects($this->once())
            ->method('execute')
            ->willReturn(true)
        ;
        $this
            ->pdoStatement
            ->expects($this->once())
            ->method('fetchAll')
            ->willReturn($result)
        ;

        $this->assertSame(
            $result,
            $this->client->getResults($statement, $params)
        );
    }

    public function testGetResultsParamsCanIncludeDataType()
    {
        $statement = 'SELECT :something';
        $params = array(
            ':something' => array(1, $this->client->typeInt())
        );
        $result = array(array('r1c1-value'), array('r2c1-value'));

        $this
            ->fac
            ->method('create')
            ->willReturn($this->connection)
        ;
        $this
            ->connection
            ->expects($this->once())
            ->method('prepare')
            ->with($statement)
        ;
        $this
            ->pdoStatement
            ->expects($this->once())
            ->method('bindValue')
            ->with(':something', 1, $this->client->typeInt())
            ->willReturn(true)
        ;
        $this
            ->pdoStatement
            ->expects($this->once())
            ->method('execute')
            ->willReturn(true)
        ;
        $this
            ->pdoStatement
            ->expects($this->once())
            ->method('fetchAll')
            ->willReturn($result)
        ;

        $this->assertSame(
            $result,
            $this->client->getResults($statement, $params)
        );
    }

    public function testGetSingleResultReturnsFirstResult()
    {
        $statement = 'SELECT :something';
        $params = array(':something' => 'all the things');
        $result = array(array('r1c1-value'), array('r2c1-value'));

        $this
            ->fac
            ->method('create')
            ->willReturn($this->connection)
        ;
        $this
            ->connection
            ->expects($this->once())
            ->method('prepare')
            ->with($statement)
        ;
        $this
            ->pdoStatement
            ->expects($this->once())
            ->method('bindValue')
            ->with(':something', 'all the things', $this->client->typeStr())
            ->willReturn(true)
        ;
        $this
            ->pdoStatement
            ->expects($this->once())
            ->method('execute')
            ->willReturn(true)
        ;
        $this
            ->pdoStatement
            ->expects($this->once())
            ->method('fetchAll')
            ->willReturn($result)
        ;

        $this->assertSame(
            $result[0],
            $this->client->getSingleResult($statement, $params)
        );
    }

    public function testGetSingleResultReturnsNullOnEmptyResult()
    {
        $statement = 'SELECT :something';
        $params = array(':something' => 'all the things');

        $this
            ->fac
            ->method('create')
            ->willReturn($this->connection)
        ;
        $this
            ->connection
            ->expects($this->once())
            ->method('prepare')
            ->with($statement)
        ;
        $this
            ->pdoStatement
            ->expects($this->once())
            ->method('bindValue')
            ->with(':something', 'all the things', $this->client->typeStr())
            ->willReturn(true)
        ;
        $this
            ->pdoStatement
            ->expects($this->once())
            ->method('execute')
            ->willReturn(true)
        ;
        $this
            ->pdoStatement
            ->expects($this->once())
            ->method('fetchAll')
            ->willReturn(array())
        ;

        $this->assertNull($this->client->getSingleResult($statement, $params));
    }

    public function testTypeBoolReturnsPDOParamContant()
    {
        $this->assertSame(PDO::PARAM_BOOL, $this->client->typeBool());
    }

    public function testTypeIntReturnsPDOParamContant()
    {
        $this->assertSame(PDO::PARAM_INT, $this->client->typeInt());
    }

    public function testTypeLobReturnsPDOParamContant()
    {
        $this->assertSame(PDO::PARAM_LOB, $this->client->typeLob());
    }

    public function testTypeNullReturnsPDOParamContant()
    {
        $this->assertSame(PDO::PARAM_NULL, $this->client->typeNull());
    }

    public function testTypeStrReturnsPDOParamContant()
    {
        $this->assertSame(PDO::PARAM_STR, $this->client->typeStr());
    }
}
