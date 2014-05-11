#!/usr/bin/env php
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
 * This is a PHP script which automates a variety of DB related tasks
 *
 * PHP Version 5.4 
 *
 * @category TechxplorerUtils
 * @package  TechxplorerUtils
 * @author   techxplorer <corey@techxplorer.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     https://github.com/techxplorer/techxplorer-utils
 */

// adjust error reporting to aid in development
error_reporting(E_ALL);

// include the required libraries
require __DIR__ . '/vendor/autoload.php';

// shorten namespaces
use \Techxplorer\Utils\Files as Files;
use \Techxplorer\Utils\System as System;
use \Techxplorer\Utils\Password as Password;

use \Techxplorer\Utils\FileNotFoundException;
use \Techxplorer\Utils\ConfigParseException;

/**
 * Main driving class of the script
 *
 * @category TechxplorerUtils
 * @package  TechxplorerUtils
 * @author   techxplorer <corey@techxplorer.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     https://github.com/techxplorer/techxplorer-utils
 *
 */
class DbAssist
{
    /**
     * defines a name for the script
     */
    const SCRIPT_NAME = "Techxplorer's Database Assistant Script";

    /**
     * defines the version of the script
     */
    const SCRIPT_VERSION = 'v1.2.1';

    /**
     * defines the uri for more information
     */
    const MORE_INFO_URI = 'https://github.com/techxplorer/techxplorer-utils';

    /**
     * defines the license uri
     */
    const LICENSE_URI = 'http://www.gnu.org/copyleft/gpl.html';

    /**
     * define the default configuration file
     */
    const DEFAULT_CONFIG_FILE = 'db-assist.json';

    /**
     * main driving function
     *
     * @return void
     */
    public function doTask()
    {
        // output some helper text
        \cli\out(self::SCRIPT_NAME . ' - ' . self::SCRIPT_VERSION . "\n");
        \cli\out('License: ' . self::LICENSE_URI . "\n\n");

        // define a list of valid actions
        $valid_actions = array(
            'create' => 'Create a database and matching user',
            'empty'  => 'Empty (recreate) a database',
            'delete' => 'Delete a database and matching user',
            'list'   => 'List all databases and users'

        );

        // read in the default config
        try {
            $config_path = __DIR__ . '/data/' . self::DEFAULT_CONFIG_FILE;
            $config = Files::loadConfig($config_path);
        } catch (FileNotFoundException $ex) {
            \cli\err(
                "%rERROR: %wUnable to find configuration file:\n" .
                $config_path . 
                "\n"
            );  
            die(1);
        } catch (ConfigParseException $ex) {
            \cli\err(
                "%rERROR: %wUnable to load configuration file:\n" . 
                $config_path . 
                "\n"
            );  
            die(1);
        }

        // improve handling of arguments
        $arguments = new \cli\Arguments($_SERVER['argv']);

        $arguments->addOption(
            array('user', 'u'),
            array(
                'default' => '',
                'description' => 'The PostgreSQL user to use'
            )
        );

        $arguments->addOption(
            array('database', 'd'),
            array(
                'default' => '',
                'description' => 'The name of the database to use'
            )
        );

        $arguments->addOption(
            array('password', 'p'),
            array(
                'default' => '',
                'description' => 'The password to use'
            )
        );

        $arguments->addOption(
            array('action', 'a'),
            array(
                'default' => '',
                'description' => 'The name of the action to undertake'
            )
        );

        $arguments->addFlag(array('help', 'h'), 'Show this help screen');
        $arguments->addFlag(array('list', 'l'), 'List available actions');

        // parse the arguments
        $arguments->parse();

        // show the help screen if required
        if ($arguments['help']) {
            \cli\out($arguments->getHelpScreen());
            \cli\out("\n\n");
            die(0);
        }

        if ($arguments['list']) {

            $actions = array();
            foreach ($valid_actions as $key => $value) {
                $actions[] = array($key, $value);
            }

            // output the table
            \cli\out("List of available actions:\n");
            $table = new \cli\Table();
            $table->setHeaders(array('Action', 'Description'));
            $table->setRows($actions);
            $table->display();
            die(0);
        }

        if (!$arguments['action']) {
            \cli\out("Error: Missing required option: --action\n\n");
            \cli\out($arguments->getHelpScreen());
            \cli\out("\n\n");
            die(1);
        }

        $action = trim($arguments['action']);

        if (!array_key_exists($action, $valid_actions)) {
            \cli\out(
                "Error: Invalid action detected.\n" . 
                "Use the -l flag to see a list of valid actions.\n"
            );
            die(1);
        }

        // call the appropriate user function
        call_user_func(
            'self::doAction' . ucfirst($action),
            $arguments,
            $config
        );
    }

    /**
     * Gather the minimum required info for a task
     *
     * @param array $arguments list of command line arguments
     *
     * @return array an array containing the argument values
     *
     */
    public static function getArguments($arguments)
    {
        // get the required argument options
        if (!$arguments['user']) {
            \cli\out("ERROR: Missing required argument: --user\n\n");
            \cli\out($arguments->getHelpScreen());
            \cli\out("\n\n");
            die(1);
        } else {
            $user = $arguments['user'];
        }

        if (!$arguments['database']) {
            \cli\out("WARNING: Missing optional option: --database\n");
            \cli\out("         Using the user name as the database name\n\n");
            $database = $user;
        } else {
            $database = $arguments['database'];
        }

        if (!$arguments['password']) {
            $password = Password::generate();
        } else {
            $password = $arguments['password'];
        }

        return array($user, $database, $password);
    }

    /**
     * create a database and associated user
     *
     * @param array $arguments  list of command line arguments
     * @param array $pg_details postgres connection details
     *
     * @return void
     *
     */
    public static function doActionCreate($arguments, $pg_details)
    {
        list($user, $database, $password) = DbAssist::getArguments($arguments);

        \cli\out(
            "Attempting to create a database with the following settings\n"
        );

        $table = new \cli\Table();
        $table->setHeaders(array('Setting', 'Value'));
        $table->setRows(
            array(
                array('Username', $user),
                array('Password', $password),
                array('Database Name', $database)
            )
        );
        $table->display();

        // get a connection to the database
        $db_connection = DbAssist::getConnection($pg_details);

        if ($db_connection == false) {
            \cli\err("ERROR: Unable to connect to the database\n");
            die(1);
        }

        // check if the user already exists
        $result = DbAssist::userExists($db_connection, $user);

        if (!$result) {
            \cli\err("ERROR: Unable to check if the user already exists\n");
            pg_close($db_connection);
            die(1);
        }

        if (pg_fetch_row($result) == true) {
            \cli\err("ERROR: The user already exists\n");
            pg_close($db_connection);
            die(1);
        }

        // check if the database exists
        $result = DbAssist::databaseExists($db_connection, $database);

        if (!$result) {
            \cli\err("ERROR: Unable to check if the database already exists\n");
            pg_close($db_connection);
            die(1);
        }

        if (pg_fetch_row($result) == true) {
            \cli\err("ERROR: The database already exists\n");
            pg_close($db_connection);
            die(1);
        }

        // create the user and then create the database
        $result = DbAssist::createUser($db_connection, $user, $password);

        if (!$result) {
            \cli\err("ERROR: Unable to create the user\n");
            pg_close($db_connection);
            die(1);
        }

        $result = DbAssist::createDatabase($db_connection, $database, $user);

        if (!$result) {
            \cli\err("ERROR: Unable to create the database\n");
            pg_close($db_connection);
            die(1);
        }

        //play nice and tidy up
        pg_close($db_connection);

        \cli\out("SUCCESS: the user and matching database has been created.\n");
    }

    /**
     * Drop and re-create a database
     *
     * @param array $arguments  list of command line arguments
     * @param array $pg_details postgres connection details
     *
     * @return void
     */
    public static function doActionEmpty($arguments, $pg_details)
    {
        list($user, $database) = DbAssist::getArguments($arguments);

        \cli\out("Attempting to empty a database with the following settings\n");
        $table = new \cli\Table();
        $table->setHeaders(array('Setting', 'Value'));
        $table->setRows(
            array(
                array('Username', $user),
                array('Database Name', $database)
            )
        );
        $table->display();

        // get a connection to the database
        $db_connection = DbAssist::getConnection($pg_details);

        if ($db_connection == false) {
            \cli\err("Error: Unable to connect to the database\n");
            die(1);
        }

        // check if the user already exists
        $result = DbAssist::userExists($db_connection, $user);

        if (!$result) {
            \cli\err("Error: Unable to check if the user already exists\n");
            pg_close($db_connection);
            die(1);
        }

        if (!pg_fetch_row($result) == true) {
            \cli\err("Error: The user doesn't exist\n");
            pg_close($db_connection);
            die(1);
        }

        // check if the database exists
        $result = DbAssist::databaseExists($db_connection, $database);

        if (!$result) {
            \cli\err("Error: Unable to check if the database already exists\n");
            pg_close($db_connection);
            die(1);
        }

        if (!pg_fetch_row($result) == true) {
            \cli\err("Error: The database doesn't exist\n");
            pg_close($db_connection);
            die(1);
        }

        // drop the existing database and then recreate it
        $result = DbAssist::dropDatabase($db_connection, $database, $user);

        if (!$result) {
            \cli\err("Error: Unable to drop the existing database\n");
            pg_close($db_connection);
            die(1);
        }

        $result = DbAssist::createDatabase($db_connection, $database, $user);

        if (!$result) {
            \cli\err("Error: Unable to create the database\n");
            pg_close($db_connection);
            die(1);
        }

        //play nice and tidy up
        pg_close($db_connection);

        \cli\out(
            "Success: the specified database has been dropped and recreated.\n"
        );
    }

    /**
     * Drop a database and the associated user
     *
     * @param array $arguments  list of command line arguments
     * @param array $pg_details postgres connection details
     *
     * @return void
     *
     */
    public static function doActionDelete($arguments, $pg_details)
    {
        list($user, $database) = DbAssist::getArguments($arguments);

        \cli\out(
            "Attempting to delete the following database and user\n"
        );
        $table = new \cli\Table();
        $table->setHeaders(array('Setting', 'Value'));
        $table->setRows(
            array(
                array('Username', $user),
                array('Database Name', $database)
            )
        );
        $table->display();

        // get a connection to the database
        $db_connection = DbAssist::getConnection($pg_details);

        if ($db_connection == false) {
            \cli\err("Error: Unable to connect to the database\n");
            die(1);
        }

        // check if the user already exists
        $result = DbAssist::userExists($db_connection, $user);

        if (!$result) {
            \cli\err("Error: Unable to check if the user already exists\n");
            pg_close($db_connection);
            die(1);
        }

        if (!pg_fetch_row($result) == true) {
            \cli\err("Error: The user doesn't exist\n");
            pg_close($db_connection);
            die(1);
        }

        // check if the database exists
        $result = DbAssist::databaseExists($db_connection, $database);

        if (!$result) {
            \cli\err("Error: Unable to check if the database already exists\n");
            pg_close($db_connection);
            die(1);
        }

        if (!pg_fetch_row($result) == true) {
            \cli\err("Error: The database doesn't exist\n");
            pg_close($db_connection);
            die(1);
        }

        // drop the existing database
        $result = DbAssist::dropDatabase($db_connection, $database);

        if (!$result) {
            \cli\err("Error: Unable to drop the existing database\n");
            pg_close($db_connection);
            die(1);
        }

        // drop the existing user
        $result = DbAssist::dropUser($db_connection, $user);

        if (!$result) {
            \cli\err("Error: Unable to delete the existing user\n");
            pg_close($db_connection);
            die(1);
        }

        //play nice and tidy up
        pg_close($db_connection);

        \cli\out("Success: the specified database and user has been deleted.\n");
    }

    /**
     * List databases and thie associated users
     *
     * @param array $arguments  list of command line arguments
     * @param array $pg_details postgres connection details
     *
     * @return void
     *
     */
    public static function doActionList($arguments, $pg_details)
    {
        \cli\out("Attempting to list all databases and users\n");
        $table = new \cli\Table();
        $table->setHeaders(array('Databases', 'User List'));

        // get a connection to the database
        $db_connection = DbAssist::getConnection($pg_details);

        if ($db_connection == false) {
            \cli\err("Error: Unable to connect to the database\n");
            die(1);
        }

        // get a list of databases
        $databases = DbAssist::getDatabaseList($db_connection);

        // get a list of users
        $list = DbAssist::getUserList($db_connection, $databases);
        $table->setRows($list);
        $table->display();

        //play nice and tidy up
        pg_close($db_connection);
    }

    /**
     * get a list of users associated with a list of databases
     *
     * @param resource $db_connection to the database
     * @param array    $databases     list of databases
     *
     * @return array list of users and databases
     *
     */
    public static function getUserList($db_connection, $databases)
    {
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

            $result = pg_query_params($db_connection, $sql, array($database));

            if (!$result) {
                $users[$database] = '';
            } else {
                $user_list = '';
                while ($row = pg_fetch_row($result)) {
                    $user_list .= $row[0] . ', ';
                }

                $user_list = substr($user_list, 0, strlen($user_list) -2);
                $users[] = array($database, $user_list);
            }

            $result = null;
        }

        return $users;
    }

    /**
     * get a list of databases
     *
     * @param resource $db_connection to the database
     *
     * @return array list of databases
     */
    public static function getDatabaseList($db_connection)
    {
        // skip databases
        $skip = array('template0', 'template1', 'postgres');
        $databases = array();

        $result = pg_query(
            $db_connection, 
            "SELECT datname 
             FROM pg_database 
             WHERE datistemplate = false 
             ORDER BY datname"
        );

        if (!$result) {
            return false;
        }

        while ($row = pg_fetch_row($result)) {

            if (!in_array($row[0], $skip)) {
                $databases[] = $row[0];
            }
        }

        // play nice and tidy up
        $result = null;

        return $databases;
    }

    /**
     * drop a user
     *
     * @param resource $db_connection to the database
     * @param string   $user          the name of the user to drop
     *
     * @return resource result of executing the query
     *
     */
    public static function dropUser($db_connection, $user)
    {
        return pg_query($db_connection, "drop user $user");
    }

    /**
     * drop a database
     *
     * @param resource $db_connection to the database
     * @param string   $database      the name of the database to drop
     *
     * @return resource result of executing the query
     *
     */
    public static function dropDatabase($db_connection, $database)
    {
        return pg_query($db_connection, "drop database $database");
    }

    /**
     * create a database and associate a user with it
     *
     * @param resource $db_connection to the database
     * @param string   $database      the name of the database
     * @param string   $user          the name of the user to associate with it
     *
     * @return resource result of executing the query
     *
     */
    public static function createDatabase($db_connection, $database, $user)
    {
        $result = pg_query($db_connection, "create database $database");

        if (!$result) {
            return false;
        }

        return pg_query(
            $db_connection,
            "grant all privileges on database $database to $user"
        );
    }

    /**
     * create a user
     *
     * @param resource $db_connection to the database
     * @param string   $user          the name of the user to create
     * @param string   $password      the password for the new user
     *
     * @return resource result of executing the query
     *
     */
    public static function createUser($db_connection, $user, $password)
    {
        return pg_query(
            $db_connection, 
            "create user $user with password '$password'"
        );
    }

    /**
     * check to see if the database exists
     *
     * @param resource $db_connection to the database
     * @param string   $database      the name of the database
     *
     * @return resource result of executing the query
     *
     */
    public static function databaseExists($db_connection, $database)
    {
        return pg_query_params(
            $db_connection, 
            'SELECT 1 from pg_database WHERE datname=$1',
            array($database)
        );
    }

    /**
     * check to see if the user exists
     *
     * @param resource $db_connection to the database
     * @param string   $user          the name of the database
     *
     * @return resource result of executing the query
     *
     */
    public static function userExists($db_connection, $user)
    {
        return pg_query_params(
            $db_connection,
            'SELECT 1 FROM pg_roles WHERE rolname=$1',
            array($user)
        );
    }

    /**
     * get a connection to the database
     *
     * @param array $pg_details the config information
     *
     * @return resource result of executing the query
     *
     */
    public static function getConnection($pg_details)
    {
        return pg_connect(
            'host=' .
            $pg_details['host'] .
            ' dbname=' .
            $pg_details['database'] .
            ' user=' .
            $pg_details['user'] .
            ' password=' .
            $pg_details['password']
        );
    }
}

// make sure script is only run on the cli
if (!System::isOnCLI()) {
    die("This script can only be run on the CLI on Mac OS X");
} else {
    $app = new DbAssist();
    $app->doTask();
}
