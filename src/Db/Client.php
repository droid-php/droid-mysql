<?php

namespace Droid\Plugin\Mysql\Db;

use PDO;
use PDOException;

/**
 * A minimal PDO MySQL client.
 */
class Client
{
    protected $config;
    protected $connFac;
    protected $connection;

    public function __construct(Config $config, ConnectionFactory $connFac)
    {
        $this->config = $config;
        $this->connFac = $connFac;
    }

    /**
     * @return Config
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @return \PDO
     *
     * @throws \Droid\Plugin\Mysql\Db\ClientException
     */
    public function getConnection()
    {
        if (!$this->connection) {
            $this->connFac->setConfig($this->config);
            try {
                $this->connection = $this->connFac->create();
            } catch (PDOException $e) {
                throw new ClientException(
                    'Failed to create a MySQL connection.',
                    null,
                    $e
                );
            }
        }
        return $this->connection;
    }

    /**
     * Execute a statement, returning boolean true on success.
     *
     * @param string $statement
     * @param array $params
     *
     * @return boolean
     *
     * @throws \Droid\Plugin\Mysql\Db\ClientException
     */
    public function execute($statement, $params = array())
    {
        $preparedStmt = null;
        $result = false;

        try {
            $preparedStmt = $this->getConnection()->prepare($statement);
        } catch (PDOException $e) {
            throw new ClientException(
                'Failed to prepare MySQL statement.',
                null,
                $e
            );
        }

        try {
            $result = $preparedStmt->execute($params);
        } catch (PDOException $e) {
            throw new ClientException(
                'Failed to execute MySQL statement.',
                null,
                $e
            );
        }

        return $result;
    }

    /**
     * Execute a statement, returning all results.
     *
     * @param string $statement
     * @param array $params
     *
     * @return array
     *
     * @throws \Droid\Plugin\Mysql\Db\ClientException
     */
    public function getResults($statement, $params = array())
    {
        $preparedStmt = null;
        $result = false;

        try {
            $preparedStmt = $this->getConnection()->prepare($statement);
        } catch (PDOException $e) {
            throw new ClientException(
                'Failed to prepare MySQL statement.',
                null,
                $e
            );
        }

        try {
            $preparedStmt->execute($params);
        } catch (PDOException $e) {
            throw new ClientException(
                'Failed to execute MySQL statement.',
                null,
                $e
            );
        }

        try {
            $result = $preparedStmt->fetchAll(PDO::FETCH_BOTH);
        } catch (PDOException $e) {
            throw new ClientException(
                'Failed to fetch results of a MySQL statement execution.',
                null,
                $e
            );
        }

        return $result;
    }

    /**
     * Execute a statement, returning one row of results or null.
     *
     * @param string $statement
     * @param array $params
     *
     * @return null|array
     *
     * @throws \Droid\Plugin\Mysql\Db\ClientException
     */
    public function getSingleResult($statement, $params = array())
    {
        $result = $this->getResults($statement, $params);

        if (!sizeof($result)) {
            return null;
        }

        return $result[0];
    }
}
