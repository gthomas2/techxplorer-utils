#!/usr/bin/env php
<?php
/*
 * This file is part of Techxplorer's Moodle User List Creator script.
 *
 * Techxplorer's Moodle User List Creator script is free software: you can redistribute it
 * and/or modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * Techxplorer's Moodle User List Creator script is distributed in the hope that it will
 * be useful, but WITHOUT ANY WARRANTY; without even the implied
 * warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Techxplorer's Moodle User List Creator script.
 * If not, see <http://www.gnu.org/licenses/>
 */

// adjust error reporting to aid in development
error_reporting(E_ALL);

// include the required libraries
require(__DIR__ . '/vendor/autoload.php');

/**
 * a php script which can be used to create a file of
 * user records for uploading into moodle
 *
 * @since 1.0
 * @author techxplorer <corey@techxplorer.com>
 * @license http://opensource.org/licenses/GPL-3.0 GNU Public License v3.0
 * @package Techxplorer-Utils
 */

/**
 * main driving class of Techxplorer's User Moodle List Creator
 *
 * @since 1.0
 * @author techxplorer <corey@techxplorer.com>
 *
 * @copyright 2013 Corey Wallis (techxplorer)
 * @license http://opensource.org/licenses/GPL-3.0
 */
class MdlUserListCreator {

	/**
	 * defines a name for the script
	 */
	const SCRIPT_NAME = "Techxplorer's Moodle User List Creator Script";

	/**
	 * defines the version of the script
	 */
	const SCRIPT_VERSION = 'v1.0.2';

	/**
	 * defines the uri for more information
	 */
	const MORE_INFO_URI = 'https://github.com/techxplorer/techxplorer-utils';

	/**
	 * defines the license uri
	 */
	const LICENSE_URI = 'http://opensource.org/licenses/GPL-3.0';

	/**
	 * defines the male name path
	 */
	const MALE_DATA_PATH = '/data/male-names.txt';

	/**
	 * define the female name path
	 */
	const FEMALE_DATA_PATH = '/data/female-names.txt';

	/**
	 * defines the last name path
	 */
	const LAST_NAME_DATA_PATH = '/data/last-names.txt';

	/**
	 * define the default role definition file
	 */
	const DEFAULT_ROLES_FILE = '/data/mdl-roles.json.dist';

	/**
	 * define the override role definition file
	 */
	const OVERRIDE_ROLES_FILE = '/data/mdl-roles.json';

	/**
	 * defines the default number records to create
	 */
	const DEFAULT_RECORD_COUNT = 100;

    /**
     * defines the default domain for email addresses
     */
    const DEFAULT_EMAIL_DOMAIN = 'example.com';

	/**
	 * main driving function
	 *
	 * @since 1.0
	 * @author techxplorer <corey@techxplorer.com>
	 */
	public function create_file() {

        // output some helper text
		\cli\out(self::SCRIPT_NAME . ' - ' . self::SCRIPT_VERSION . "\n");
		\cli\out('License: ' . self::LICENSE_URI . "\n\n");

		// improve handling of arguments
		$arguments = new \cli\Arguments($_SERVER['argv']);

		$arguments->addOption(array('output', 'o'),
			array(
				'default' => '',
				'description' => 'Set the path to the output file'
			)
		);

		$arguments->addOption(array('number', 'n'),
			array(
				'default' => self::DEFAULT_RECORD_COUNT,
				'description' => 'Set the number of records to create'
			)
		);

		$arguments->addOption(array('domain', 'd'),
			array (
				'default' => self::DEFAULT_EMAIL_DOMAIN,
				'description' => 'The domain name to use when generating email addresses'
			)
		);

		$arguments->addOption(array('course', 'c'),
			array (
				'default' => '',
				'description' => 'The short code of a course to enrol the users in'
			)
		);

		$arguments->addOption(array('role', 'r'),
			array(
				'default' => '',
				'description' => 'Assign the specified role to the generated users'
			)
		);

		$arguments->addFlag(array('help', 'h'), 'Show this help screen');
		$arguments->addFlag(array('list-roles', 'l'), 'List available roles');

		// parse the arguments
		$arguments->parse();

		// show the help screen if required
		if($arguments['help']) {
			\cli\out($arguments->getHelpScreen());
			\cli\out("\n\n");
			die(0);
		}

		// output the list of avaialble roles
		if($arguments['list-roles']) {

			// load the role data
			$role_def = $this->load_role_definitions();
			$roles = array();

			// turn the associative array into an array for output
			foreach($role_def as $key => $value) {
				$roles[] = array($key, $value);
			}

			// output the table
			\cli\out("List of available roles\n");
			$table = new \cli\Table();
			$table->setHeaders(array('ID', 'Description'));
			$table->setRows($roles);
			$table->display();
			die(0);
		}

		// check the arguments
		if(!$arguments['output']) {
			\cli\out("Error: Missing required argument --output\n");
			\cli\out($arguments->getHelpScreen());
			\cli\out("\n\n");
		 	die(-1);
		} else {
			$output_path = $arguments['output'];
	 		$output_path = realpath(dirname($output_path)) . '/' . basename($output_path);
		}

		// check to make sure the file doesn't already exist
	 	if(file_exists($output_path) == true) {
		 	\cli\err("ERROR: The output file already exists\n");
		 	die(-1);
	 	}

	 	if(!$arguments['number']) {
		 	$required_records = self::DEFAULT_RECORD_COUNT;
	 	} else {
		 	$required_records = $arguments['number'];

		 	if(!is_numeric($required_records)) {
			 	\cli\err("ERROR: The record number must be numeric\n");
			 	die(-1);
		 	}

		 	if((int)$required_records != $required_records) {
			 	\cli\err("ERROR: The record number must be a whole number\n");
			 	die(-1);
		 	}

		 	$required_records = (int)$required_records;
	 	}

		if(!$arguments['domain']) {
			$domain_name = self::DEFAULT_EMAIL_DOMAIN;
		} else {
			$domain_name = $arguments['domain'];
		}

		if(!$arguments['course']) {
			$course = false;
		} else {
			$course = $arguments['course'];
		}

		if(!$arguments['role']) {
		    $role = false;
		} else {

			// using role only makes sense if a course is specified
			if(!$course) {
				\cli\err("ERROR: you can only specify a role in conjunction with a course\n");
				die(-1);
			}

            // get the role definition list
			$role_def = $this->load_role_definitions();

			// is this is a numeric or string role
			if(!is_numeric($arguments['role'])) {
			    // string
			    $tmp = strtolower(trim($arguments['role']));

    			foreach($role_def as $key => $value) {
    			    if($value == $tmp) {
        			    $role = $key;
    			    }
    			}

    			if($role === false) {
        			\cli\err("ERROR: Un-recognised role name '$role'\n");
        			die(-1);
    			}

			} else {
    			// numeric
    			if(!array_key_exists($arguments['role'], $role_def)) {
        			\cli\err("ERROR: Un-recognised role id '{$arguments['role']}'\n");
        			die(-1);
    			}

    			$role = $arguments['role'];
			}
		}

	 	// check to make sure we can access the data files
	 	if(!is_readable(__DIR__ . self::MALE_DATA_PATH)) {
		 	\cli\err("ERROR: Unable to access the male name data file\n");
			die(-1);
	 	}

	 	if(!is_readable(__DIR__ . self::FEMALE_DATA_PATH)) {
		 	\cli\err("ERROR: Unable to access the female name data file\n");
			die(-1);
	 	}

	 	if(!is_readable(__DIR__ . self::LAST_NAME_DATA_PATH)) {
		 	\cli\err("ERROR: Unable to access the last name data file\n");
			die(-1);
	 	}

	 	// read in the data files
	 	$male_data = $this->get_data(__DIR__ . self::MALE_DATA_PATH);

	 	if($male_data == false) {
		 	\cli\err("ERROR: Unable to read the male name data file\n");
			die(-1);
	 	}

	 	$female_data = $this->get_data(__DIR__ . self::FEMALE_DATA_PATH);

	 	if($female_data == false) {
		 	\cli\err("ERROR: Unable to read the female name data file\n");
			die(-1);
	 	}

	 	$surname_data = $this->get_data(__DIR__ . self::LAST_NAME_DATA_PATH);

	 	if($surname_data == false) {
		 	\cli\err("ERROR: Unable to read the female name data file\n");
			die(-1);
	 	}

	 	// get the size of the arrays for use later
	 	$male_count    = count($male_data) - 1;
	 	$female_count  = count($female_data) - 1;
	 	$surname_count = count($surname_data) - 1;

	 	// output some information
	 	if($course != false){
		 	\cli\out("Creating file of $required_records user records\n");
		 	\cli\out("  with email addresses @ $domain_name\n");
		 	\cli\out("  with course short code: $course\n");
		 	if(!$role) {
    		 	\cli\out("  with role id: $role\n");
		 	}
		 	\cli\out("  to $output_path\n");
	 	} else {
		 	\cli\out("Creating file of $required_records user records\n");
		 	\cli\out("  with email addresses @ $domain_name\n");
		 	\cli\out("  to $output_path\n");
	 	}

	 	// generate the user records
	 	$name_count = 0;
	 	$user_records = array();

	 	while($name_count < $required_records) {

	 		// get the first name
	 		// alternate between male and female
	 		if($name_count % 2 == 0) {
		 		$first_name = $male_data[rand(0, $male_count)];
		 	} else {
			 	$first_name = $female_data[rand(0, $male_count)];
		 	}

		 	$surname = $surname_data[rand(0, $surname_count)];

		 	$user_name = strtolower($first_name . '.' . $surname);

		 	if(!array_key_exists($user_name, $user_records)) {

		 		$email_address = $user_name . '@' . $domain_name;

		 		if($course != false) {
		 			if($role !== false) {
				 		$user_records[$user_name] = array($user_name, $this->generate_password(), $first_name , $surname, $email_address, $course, $role);
				 	} else {
					 	$user_records[$user_name] = array($user_name, $this->generate_password(), $first_name , $surname, $email_address, $course);
				 	}
		 		} else {
			 		$user_records[$user_name] = array($user_name, $this->generate_password(), $first_name , $surname, $email_address);
		 		}

			 	$name_count++;
		 	}
	 	}

	 	// output the user records
	 	$output_handle = fopen($output_path, 'w');

	 	if($output_handle == false) {
		 	\cli\err("ERROR: unable to open the output file\n");
		 	die(-1);
	 	}

	 	// output the file header
	 	//username,password,firstname,lastname,email
	 	if($role !== false) {
		 	$result = fwrite($output_handle, "username,password,firstname,lastname,email,course1,role1\n");
	 	} else if($course != false) {
		 	$result = fwrite($output_handle, "username,password,firstname,lastname,email,course1\n");
	 	} else {
		 	$result = fwrite($output_handle, "username,password,firstname,lastname,email\n");
	 	}

	 	if($result == false) {
		 	\cli\err("ERROR: unable to write the output file\n");
		 	die(-1);
	 	}

	 	foreach($user_records as $data) {

	 		$result = fputcsv($output_handle, $data);

	 		if($result == false) {
		 		\cli\err("ERROR: unable to write the output file\n");
		 		die(-1);
	 		}

	 	}

	 	// play nice and tidy up
	 	fclose($output_handle);

	 	\cli\out("SUCCESS: File successfully created.\n");
	}

	// small private function to load the role definition file
	private function load_role_definitions() {

		$role_def = false;
		$roles = array();

		// start with the override file
		if(is_readable(__DIR__ . self::OVERRIDE_ROLES_FILE)) {
			$role_def = json_decode(file_get_contents(__DIR__ . self::OVERRIDE_ROLES_FILE), true);

			if($role_def == null) {
				\cli\err("ERROR: Unable to load the role override file:\n" . self::OVERRIDE_ROLES_FILE . "\n");
				die(-1);
			}
		}

		// try the default role file
		if($role_def == false) {
			// try the default file
			if(!is_readable(__DIR__ . self::DEFAULT_ROLES_FILE)) {
				\cli\err("ERROR: Unable to locate the default role file:\n" . self::DEFAULT_ROLES_FILE . "\n");
				die(-1);
			} else {
				$role_def = json_decode(file_get_contents(__DIR__ . self::DEFAULT_ROLES_FILE), true);
			}

			if($role_def == null) {
				\cli\err("ERROR: Unable to load the default role file:\n" . self::OVERRIDE_ROLES_FILE . "\n");
				die(-1);
			}
		}

		// ensure consistent format of the role_def
		foreach($role_def as $key => $value) {
    		if(!is_numeric($key)) {
        		\cli\err("ERROR: invalid key value found in role definition file");
        		die(-1);
    		}

    		$roles[$key] = strtolower($value);
		}

		return $roles;
	}

	// small private function to make reading in the data files easier
	private function get_data($path) {
		$data = file($path, FILE_IGNORE_NEW_LINES);

	 	$filter_data = function ($var) {
			return strncmp($var, '#', 1);
	 	};

	 	if($data != false) {
		 	return array_values(array_filter($data, $filter_data));
	 	} else {
		 	return false;
	 	}
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
			$sets[] = '!@#$%&*?';

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
	$app = new MdlUserListCreator();
	$app->create_file();
} else {
	// no
	die("This script can only be run on the cli\n");
}

?>