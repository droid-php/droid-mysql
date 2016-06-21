<?php

namespace Droid\Plugin\Mysql\Db;

use PDO;

/**
 * Create a PDO instance representing a connection to a database.
 */
class ConnectionFactory
{
    protected $config;

    public function setConfig(Config $config)
    {
        $this->config = $config;
        return $this;
    }

    public function getConnectionParams()
    {
        return array(
            $this->config->getDsn(),
            $this->config->getUserName(),
            $this->config->getUserPassword(),
        );
    }

    /**
     * @return \PDO
     */
    public function create()
    {
        list($dsn, $username, $password) = $this->getConnectionParams();

        return new PDO(
            $dsn,
            $username,
            $password,
            array(
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            )
        );
    }
}
