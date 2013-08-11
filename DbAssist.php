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

// include the required libraries
require('vendor/autoload.php');

// work around a bug in the Arguments class of the CLI package
$path = __DIR__ . '/vendor/jlogsdon/cli';
set_include_path(get_include_path() . PATH_SEPARATOR . $path);

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
	const SCRIPT_VERSION = 'v1.0'; 
	
	/**
	 * defines the uri for more information
	 */
	const MORE_INFO_URI = 'https://github.com/techxplorer/techxplorer-utils';
	
	/**
	 * defines the license uri
	 */
	const LICENSE_URI = 'http://opensource.org/licenses/GPL-3.0';
	
	/**
	 * define the default role definition file
	 */
	const DEFAULT_CFG_FILE = '/data/db-assist.json.dist';
	
	/**
	 * define the override role definition file
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
		$valid_actions = array('create' => 'Create a database and matching user');
		
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
		call_user_func('self::do_action_' . $action, $arguments, $pg_details, $this);		
	}
	
	/**
	 * used to create a database and matching user
	 *
	 * @since 1.0
	 * @author techxplorer <corey@techxplorer.com>
	 */
	static public function do_action_create($arguments, $pg_details, $self) {
	
		$user = '';
		$database = '';
		$password = '';
		
		// get the required argument options
		if(!$arguments['user']) {
			\cli\out("Error: Missing required option: --user\n\n");
			\cli\out($arguments->getHelpScreen());
			\cli\out("\n\n");
		 	die(-1);
		} else {
			$user = $arguments['user'];
		}
		
		if(!$arguments['database']) {
			\cli\out("Error: Missing required option: --database\n\n");
			\cli\out($arguments->getHelpScreen());
			\cli\out("\n\n");
		 	die(-1);
		} else {
			$database = $arguments['database'];
		}
		
		if(!$arguments['password']) {
			$password = $self->generate_password();
		} else {
			$password = $arguments['password'];
		}
		
		\cli\out("Attempting to create a database with the following settings\n");
		$table = new \cli\Table();
		$table->setHeaders(array('Setting', 'Value'));
		$table->setRows(array(array('Username', $user), array('Password', $password), array('Database Name', $database)));
		$table->display();
		
		// get a connection to the database
		$db_connection = $self->get_db_connection($pg_details);
		
		if($db_connection == false) {
			\cli\err("Error: Unable to connect to the database\n");
			die(-1);
		}
		
		// check if the user already exists
		$result = pg_query_params($db_connection, 'SELECT 1 FROM pg_roles WHERE rolname=$1', array($user));
		
		if(!$result) {
			\cli\err("Error: Unable to check if the user already exists\n");
			pg_close($db_connection);
			die(-1);
		}
		
		if(pg_fetch_row($result) == true) {
			\cli\err("Error: The user already exists\n");
			pg_close($db_connection);
			die(-1);
		}
		
		// check if the database exists
		$result = pg_query_params($db_connection, 'SELECT 1 from pg_database WHERE datname=$1', array($database));
		
		if(!$result) {
			\cli\err("Error: Unable to check if the database already exists\n");
			pg_close($db_connection);
			die(-1);
		}
		
		if(pg_fetch_row($result) == true) {
			\cli\err("Error: The database already exists\n");
			pg_close($db_connection);
			die(-1);
		}
		
		// create the user and then create the database
		$result = pg_query($db_connection, "create user $user with password '$password'");
		
		if(!$result) {
			\cli\err("Error: Unable to create the user record\n");
			pg_close($db_connection);
			die(-1);
		}
		
		$result = pg_query($db_connection, "create database $database");
		
		if(!$result) {
			\cli\err("Error: Unable to create the database\n");
			pg_close($db_connection);
			die(-1);
		}
		
		$result = pg_query($db_connection, "grant all privileges on database $database to $user");
		
		if(!$result) {
			\cli\err("Error: Unable to grant the required privileges\n");
			pg_close($db_connection);
			die(-1);
		}
		
		//play nice and tidy up
		pg_close($db_connection);
		
		\cli\out("Success: the user and matching database has been created.\n");
		
	}
	
	// private function to connect to the database
	private function get_db_connection($pg_details) {
		return pg_connect('host=' . $pg_details['host'] . ' dbname=' . $pg_details['database'] . ' user=' . $pg_details['user'] . ' password=' . $pg_details['password']);
	}
	
	// private function to load configuration details
	// small private function to load the role definition file
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
	
	// function to generate passwords derived from 
	// https://gist.github.com/tylerhall/521810
	// and considered to be in the public domain
	private function generate_password($length = 8, $add_dashes = false, $available_sets = 'luds') {
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