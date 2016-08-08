<?php

namespace Droid\Test\Plugin\Mysql\Db;

use PDO;
use UnexpectedValueException;

use Droid\Plugin\Mysql\Db\Client;
use Droid\Plugin\Mysql\Model\MasterInfo;

class MasterInfoTest extends \PHPUnit_Framework_TestCase
{
    protected $client;
    protected $masterInfo;
    protected $statement;
    protected $paramNames;

    public function __construct($name = null, array $data = array(), $dataName = '')
    {
        $this->statement = $this->buildExpectedStatement();
        $this->paramNames = $this->buildParameterNames();

        return parent::__construct($name, $data, $dataName);
    }

    protected function setUp()
    {
        $this->client = $this
            ->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->setMethods(array('execute'))
            ->getMock()
        ;
        $this->masterInfo = new MasterInfo($this->client);
    }

    protected function populateMasterInfo($properties)
    {
        if (isset($properties['hostname'])) {
            $this->masterInfo->setMasterHostname($properties['hostname']);
        }
        if (isset($properties['user'])) {
            $this->masterInfo->setReplicationUsername($properties['user']);
        }
        if (isset($properties['passwd'])) {
            $this->masterInfo->setReplicationPassword($properties['passwd']);
        }
        if (isset($properties['log'])) {
            list($filename, $position) = $properties['log'];
            $this->masterInfo->setRecordedLogInfo($filename, $position);
        }
    }

    protected function buildExpectedStatement()
    {
        return implode(
            ', ',
            array(
                'CHANGE MASTER TO MASTER_HOST=:master_hostname',
                'MASTER_USER=:replication_username',
                'MASTER_PASSWORD=:replication_password',
                'MASTER_LOG_FILE=:recorded_log_file_name',
                'MASTER_LOG_POS=:recorded_log_pos',
            )
        ) . ';';
    }

    protected function buildParameterNames()
    {
        return array(
            ':master_hostname',
            ':replication_username',
            ':replication_password',
            ':recorded_log_file_name',
            ':recorded_log_pos',
        );
    }

    /**
     * @dataProvider getMasterInfoDataForExecute
     */
    public function testExecute($properties, $params, $except, $exceptMsg)
    {
        $this->populateMasterInfo($properties);

        if ($except) {
            $this->setExpectedException($except, $exceptMsg);
            $this->masterInfo->execute();
            return;
        }

        $this
            ->client
            ->expects($this->once())
            ->method('execute')
            ->with($this->statement, array_combine($this->paramNames, $params))
        ;

        $this->masterInfo->execute();
    }

    public function getMasterInfoDataForExecute()
    {
        return array(
            'Missing hostname' => array(
                array(
                    'hostname' => '',
                    'user' => 'repln_user',
                    'passwd' => 'pa55w0rd',
                ),
                null,
                UnexpectedValueException::class,
                'Cannot execute CHANGE MASTER without a valid master hostname',
            ),
            'Missing username' => array(
                array(
                    'hostname' => 'master.example.com',
                    'user' => '',
                    'passwd' => 'pa55w0rd',
                ),
                null,
                UnexpectedValueException::class,
                'Cannot execute CHANGE MASTER without a valid replication username',
            ),
            'Missing password' => array(
                array(
                    'hostname' => 'master.example.com',
                    'user' => 'repln_user',
                    'passwd' => '',
                ),
                null,
                UnexpectedValueException::class,
                'Cannot execute CHANGE MASTER without a valid replication password',
            ),
            'Empty log file name with incorrect position' => array(
                array(
                    'hostname' => 'master.example.com',
                    'user' => 'repln_user',
                    'passwd' => 'pa55w0rd',
                    'log' => array('', 178),
                ),
                null,
                UnexpectedValueException::class,
                'The value of the log position must be equal to',
            ),
            'Log file name with incorrect position' => array(
                array(
                    'hostname' => 'master.example.com',
                    'user' => 'repln_user',
                    'passwd' => 'pa55w0rd',
                    'log' => array('some.log', 4),
                ),
                null,
                UnexpectedValueException::class,
                'The value of the log position must be greater than',
            ),
            'Executes with default log info' => array(
                array(
                    'hostname' => 'master.example.com',
                    'user' => 'repln_user',
                    'passwd' => 'pa55w0rd',
                ),
                array(
                    'master.example.com',
                    'repln_user',
                    'pa55w0rd',
                    '',
                    array(4, PDO::PARAM_INT),
                ),
                null,
                null,
            ),
            'Executes with specified log info' => array(
                array(
                    'hostname' => 'master.example.com',
                    'user' => 'repln_user',
                    'passwd' => 'pa55w0rd',
                    'log' => array('some.log', 178),
                ),
                array(
                    'master.example.com',
                    'repln_user',
                    'pa55w0rd',
                    'some.log',
                    array(178, PDO::PARAM_INT),
                ),
                null,
                null,
            ),
        );
    }
}
