#!/usr/bin/env php
<?php
/*
 * This file is part of Techxplorer's Utility Script Package.
 *
 * Techxplorer's Utility Script Package is free software: you can
 * redistribute it and/or modify it under the terms of the GNU
 * General Public License as published by the Free Software Foundation,
 * either version 3 of the License, or (at your option) any later version.
 *
 * Techxplorer's Utility Script Package is distributed in the hope that
 * it will be useful, but WITHOUT ANY WARRANTY; without even the implied
 * warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Techxplorer's Utility Script Package.
 * If not, see <http://www.gnu.org/licenses/>
 */

// adjust error reporting to aid in development
error_reporting(E_ALL);
ini_set('display_errors', 'stderr');

// include the required libraries
require(__DIR__ . '/vendor/autoload.php');

/**
 * a php script which can be used to help undertake a variety of
 * repetative database related tasks
 *
 * @since 1.0
 * @author techxplorer <corey@techxplorer.com>
 * @license http://opensource.org/licenses/GPL-3.0 GNU Public License v3.0
 * @package Techxplorer-Utils
 */

/**
 * main driving class of Techxplorer's Database Assistant
 *
 * @since 1.0
 * @author techxplorer <corey@techxplorer.com>
 *
 * @copyright 2013 Corey Wallis (techxplorer)
 * @license http://opensource.org/licenses/GPL-3.0
 */
class DbAssist {

	/**
	 * defines a name for the script
	 */
	const SCRIPT_NAME = "Techxplorer's Database Assistant Script";

	/**
	 * defines the version of the script
	 */
	const SCRIPT_VERSION = 'v1.2';

	/**
	 * defines the uri for more information
	 */
	const MORE_INFO_URI = 'https://github.com/techxplorer/techxplorer-utils';

	/**
	 * defines the license uri
	 */
	const LICENSE_URI = 'http://opensource.org/licenses/GPL-3.0';

	/**
	 * define the default configuration file
	 */
	const DEFAULT_CFG_FILE = '/data/db-assist.json.dist';

	/**
	 * define the configuration override file
	 */
	const OVERRIDE_CFG_FILE = '/data/db-assist.json';

	/**
	 * main driving function
	 *
	 * @since 1.0
	 * @author techxplorer <corey@techxplorer.com>
	 */
	public function do_task() {

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

		// get the database configuration
		$pg_details = self::load_pg_details();

		// improve handling of arguments
		$arguments = new \cli\Arguments($_SERVER['argv']);

		$arguments->addOption(array('user', 'u'),
			array(
				'default' => '',
				'description' => 'The PostgreSQL user to use'
			)
		);

		$arguments->addOption(array('database', 'd'),
			array(
				'default' => '',
				'description' => 'The name of the database to use'
			)
		);

		$arguments->addOption(array('password', 'p'),
			array(
				'default' => '',
				'description' => 'The password to use'
			)
		);

		$arguments->addOption(array('action', 'a'),
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
		if($arguments['help']) {
			\cli\out($arguments->getHelpScreen());
			\cli\out("\n\n");
			die(0);
		}

		if($arguments['list']) {

			$actions = array();
			foreach($valid_actions as $key => $value) {
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

		if(!$arguments['action']) {
			\cli\out("Error: Missing required option: --action\n\n");
			\cli\out($arguments->getHelpScreen());
			\cli\out("\n\n");
		 	die(-1);
		}

		$action = trim($arguments['action']);

		if(!array_key_exists($action, $valid_actions)) {
			\cli\out("Error: Invalid action detected.\n Use the -l flag to see a list of valid actions.\n\n");
		 	die(-1);
		}

		// call the appropriate user function
		call_user_func('self::do_action_' . $action, $arguments, $pg_details);
	}

    /**
     * use to gather the minimum required information to undertake a taks
     *
     * @param array $arguments list of command line arguments
     * @return array an array contain the 'user', 'database' and 'password' elements
     *
     * @since 1.0
     * @author techxplorer <corey@techxplorer.com>
     */
    static public function get_arguments($arguments) {

         // get the required argument options
         if(!$arguments['user']) {
             \cli\out("ERROR: Missing required argument: --user\n\n");
             \cli\out($arguments->getHelpScreen());
             \cli\out("\n\n");
             die(-1);
         } else {
             $user = $arguments['user'];
         }

         if(!$arguments['database']) {
             \cli\out("WARNING: Missing optional option: --database\n");
             \cli\out("         Using the user name as the database name\n\n");
             $database = $user;
         } else {
             $database = $arguments['database'];
         }

         if(!$arguments['password']) {
             $password = DbAssist::generate_password();
         } else {
             $password = $arguments['password'];
         }

        return array($user, $database, $password);
    }

	/**
     * create a database and associated user
     *
     * @param array $arguments list of command line arguments
     * @param array $pg_details postgres connection details
     *
     * @since 1.0
     * @author techxplorer <corey@techxplorer.com>
     */
	static public function do_action_create($arguments, $pg_details) {

        list($user, $database, $password) = DbAssist::get_arguments($arguments);

		\cli\out("Attempting to create a database with the following settings\n");
		$table = new \cli\Table();
		$table->setHeaders(array('Setting', 'Value'));
		$table->setRows(array(array('Username', $user), array('Password', $password), array('Database Name', $database)));
		$table->display();

		// get a connection to the database
		$db_connection = DbAssist::get_db_connection($pg_details);

		if($db_connection == false) {
			\cli\err("ERROR: Unable to connect to the database\n");
			die(-1);
		}

		// check if the user already exists
		$result = DbAssist::user_exists($db_connection, $user);

		if(!$result) {
			\cli\err("ERROR: Unable to check if the user already exists\n");
			pg_close($db_connection);
			die(-1);
		}

		if(pg_fetch_row($result) == true) {
			\cli\err("ERROR: The user already exists\n");
			pg_close($db_connection);
			die(-1);
		}

		// check if the database exists
		$result = DbAssist::database_exists($db_connection, $database);

		if(!$result) {
			\cli\err("ERROR: Unable to check if the database already exists\n");
			pg_close($db_connection);
			die(-1);
		}

		if(pg_fetch_row($result) == true) {
			\cli\err("ERROR: The database already exists\n");
			pg_close($db_connection);
			die(-1);
		}

		// create the user and then create the database
		$result = DbAssist::create_user($db_connection, $user, $password);

		if(!$result) {
			\cli\err("ERROR: Unable to create the user\n");
			pg_close($db_connection);
			die(-1);
		}

		$result = DbAssist::create_database($db_connection, $database, $user);

		if(!$result) {
			\cli\err("ERROR: Unable to create the database\n");
			pg_close($db_connection);
			die(-1);
		}

		//play nice and tidy up
		pg_close($db_connection);

		\cli\out("SUCCESS: the user and matching database has been created.\n");
	}

	/**
     * Drop and re-create a database
     *
     * @param array $arguments list of command line arguments
     * @param array $pg_details postgres connection details
     *
     * @since 1.0
     * @author techxplorer <corey@techxplorer.com>
     */
	static public function do_action_empty($arguments, $pg_details) {

		list($user, $database) = DbAssist::get_arguments($arguments);

		\cli\out("Attempting to empty a database with the following settings\n");
		$table = new \cli\Table();
		$table->setHeaders(array('Setting', 'Value'));
		$table->setRows(array(array('Username', $user), array('Database Name', $database)));
		$table->display();

		// get a connection to the database
		$db_connection = DbAssist::get_db_connection($pg_details);

		if($db_connection == false) {
			\cli\err("Error: Unable to connect to the database\n");
			die(-1);
		}

		// check if the user already exists
		$result = DbAssist::user_exists($db_connection, $user);

		if(!$result) {
			\cli\err("Error: Unable to check if the user already exists\n");
			pg_close($db_connection);
			die(-1);
		}

		if(!pg_fetch_row($result) == true) {
			\cli\err("Error: The user doesn't exist\n");
			pg_close($db_connection);
			die(-1);
		}

		// check if the database exists
		$result = DbAssist::database_exists($db_connection, $database);

		if(!$result) {
			\cli\err("Error: Unable to check if the database already exists\n");
			pg_close($db_connection);
			die(-1);
		}

		if(!pg_fetch_row($result) == true) {
			\cli\err("Error: The database doesn't exist\n");
			pg_close($db_connection);
			die(-1);
		}

		// drop the existing database and then recreate it
		$result = DbAssist::drop_database($db_connection, $database, $user);

		if(!$result) {
			\cli\err("Error: Unable to drop the existing database\n");
			pg_close($db_connection);
			die(-1);
		}

		$result = DbAssist::create_database($db_connection, $database, $user);

		if(!$result) {
			\cli\err("Error: Unable to create the database\n");
			pg_close($db_connection);
			die(-1);
		}

		//play nice and tidy up
		pg_close($db_connection);

		\cli\out("Success: the specified database has been dropped and recreated.\n");

	}

	/**
     * Drop a database and the associated user
     *
     * @param array $arguments list of command line arguments
     * @param array $pg_details postgres connection details
     *
     * @since 1.0
     * @author techxplorer <corey@techxplorer.com>
     */
	static public function do_action_delete($arguments, $pg_details) {

		list($user, $database) = DbAssist::get_arguments($arguments);

		\cli\out("Attempting to delete a database and matching user with the following settings\n");
		$table = new \cli\Table();
		$table->setHeaders(array('Setting', 'Value'));
		$table->setRows(array(array('Username', $user), array('Database Name', $database)));
		$table->display();

		// get a connection to the database
		$db_connection = DbAssist::get_db_connection($pg_details);

		if($db_connection == false) {
			\cli\err("Error: Unable to connect to the database\n");
			die(-1);
		}

		// check if the user already exists
		$result = DbAssist::user_exists($db_connection, $user);

		if(!$result) {
			\cli\err("Error: Unable to check if the user already exists\n");
			pg_close($db_connection);
			die(-1);
		}

		if(!pg_fetch_row($result) == true) {
			\cli\err("Error: The user doesn't exist\n");
			pg_close($db_connection);
			die(-1);
		}

		// check if the database exists
		$result = DbAssist::database_exists($db_connection, $database);

		if(!$result) {
			\cli\err("Error: Unable to check if the database already exists\n");
			pg_close($db_connection);
			die(-1);
		}

		if(!pg_fetch_row($result) == true) {
			\cli\err("Error: The database doesn't exist\n");
			pg_close($db_connection);
			die(-1);
		}

		// drop the existing database
		$result = DbAssist::drop_database($db_connection, $database);

		if(!$result) {
			\cli\err("Error: Unable to drop the existing database\n");
			pg_close($db_connection);
			die(-1);
		}

		// drop the existing user
		$result = DbAssist::drop_user($db_connection, $user);

		if(!$result) {
			\cli\err("Error: Unable to delete the existing user\n");
			pg_close($db_connection);
			die(-1);
		}

		//play nice and tidy up
		pg_close($db_connection);

		\cli\out("Success: the specified database and user has been deleted.\n");
	}

	/**
     * List databases and thie associated users
     *
     * @param array $arguments list of command line arguments
     * @param array $pg_details postgres connection details
     *
     * @since 1.0
     * @author techxplorer <corey@techxplorer.com>
     */
	static public function do_action_list($arguments, $pg_details) {

		\cli\out("Attempting to list all databases and users\n");
		$table = new \cli\Table();
		$table->setHeaders(array('Databases', 'User List'));

		// get a connection to the database
		$db_connection = DbAssist::get_db_connection($pg_details);

		if($db_connection == false) {
			\cli\err("Error: Unable to connect to the database\n");
			die(-1);
		}

		// get a list of databases
		$databases = DbAssist::get_database_list($db_connection);

		// get a list of users
		$list = DbAssist::get_user_list($db_connection, $databases);
		$table->setRows($list);
		$table->display();

		//play nice and tidy up
		pg_close($db_connection);
	}

    /**
     * get a list of users associated with a list of databases
     *
     * @param resource $db_connection to the database
     * @param array $databases list of databases
     *
     * @return array list of users and databases
     *
     * @since 1.0
     * @author techxplorer <corey@techxplorer.com>
     */
	static public function get_user_list($db_connection, $databases) {

	$sql = "SELECT u.usename, d.datname
FROM pg_user u,
     (SELECT datname, split_part(aclexplode(datacl)::varchar, ',', 2) AS userid
      FROM pg_database
      GROUP BY datname, userid) AS d
WHERE u.usesysid::varchar = d.userid
AND d.datname = $1
ORDER by u.usename";

		$users = array();

		foreach($databases as $database) {

			$result = pg_query_params($db_connection, $sql, array($database));

			if(!$result) {
				$users[$database] = '';
			} else {
				$user_list = '';
				while ($row = pg_fetch_row($result)) {
					$user_list .= $row[0] . ', ';
				}

				$user_list = substr($user_list, 0, strlen($user_list) -2 );
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
     * @param array list of databases
     *
     * @return array list of databases
     *
     * @since 1.0
     * @author techxplorer <corey@techxplorer.com>
     */
	public static function get_database_list($db_connection) {

		// skip databases
		$skip = array('template0', 'template1', 'postgres');
		$databases = array();

		$result = pg_query($db_connection, 'SELECT datname FROM pg_database WHERE datistemplate = false ORDER BY datname');

		if(!$result) {
			return false;
		}

		while ($row = pg_fetch_row($result)) {

			if(!in_array($row[0], $skip)) {
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
     * @param string $user the name of the user to drop
     *
     * @return resource result of executing the query
     *
     * @since 1.0
     * @author techxplorer <corey@techxplorer.com>
     */
	static public function drop_user($db_connection, $user) {
		return pg_query($db_connection, "drop user $user");
	}

	/**
     * drop a database
     *
     * @param resource $db_connection to the database
     * @param string $database the name of the database to drop
     *
     * @return resource result of executing the query
     *
     * @since 1.0
     * @author techxplorer <corey@techxplorer.com>
     */
	static public function drop_database($db_connection, $database) {
		return pg_query($db_connection, "drop database $database");
	}

	/**
     * create a database and associate a user with it
     *
     * @param resource $db_connection to the database
     * @param string $database the name of the database
     * @param string $user the name of the user to associate with it
     *
     * @return resource result of executing the query
     *
     * @since 1.0
     * @author techxplorer <corey@techxplorer.com>
     */
	static public function create_database($db_connection, $database, $user) {
		$result = pg_query($db_connection, "create database $database");

		if(!$result) {
			return false;
		}

		return pg_query($db_connection, "grant all privileges on database $database to $user");
	}

	/**
     * create a user
     *
     * @param resource $db_connection to the database
     * @param string $user the name of the user to create
     * @param string $password the password for the new user
     *
     * @return resource result of executing the query
     *
     * @since 1.0
     * @author techxplorer <corey@techxplorer.com>
     */
	static public function create_user($db_connection, $user, $password) {
		return pg_query($db_connection, "create user $user with password '$password'");

	}

	/**
     * check to see if the database exists
     *
     * @param resource $db_connection to the database
     * @param string $database the name of the database
     *
     * @return resource result of executing the query
     *
     * @since 1.0
     * @author techxplorer <corey@techxplorer.com>
     */
	static public function database_exists($db_connection, $database) {
		return pg_query_params($db_connection, 'SELECT 1 from pg_database WHERE datname=$1', array($database));
	}

	/**
     * check to see if the user exists
     *
     * @param resource $db_connection to the database
     * @param string $user the name of the database
     *
     * @return resource result of executing the query
     *
     * @since 1.0
     * @author techxplorer <corey@techxplorer.com>
     */
    static public function user_exists($db_connection, $user) {
		return pg_query_params($db_connection, 'SELECT 1 FROM pg_roles WHERE rolname=$1', array($user));
	}

	/**
     * get a connection to the database
     *
     * @param resource $db_connection to the database
     * @param string $user the name of the database
     *
     * @return resource result of executing the query
     *
     * @since 1.0
     * @author techxplorer <corey@techxplorer.com>
     */
    static public function get_db_connection($pg_details) {
		return pg_connect('host=' . $pg_details['host'] . ' dbname=' . $pg_details['database'] . ' user=' . $pg_details['user'] . ' password=' . $pg_details['password']);
	}

	/**
     * load the database connection details from the default or override config file
     *
     * @param resource $db_connection to the database
     * @param string $user the name of the database
     *
     * @return resource result of executing the query
     *
     * @since 1.0
     * @author techxplorer <corey@techxplorer.com>
     */
	private function load_pg_details() {

		$pg_details = false;

		// start with the override file
		if(is_readable(__DIR__ . self::OVERRIDE_CFG_FILE)) {
			$pg_details = json_decode(file_get_contents(__DIR__ . self::OVERRIDE_CFG_FILE), true);

			if($pg_details == null) {
				\cli\err("ERROR: Unable to load the config override file:\n" . self::OVERRIDE_CFG_FILE . "\n");
				die(-1);
			}
		}

		// try the default role file
		if($pg_details == false) {
			// try the default file
			if(!is_readable(__DIR__ . self::DEFAULT_CFG_FILE)) {
				\cli\err("ERROR: Unable to locate the default role file:\n" . self::DEFAULT_CFG_FILE . "\n");
				die(-1);
			} else {
				$pg_details = json_decode(file_get_contents(__DIR__ . self::DEFAULT_CFG_FILE), true);
			}

			if($pg_details == null) {
				\cli\err("ERROR: Unable to load the default cfg file:\n" . self::DEFAULT_CFG_FILE . "\n");
				die(-1);
			}
		}

		return $pg_details;
	}

    /**
     * generate a password
     *
     * @param integer $length the length of the dabase
     * @param bool $add_dashes add dashes to the password
     * @param string $available_sets the list of available character sets
     *
     * @param resource $db_connection to the database
     * @param string $user the name of the database
     *
     * @return resource result of executing the query
     *
     * @since 1.0
     * @link https://gist.github.com/tylerhall/521810 Original Implementation (public domain)
     */
	public static function generate_password($length = 8, $add_dashes = false, $available_sets = 'luds') {
		$sets = array();
		if(strpos($available_sets, 'l') !== false)
			$sets[] = 'abcdefghjkmnpqrstuvwxyz';
		if(strpos($available_sets, 'u') !== false)
			$sets[] = 'ABCDEFGHJKMNPQRSTUVWXYZ';
		if(strpos($available_sets, 'd') !== false)
			$sets[] = '23456789';
		if(strpos($available_sets, 's') !== false)
			$sets[] = '!@#$&*?';

		$all = '';
		$password = '';
		foreach($sets as $set)
		{
			$password .= $set[array_rand(str_split($set))];
			$all .= $set;
		}

		$all = str_split($all);
		for($i = 0; $i < $length - count($sets); $i++)
			$password .= $all[array_rand($all)];

		$password = str_shuffle($password);

		if(!$add_dashes)
			return $password;

		$dash_len = floor(sqrt($length));
		$dash_str = '';
		while(strlen($password) > $dash_len)
		{
			$dash_str .= substr($password, 0, $dash_len) . '-';
			$password = substr($password, $dash_len);
		}
		$dash_str .= $password;
		return $dash_str;
	}
}


// make sure script is only run on the cli
if(substr(php_sapi_name(), 0, 3) == 'cli') {
	// yes
	$app = new DbAssist();
	$app->do_task();
} else {
	// no
	die("This script can only be run on the cli\n");
}
?>