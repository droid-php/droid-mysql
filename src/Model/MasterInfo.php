<?php

namespace Droid\Plugin\Mysql\Model;

use UnexpectedValueException;

use Droid\Plugin\Mysql\Db\Client;

/**
 * Generate the CHANGE MASTER MySQL query statement to configure a slave in
 * Binary Log Replication.
 */
class MasterInfo
{
    const EMPTY_LOG_FILENAME = '';
    const EMPTY_LOG_POSITION = 4;

    protected $client;

    private $master_hostname;
    private $replication_username;
    private $replication_password;
    private $recorded_log_file_name = self::EMPTY_LOG_FILENAME;
    private $recorded_log_pos = self::EMPTY_LOG_POSITION;


    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function setMasterHostname($hostname)
    {
        $this->master_hostname = (string) $hostname;
        return $this;
    }

    public function setReplicationUsername($username)
    {
        $this->replication_username = (string) $username;
        return $this;
    }

    public function setReplicationPassword($password)
    {
        $this->replication_password = (string) $password;
        return $this;
    }

    public function setRecordedLogInfo($filename, $position)
    {
        $this->recorded_log_file_name = (string) $filename;
        $this->recorded_log_pos = (int) $position;
        return $this;
    }

    /**
     * Execute the CHANGE MASTER MySQL query.
     *
     * @throws \UnexpectedValueException
     */
    public function execute()
    {
        if (!is_string($this->master_hostname)
            || empty($this->master_hostname)
        ) {
            throw new UnexpectedValueException(
                'Cannot execute CHANGE MASTER without a valid master hostname.'
            );
        }
        if (!is_string($this->replication_username)
            || empty($this->replication_username)
        ) {
            throw new UnexpectedValueException(
                'Cannot execute CHANGE MASTER without a valid replication username.'
            );
        }
        if (!is_string($this->replication_password)
            || empty($this->replication_password)
        ) {
            throw new UnexpectedValueException(
                'Cannot execute CHANGE MASTER without a valid replication password.'
            );
        }
        if (!is_string($this->recorded_log_file_name)) {
            throw new UnexpectedValueException(
                'Cannot execute CHANGE MASTER without a valid log file name (empty is acceptable).'
            );
        }
        if (!is_int($this->recorded_log_pos)) {
            throw new UnexpectedValueException(
                'Cannot execute CHANGE MASTER without a valid log file position.'
            );
        }
        if ($this->recorded_log_file_name == self::EMPTY_LOG_FILENAME
            && $this->recorded_log_pos != self::EMPTY_LOG_POSITION
        ) {
            throw new UnexpectedValueException(
                sprintf(
                    'The value of the log position must be equal to %d when the log file name is given as "%s".',
                    self::EMPTY_LOG_POSITION,
                    self::EMPTY_LOG_FILENAME
                )
            );
        }
        if ($this->recorded_log_file_name != self::EMPTY_LOG_FILENAME
            && $this->recorded_log_pos <= self::EMPTY_LOG_POSITION
        ) {
            throw new UnexpectedValueException(
                sprintf(
                    'The value of the log position must be greater than %d when the log file name is given something other than "%s".',
                    self::EMPTY_LOG_POSITION,
                    self::EMPTY_LOG_FILENAME
                )
            );
        }

        $statement = implode(
            ', ',
            array(
                'CHANGE MASTER TO MASTER_HOST=:master_hostname',
                'MASTER_USER=:replication_username',
                'MASTER_PASSWORD=:replication_password',
                'MASTER_LOG_FILE=:recorded_log_file_name',
                'MASTER_LOG_POS=:recorded_log_pos',
            )
        ) . ';';
        $params = array(
            ':master_hostname' => $this->master_hostname,
            ':replication_username' => $this->replication_username,
            ':replication_password' => $this->replication_password,
            ':recorded_log_file_name' => $this->recorded_log_file_name,
            ':recorded_log_pos' => array(
                $this->recorded_log_pos, $this->client->typeInt()
            ),
        );

        return $this->client->execute($statement, $params);
    }
}
