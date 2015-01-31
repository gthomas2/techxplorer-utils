<?php
/**
 * This file is part of Techxplorer's Utility Scripts.
 *
 * Techxplorer's Utility Scripts is free software: you can redistribute
 * it and/or modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * Techxplorer's Utility Scripts is distributed in the hope that it will
 * be useful, but WITHOUT ANY WARRANTY; without even the implied
 * warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Techxplorer's Utility Scripts.
 * If not, see <http://www.gnu.org/licenses/>
 *
 * @author techxplorer <corey@techxplorer.com>
 * @copyright techxplorer 2015
 * @license GPL-3.0+
 * @see https://github.com/techxplorer/techxplorer-utils
 * @version 1.0
 */

namespace Techxplorer\Database;

use \InvalidArgumentException;

/**
 * A base class defining consistent methods for Database related tasks
 *
 * @package    Techxplorer
 * @subpackage Database
 */
abstract class Database
{
    /** @var string the database host */
    protected $db_host;

    /** @var string the database user */
    protected $db_user;

    /** @var string the database password */
    protected $db_password;

    /** @var string the database name */
    protected $db_name;

    /** @var object the connection to the database */
    protected $db_connection;

    /**
     * Instantiate a new database object
     *
     * @param string $db_host     the database host name
     * @param string $db_user     the database user name
     * @param string $db_password the database user password
     * @param string $db_name     the name of the database itself
     *
     * @throws InvalidArgumentException if any arguments fail validation
     */
    protected function __construct($db_host, $db_user, $db_password, $db_name)
    {
        if (empty($db_host)) {
            throw new InvalidArgumentException('The $db_host parameter is required');
        }

        if (empty($db_user)) {
            throw new InvalidArgumentException('The $db_user parameter is required');
        }

        if (empty($db_password)) {
            throw new InvalidArgumentException('The $db_password parameter is required');
        }

        if (empty($db_name)) {
            throw new InvalidArgumentException('the $db_name parameter is required');
        }

        $this->db_host = $db_host;
        $this->db_user = $db_user;
        $this->db_password = $db_password;
        $this->db_name = $db_name;
    }

    /**
     * Instantiate a connection to the database
     *
     * @return bool true on success, false on failure
     */
    abstract public function connect();

    /**
     * Get a list of databases
     *
     * @return array a list of databases
     */
    abstract public function getDatabaseList();

    /**
     * Get a list of users associated with one or more databases
     *
     * @param array $databases an array of databases
     *
     * @return array an array of users indexed by database
     *
     * @throws InvalidArgumentException if the arguments are invalid
     */
    abstract public function getUserList($databases);

    /**
     * Check to see if a user already exists in the system
     *
     * @param string $username the name of the user
     *
     * @return bool true if the user exists, false if they don't
     *
     * @throws InvalidArgumentException if the arguments are invalid
     */
    abstract public function userExists($username);

    /**
     * Check to see if a database already exists in the system
     *
     * @param string $database the name of the database
     *
     * @return bool true if the database exists, false if it doesn't
     *
     * @throws InvalidArgumentException if the arguments are invalid
     */
    abstract public function databaseExists($database);

    /**
     * Create a new user in the system
     *
     * @param string $username the name of the new user
     * @param string $password the password for the new user
     *
     * @return bool true on success, false on failure
     *
     * @throws InvalidArgumentException if the arguments are invalid
     */
    abstract public function createUser($username, $password);

    /**
     * Create a new database in the system
     *
     * @param string $database the name of the new database
     * @param string $username the name of the new user to associate with the database
     *
     * @return bool true on success, false on failure
     *
     * @throws InvalidArgumentException if the arguments are invalid
     */
    abstract public function createDatabase($database, $username);

    /**
     * Delete a user in the system
     *
     * @param string $username the name of the user
     *
     * @return bool true on success, false on failure
     *
     * @throws InvalidArgumentException if the arguments are invalid
     */
    abstract public function dropUser($username);

    /**
     * Delete a database in the system
     *
     * @param string $database the name of the database
     *
     * @return bool true on success, false on failure
     *
     * @throws InvalidArgumentException if the arguments are invalid
     */
    abstract public function dropDatabase($database);

    /**
     * Tidy up any remaining resources
     */
    public function __destruct()
    {
        throw new RuntimeException(get_called_class() . ' must define an custom destructor.');
    }
}
