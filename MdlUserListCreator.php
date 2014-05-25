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
 * This is a PHP script which can be used to create a
 * csv file containing user information for upload into a 
 * Moodle instance.
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
class MdlUserListCreator
{
    /**
     * defines a name for the script
     */
    const SCRIPT_NAME = "Techxplorer's Moodle User List Creator Script";

    /**
     * defines the version of the script
     */
    const SCRIPT_VERSION = 'v1.0.4';

    /**
     * defines the uri for more information
     */
    const MORE_INFO_URI = 'https://github.com/techxplorer/techxplorer-utils';

    /**
     * defines the license uri
     */
    const LICENSE_URI = 'http://www.gnu.org/copyleft/gpl.html';

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
     * define the override role definition file
     */
    const ROLES_FILE = 'mdl-roles.json';

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
     * @return void
     */
    public function doTask()
    {
        // output some helper text
        \cli\out(self::SCRIPT_NAME . ' - ' . self::SCRIPT_VERSION . "\n");
        \cli\out('License: ' . self::LICENSE_URI . "\n\n");

        // improve handling of arguments
        $arguments = new \cli\Arguments($_SERVER['argv']);

        $arguments->addOption(
            array('output', 'o'),
            array(
                'default' => '',
                'description' => 'Set the path to the output file'
            )
        );

        $arguments->addOption(
            array('number', 'n'),
            array(
                'default' => self::DEFAULT_RECORD_COUNT,
                'description' => 'Set the number of records to create'
            )
        );

        $arguments->addOption(
            array('domain', 'd'),
            array (
                'default' => self::DEFAULT_EMAIL_DOMAIN,
                'description' => 'The domain name for email addresses'
            )
        );

        $arguments->addOption(
            array('course', 'c'),
            array (
                'default' => '',
                'description' => 'The short code of a course to enrol the users in'
            )
        );

        $arguments->addOption(
            array('role', 'r'),
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
        if ($arguments['help']) {
            \cli\out($arguments->getHelpScreen());
            \cli\out("\n\n");
            die(0);
        }

        // load the role data
        try {
            $role_path = __DIR__ . '/data/' . self::ROLES_FILE;
            $role_defs = Files::loadConfig($role_path);

            $role_defs = array_map('strtolower', $role_defs);
        } catch (FileNotFoundException $ex) {
            \cli\err(
                "%rERROR: %wUnable to find configuration file:\n" .
                $role_path .
                "\n"
            );  
            die(1);
        } catch (ConfigParseException $ex) {
            \cli\err(
                "%rERROR: %wUnable to load configuration file:\n" .
                $role_path .
                "\n"
            );  
            die(1);
        }

        // output the list of avaialble roles
        if ($arguments['list-roles']) {

            $roles = array();

            // turn the associative array into an array for output
            foreach ($role_defs as $key => $value) {
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
        if (!$arguments['output']) {
            \cli\out("Error: Missing required argument --output\n");
            \cli\out($arguments->getHelpScreen());
            \cli\out("\n\n");
            die(1);
        } else {
            $output_path = $arguments['output'];
            $output_path = realpath(dirname($output_path)) .
                '/' . basename($output_path);
        }

        // check to make sure the file doesn't already exist
        if (file_exists($output_path) == true) {
            \cli\err("ERROR: The output file already exists\n");
            die(1);
        }

        if (!$arguments['number']) {
            $required_records = self::DEFAULT_RECORD_COUNT;
        } else {
            $required_records = $arguments['number'];

            if (!is_numeric($required_records)) {
                \cli\err("ERROR: The record number must be numeric\n");
                die(1);
            }

            if ((int) $required_records != $required_records) {
                \cli\err("ERROR: The record number must be a whole number\n");
                die(1);
            }

            $required_records = (int) $required_records;
        }

        if (!$arguments['domain']) {
            $domain_name = self::DEFAULT_EMAIL_DOMAIN;
        } else {
            $domain_name = $arguments['domain'];
        }

        if (!$arguments['course']) {
            $course = false;
        } else {
            $course = $arguments['course'];
        }

        if (!$arguments['role']) {
            $role = false;
        } else {

            // using role only makes sense if a course is specified
            if (!$course) {
                \cli\err("ERROR: you can only specify a role with a course\n");
                die(1);
            }

            // is this is a numeric or string role
            if (!is_numeric($arguments['role'])) {
                // string
                $tmp = strtolower(trim($arguments['role']));

                foreach ($role_defs as $key => $value) {
                    if ($value == $tmp) {
                        $role = $key;
                    }
                }

                if ($role === false) {
                    \cli\err("ERROR: Un-recognised role name '$role'\n");
                    die(1);
                }

            } else {
                // numeric
                if (!array_key_exists($arguments['role'], $role_defs)) {
                    \cli\err(
                        "ERROR: Un-recognised role id " . 
                        "'{$arguments['role']}'\n"
                    );
                    die(1);
                }

                $role = $arguments['role'];
            }
        }

        // check to make sure we can access the data files
        if (!is_readable(__DIR__ . self::MALE_DATA_PATH)) {
            \cli\err("ERROR: Unable to access the male name data file\n");
            die(1);
        }

        if (!is_readable(__DIR__ . self::FEMALE_DATA_PATH)) {
            \cli\err("ERROR: Unable to access the female name data file\n");
            die(1);
        }

        if (!is_readable(__DIR__ . self::LAST_NAME_DATA_PATH)) {
            \cli\err("ERROR: Unable to access the last name data file\n");
            die(1);
        }

        // read in the data files
        $male_data = $this->_getData(__DIR__ . self::MALE_DATA_PATH);

        if ($male_data == false) {
            \cli\err("ERROR: Unable to read the male name data file\n");
            die(1);
        }

        $female_data = $this->_getData(__DIR__ . self::FEMALE_DATA_PATH);

        if ($female_data == false) {
            \cli\err("ERROR: Unable to read the female name data file\n");
            die(1);
        }

        $surname_data = $this->_getData(__DIR__ . self::LAST_NAME_DATA_PATH);

        if ($surname_data == false) {
            \cli\err("ERROR: Unable to read the female name data file\n");
            die(1);
        }

        // get the size of the arrays for use later
        $male_count    = count($male_data) - 1;
        $female_count  = count($female_data) - 1;
        $surname_count = count($surname_data) - 1;

        // output some information
        if ($course != false) {
            \cli\out("Creating file of $required_records user records\n");
            \cli\out("  with email addresses @ $domain_name\n");
            \cli\out("  with course short code: $course\n");
            if ($role !== false) {
                \cli\out('  with role: ' . $role_defs[$role] . "\n");
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

        while ($name_count < $required_records) {

            // get the first name
            // alternate between male and female
            if ($name_count % 2 == 0) {
                $first_name = $male_data[rand(0, $male_count)];
            } else {
                $first_name = $female_data[rand(0, $male_count)];
            }

            $surname = $surname_data[rand(0, $surname_count)];

            $user_name = strtolower($first_name . '.' . $surname);

            if (!array_key_exists($user_name, $user_records)) {

                $email_address = $user_name . '@' . $domain_name;

                if ($course != false) {
                    if ($role !== false) {
                        $user_records[$user_name] = array(
                            $user_name,
                            Password::generate(),
                            $first_name,
                            $surname,
                            $email_address,
                            $course,
                            $role
                        );
                    } else {
                        $user_records[$user_name] = array(
                            $user_name,
                            Password::generate(),
                            $first_name,
                            $surname,
                            $email_address,
                            $course
                        );
                    }
                } else {
                    $user_records[$user_name] = array(
                        $user_name,
                        Password::generate(),
                        $first_name,
                        $surname,
                        $email_address
                    );
                }

                $name_count++;
            }
        }

        // output the user records
        $output_handle = fopen($output_path, 'w');

        if ($output_handle == false) {
            \cli\err("ERROR: unable to open the output file\n");
            die(1);
        }

        // output the file header
        //username,password,firstname,lastname,email
        if ($role !== false) {
            $result = fwrite(
                $output_handle, 
                "username,password,firstname,lastname,email,course1,role1\n"
            );
        } elseif ($course != false) {
            $result = fwrite(
                $output_handle,
                "username,password,firstname,lastname,email,course1\n"
            );
        } else {
            $result = fwrite(
                $output_handle,
                "username,password,firstname,lastname,email\n"
            );
        }

        if ($result == false) {
            \cli\err("ERROR: unable to write the output file\n");
            die(1);
        }

        foreach ($user_records as $data) {

            $result = fputcsv($output_handle, $data);

            if ($result == false) {
                \cli\err("ERROR: unable to write the output file\n");
                die(1);
            }
        }

        // play nice and tidy up
        fclose($output_handle);

        \cli\out("SUCCESS: File successfully created.\n");
    }

    /**
     * get the data from one of the name files
     *
     * @param string $path the path to the data file to read
     *
     * @return mixed array of data or false on failure
     */
    private function _getData($path)
    {
        $data = file($path, FILE_IGNORE_NEW_LINES);

        $filter_data = function ($var) {
            return strncmp($var, '#', 1);
        };

        if ($data != false) {
            return array_values(array_filter($data, $filter_data));
        } else {
            return false;
        }
    }
}

// make sure script is only run on the cli
if (!System::isOnCLI()) {
    die("This script can only be run on the CLI on Mac OS X");
} else {
    $app = new MdlUserListCreator();
    $app->doTask();
}
