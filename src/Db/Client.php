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
     * @param array $params The keys of the array are the one-indexed parameter
     *                      number or the colon-prefixed parameter name. The
     *                      values are either a string or an array containing
     *                      the value and the data type (i.e. PDO::PARAM_*).
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

        foreach ($params as $name => $paramInfo) {
            if (is_string($paramInfo)) {
                $preparedStmt->bindValue($name, $paramInfo, PDO::PARAM_STR);
            } elseif (is_array($paramInfo)) {
                list($value, $type) = $paramInfo;
                $preparedStmt->bindValue($name, $value, $type);
            }
        }

        try {
            $result = $preparedStmt->execute();
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
     * @param array $params The keys of the array are the one-indexed parameter
     *                      number or the colon-prefixed parameter name. The
     *                      values are either a string or an array containing
     *                      the value and the data type (i.e. PDO::PARAM_*).
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

        foreach ($params as $name => $paramInfo) {
            if (is_string($paramInfo)) {
                $preparedStmt->bindValue($name, $paramInfo, PDO::PARAM_STR);
            } elseif (is_array($paramInfo)) {
                list($value, $type) = $paramInfo;
                $preparedStmt->bindValue($name, $value, $type);
            }
        }

        try {
            $preparedStmt->execute();
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

    /**
     * Get a constant which specifies that the type of a parameter is a boolean.
     *
     * @return int
     */
    public function typeBool()
    {
        return PDO::PARAM_BOOL;
    }

    /**
     * Get a constant which specifies that the type of a parameter is an SQL
     * INTEGER.
     *
     * @return int
     */
    public function typeInt()
    {
        return PDO::PARAM_INT;
    }

    /**
     * Get a constant which specifies that the type of a parameter is an SQL
     * large object.
     *
     * @return int
     */
    public function typeLob()
    {
        return PDO::PARAM_LOB;
    }

    /**
     * Get a constant which specifies that the type of a parameter is an SQL
     * NULL.
     *
     * @return int
     */
    public function typeNull()
    {
        return PDO::PARAM_NULL;
    }

    /**
     * Get a constant which specifies that the type of a parameter is an SQL
     * CHAR or VARCHAR.
     *
     * @return int
     */
    public function typeStr()
    {
        return PDO::PARAM_STR;
    }
}
