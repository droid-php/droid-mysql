<?php

namespace Droid\Plugin\Mysql\Model;

use InvalidArgumentException;
use UnexpectedValueException;

use Droid\Plugin\Mysql\Db\Client;

/**
 * Generate the MySQL query statements relating to the addition, removal and
 * the granting of privileges to a MySQL user account.
 */
class User
{
    const NAME_LEN_MAX = 16;

    protected $client;

    private $name;
    private $password;
    private $host;
    private $grantLevel;
    private $grants;
    private $grantGrant = false;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function setName($name)
    {
        if (strlen($name) > self::NAME_LEN_MAX) {
            throw new InvalidArgumentException(
                sprintf(
                    'User name cannot exceed %d characters in length',
                    self::NAME_LEN_MAX
                )
            );
        }
        $this->name = $name;
        return $this;
    }

    public function setPassword($password)
    {
        $this->password = $password;
        return $this;
    }

    public function setHost($host)
    {
        $this->host = $host;
        return $this;
    }

    public function setGrantLevel($level)
    {
        $this->grantLevel = $level;
        return $this;
    }

    public function setGrants($grants)
    {
        $this->grants = $grants;
        return $this;
    }

    public function setCanGrant($can = true)
    {
        $this->grantGrant = $can;
        return $this;
    }

    /**
     * Test whether or not the user exists.
     *
     * @return boolean
     *
     * @throws UnexpectedValueException
     */
    public function exists()
    {
        if (!$this->name) {
            throw new UnexpectedValueException(
                'Cannot check for existing user without a name'
            );
        }

        if (!$this->host) {
            throw new UnexpectedValueException(
                'Cannot check for existing user without a host because the result could be ambiguous'
            );
        }

        $row = $this->client->getSingleResult(
            'SELECT EXISTS(SELECT 1 FROM mysql.user WHERE user = :user AND host = :host)',
            array(
                ':user' => $this->name,
                ':host' => strtolower($this->host) == 'any' ? '%' : $this->host
            )
        );

        return (bool) $row[0];
    }

    /**
     * Delete the user.
     *
     * @throws UnexpectedValueException
     */
    public function delete()
    {
        if (!$this->name) {
            throw new UnexpectedValueException(
                'Cannot delete a user with no name'
            );
        }

        $parts = array('DROP USER :user');
        $params = array(':user' => $this->name);

        if ($this->host) {
            $parts[] = '@:host';
            $params[':host'] = strtolower($this->host) == 'any' ? '%' : $this->host;
        }

        $this->client->execute(implode(' ', $parts), $params);
    }

    /**
     * Create the user.
     *
     * @throws UnexpectedValueException
     */
    public function create()
    {
        if (!$this->name) {
            throw new UnexpectedValueException(
                'Cannot create a user with no name'
            );
        }

        $parts = array('CREATE USER :user');
        $params = array(':user' => $this->name);

        if ($this->host) {
            $parts[] = '@:host';
            $params[':host'] = strtolower($this->host) == 'any' ? '%' : $this->host;
        }
        if ($this->password) {
            $parts[] = 'IDENTIFIED BY :password';
            $params[':password'] = $this->password;
        }

        $this->client->execute(implode(' ', $parts), $params);
    }

    /**
     * Grant privileges to the user.
     *
     * @throws UnexpectedValueException
     */
    public function grant()
    {
        if (!$this->name) {
            throw new UnexpectedValueException(
                'Cannot grant privileges to a user with no name'
            );
        }
        if (!$this->grants) {
            throw new UnexpectedValueException(
                'Cannot grant privileges without being told what privileges to grant'
            );
        }
        if (!$this->grantLevel) {
            throw new UnexpectedValueException(
                'Cannot grant privileges without being told on what to grant them'
            );
        }

        $params = array();
        $parts = array(
            sprintf(
                'GRANT %s ON %s',
                strtolower($this->grants) == 'all'
                    ? 'ALL PRIVILEGES'
                    : $this->grants,
                $this->grantLevel
            )
        );

        $parts[] = 'TO :user';
        $params[':user'] = $this->name;

        if ($this->host) {
            $parts[] = '@:host';
            $params[':host'] = strtolower($this->host) == 'any' ? '%' : $this->host;
        }

        if ($this->grantGrant) {
            $parts[] = 'WITH GRANT OPTION';
        }

        $this->client->execute(implode(' ', $parts), $params);
    }
}
