<?php

namespace Droid\Plugin\Mysql\Db;

use UnexpectedValueException;

/**
 * Configuration for connecting and authenticating to MySQL.
 */
class Config
{
    protected $connectionUrl;
    protected $connectionData;

    public function __construct($connectionUrl = null)
    {
        $this->setConnectionUrl($connectionUrl);
    }

    public function setConnectionUrl($connectionUrl)
    {
        $this->connectionUrl = $connectionUrl;
        $this->connectionData = null;
    }

    public function getHost()
    {
        $this->initConnectionData();

        return isset($this->connectionData['host'])
            ? $this->connectionData['host']
            : null
        ;
    }

    public function getPort()
    {
        $this->initConnectionData();

        return isset($this->connectionData['port'])
            ? $this->connectionData['port']
            : null
        ;
    }

    public function getDatabaseName()
    {
        $this->initConnectionData();

        return isset($this->connectionData['path'])
            ? trim($this->connectionData['path'], '/')
            : null
        ;
    }

    public function getUserName()
    {
        $this->initConnectionData();

        return isset($this->connectionData['user'])
            ? $this->connectionData['user']
            : null
        ;
    }

    public function getUserPassword()
    {
        $this->initConnectionData();

        return isset($this->connectionData['pass'])
            ? $this->connectionData['pass']
            : null
        ;
    }

    public function getDsn()
    {
        $this->initConnectionData();

        $parts = array();
        if ($this->getHost()) {
            $parts[] = 'host=' . $this->getHost();
        }
        if ($this->getPort()) {
            $parts[] = 'port=' . $this->getPort();
        }
        if ($this->getDatabaseName()) {
            $parts[] = 'dbname=' . $this->getDatabaseName();
        }

        return sizeof($parts)
            ? 'mysql:' . implode(':', $parts)
            : null
        ;
    }

    /**
     * Parse a connection url and populate connectionData with the result.
     *
     * @throws \UnexpectedValueException
     */
    protected function initConnectionData()
    {
        if ($this->connectionData) {
            return;
        }

        if (!$this->connectionUrl) {
            throw new \UnexpectedValueException(
                'Expected a connectionUrl, got nowt.'
            );
        }

        $parsed = parse_url($this->connectionUrl);
        if ($parsed === false) {
            throw new UnexpectedValueException(
                'Expected a sensible connectionUrl, got nowt but rubbish.'
            );
        }
        $this->connectionData = $parsed;
    }
}
