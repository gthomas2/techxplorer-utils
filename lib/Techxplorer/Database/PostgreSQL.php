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

/**
 * A PostgreSQL implementation of the Database class
 *
 * @package    Techxplorer
 * @subpackage Database
 */
class PostgreSQL extends Database
{
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
    public function __construct($db_host, $db_user, $db_password, $db_name)
    {
        parent::__construct($db_host, $db_user, $db_password, $db_name);
    }

    /**
     * Instantiate a connection to the database
     *
     * @return bool true on success
     */
    public function connect()
    {
        $this->db_connection = pg_connect(
            'host=' . $this->db_host .
            ' dbname=' . $this->db_name .
            ' user=' . $this->db_user .
            ' password=' . $this->db_password
        );

        if ($this->db_connection == false) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Get a list of databases available
     *
     * @return mixed boolean|array an array of database names or false on failure
     */
    public function getDatabaseList()
    {
        // Maintain a list of databases to skip
        $skiplist  = array('template0', 'template1', 'postgres');
        $databases = array();

        $result = pg_query(
            $this->db_connection,
            "SELECT datname
             FROM pg_database
             WHERE datistemplate = false
             ORDER BY datname"
        );

        if (!$result) {
            return false;
        }

        while ($row = pg_fetch_row($result)) {
            if (!in_array($row[0], $skiplist)) {
                $databases[] = $row[0];
            }
        }

        return $databases;
    }

    /**
     * Get a list of users associated with one or more databases
     *
     * @param array $databases an array of databases
     *
     * @return array an array of users indexed by database
     *
     * @throws InvalidArgumentException of the arguments are invalid
     */
    public function getUserList($databases)
    {
        if (!is_array($databases) || count($databases) == 0) {
            throw new InvalidArgumentException('The $databses argument must be an array with at least one element');
        }

        $sql = "SELECT u.usename, d.datname
                FROM pg_user u,
                (SELECT datname, split_part(aclexplode(datacl)::varchar, ',', 2) AS userid
                    FROM pg_database
                    GROUP BY datname, userid) AS d
                WHERE u.usesysid::varchar = d.userid
                AND d.datname = $1
                ORDER by u.usename";

        $users = array();

        foreach ($databases as $database) {
            $result = pg_query_params($this->db_connection, $sql, array($database));

            if (!$result) {
                $users[] = array($database, '');
            } else {
                $list = array();

                while ($row = pg_fetch_row($result)) {
                    $list[] = $row[0];
                }

                $users[] = array($database, implode(', ', $list));
            }
        }

        return $users;
    }

    /**
     * Check to see if a user already exists in the system
     *
     * @param string $username the name of the user
     *
     * @return bool true if the user exists, false if they don't
     *
     * @throws InvalidArgumentException if the arguments are invalid
     */
    public function userExists($username)
    {
        if (empty(trim($username))) {
            throw new InvalidArgumentException('The $username argument is required.');
        }

        $result = pg_query_params(
            $this->db_connection,
            'SELECT 1 FROM pg_roles WHERE rolname=$1',
            array(trim($username))
        );

        if ($result == false) {
            return false;
        } else {
            if (pg_fetch_row($result) == true) {
                return true;
            } else {
                return false;
            }
        }
    }

    /**
     * Check to see if a database already exists in the system
     *
     * @param string $database the name of the database
     *
     * @return bool true if the database exists, false if it doesn't
     *
     * @throws InvalidArgumentException if the arguments are invalid
     */
    public function databaseExists($database)
    {
        if (empty(trim($database))) {
            throw new InvalidArgumentException('The $database argument is required.');
        }

        $result = pg_query_params(
            $this->db_connection,
            'SELECT 1 from pg_database WHERE datname=$1',
            array(trim($database))
        );

        if ($result == false) {
            return false;
        } else {
            if (pg_fetch_row($result) == true) {
                return true;
            } else {
                return false;
            }
        }
    }


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
    public function createUser($username, $password)
    {
        if (empty(trim($username))) {
            throw new InvalidArgumentException('The $username argument is required.');
        }

        if (empty(trim($password))) {
            throw new InvalidArgumentException('The $password argument is required.');
        }

        $username = trim($username);
        $password = pg_escape_literal($password);

        $result = pg_query(
            $this->db_connection,
            "CREATE user {$username} WITH PASSWORD {$password}"
        );

        if ($result == false) {
            return false;
        } else {
            return true;
        }
    }

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
    public function createDatabase($database, $username)
    {
        if (empty(trim($username))) {
            throw new InvalidArgumentException('The $username argument is required.');
        }

        if (empty(trim($database))) {
            throw new InvalidArgumentException('The $database argument is required.');
        }

        $database = trim($database);
        $username = trim($username);

        $result = pg_query(
            $this->db_connection,
            "CREATE DATABASE {$database}"
        );

        if (!$result) {
            return false;
        }

        $result = pg_query(
            $this->db_connection,
            "GRANT ALL PRIVILEGES ON DATABASE {$database} to {$username}"
        );

        if (!$result) {
            return false;
        } else {
            return true;
        }
    }

    /**
      * Delete a user in the system
      *
      * @param string $username the name of the user
      *
      * @return bool true on success, false on failure
      *
      * @throws InvalidArgumentException if the arguments are invalid
      */
    public function dropUser($username)
    {
        if (empty(trim($username))) {
            throw new InvalidArgumentException('The $username argument is required.');
        }

        $username = trim($username);

        $result = pg_query($this->db_connection, "DROP USER $username");

        if (!$result) {
            return false;
        } else {
            return true;
        }
    }

    /**
      * Delete a database in the system
      *
      * @param string $database the name of the database
      *
      * @return bool true on success, false on failure
      *
      * @throws InvalidArgumentException if the arguments are invalid
      */
    public function dropDatabase($database)
    {
        if (empty(trim($database))) {
            throw new InvalidArgumentException('The $database argument is required.');
        }

        $database = trim($database);

        $result = pg_query($this->db_connection, "DROP DATABASE $database");

        if (!$result) {
            return false;
        } else {
            return true;
        }
    }


    /**
     * Tidy up any remaining resources
     */
    public function __destruct()
    {
        pg_close($this->db_connection);
    }
}
