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
 * This is a PHP script which can be used to automate the starting and 
 * stopping of launchd services on Mac OS X
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
require_once __DIR__ . '/vendor/autoload.php';

// shorten namespaces
use \Techxplorer\Utils\Files as Files;
use \Techxplorer\Utils\System as System;

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
class ServiceMgmt
{

    /**
     * defines a name for the script
     */
    const SCRIPT_NAME = "Techxplorer's Service Management script";

    /**
     * defines the version of the script
     */
    const SCRIPT_VERSION = 'v1.0.0';

    /**
     * defines the uri for more information
     */
    const MORE_INFO_URI = 'https://github.com/techxplorer/techxplorer-utils';

    /**
     * defines the license uri
     */
    const LICENSE_URI = 'http://www.gnu.org/copyleft/gpl.html';

    /**
     * defines the default configuration file name
     */
    const DEFAULT_CONFIG_FILE = 'service-mgmt.json';

    /**
     * main driving function
     *
     * @return void
     */
    public function doTask() 
    {
        // output some helpful text
        \cli\out(self::SCRIPT_NAME . ' - ' . self::SCRIPT_VERSION . "\n");
        \cli\out('License: ' . self::LICENSE_URI . "\n\n");

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

        // build a list of valid actions
        $valid_actions = array(
            'start'   => 'Start services',
            'stop'    => 'Stop services',
            'restart' => 'Restart services'
        );

        // prepare arguments
        $arguments = new \cli\Arguments($_SERVER['argv']);

        $arguments->addOption(
            array('action', 'a'),
            array(
                'default' => '',
                'description' => 'The action to undertake'
            )
        );

        $arguments->addFlag(array('help', 'h'), 'Show this help screen');

        // parse arguments
        $arguments->parse();

        if ($arguments['help']) {
            \cli\out($arguments->getHelpScreen() . "\n\n");

            $actions = array();
            foreach ($valid_actions as $key => $value) {
                $actions[] = array($key, $value);
            }

            // output the table of actions
            \cli\out("List of available actions:\n");
            $table = new \cli\Table();
            $table->setHeaders(array('Action', 'Description'));
            $table->setRows($actions);
            $table->display();
            die(0);
        }

        if (!$arguments['action']) {
            \cli\err("%rERROR: %wMissing 'action' command line option\n");
            die(1);
        } else {
            $actions = array_keys($valid_actions);

            if (!in_array($arguments['action'], $actions)) {
                \cli\err("%rERROR: %wUnkown action '{$arguments['action']}'\n");
                die(1);
            }
        }

        $action = $arguments['action'];

        // find the launchctl app
        try {
            $launchctl_path = Files::findApp('launchctl');
        } catch (FileNotFoundException $ex) {
            \cli\err("%rERROR: %w" . $ex->getMessage() . "\n");
            die(1);
        }

        // find the whoami app
        try {
            $whoami_path = Files::findApp('whoami');
        } catch (FileNotFoundException $ex) {
            \cli\err("%rERROR: %w" . $ex->getMessage() . "\n");
            die(1);
        }

        // store the output of running commands
        $output;
        $return_var;

        // get the name of user executing the script
        exec(
            escapeshellcmd($whoami_path),
            $output,
            $return_var
        ); 

        if ($return_var != 0) {
            \cli\err("%rERROR: %w unable to determine username\n");
            die(1);
        }

        // resolve the LaunchAgents path
        $agents_path = realpath("/Users/{$output[0]}/Library/LaunchAgents");

        // determine the action to undertake
        switch($action) {
        case 'start':
            // start services
            $this->_startServices(
                $launchctl_path,
                $agents_path,
                $config
            );
            break;
        case 'stop':
            // stop services
            $this->_stopServices(
                $launchctl_path,
                $agents_path,
                $config
            );
            break;
        case 'restart':
            // stop and then start services
            $status = $this->_stopServices(
                $launchctl_path,
                $agents_path,
                $config
            );

            if ($status) {
                $this->_startServices(
                    $launchctl_path,
                    $agents_path,
                    $config
                );
            } else {
                \cli\err(
                    "%rERROR: %w" . 
                    "Not all services stopped. Cannot continue.\n"
                );
            }
            break;
        default:
            // we should not get here
            throw new RuntimeException('Unknown action slipped through validation');
        }
    }

    /**
     * Attempt to stop the listed services
     *
     * @param string $launchctl    the path to the launchctl app
     * @param string $launchagents the path to the launchagents directory
     * @param array  $services     the list of services to stop
     *
     * @return bool true on alll services stopped, false if not
     *
     * @return void
     */
    private function _stopServices($launchctl, $launchagents, $services)
    {
        \cli\out("Stopping services...\n");

        // services should be stopped in the reverse order listed
        $services = array_reverse($services, true);

        $warnings = false;

        // Loop through the services and stop them
        foreach ($services as $name => $plist) {

            $output = null;
            $return_var = null;

            // build the command to exec
            $command = "$launchctl unload $launchagents/$plist";
            $command = escapeshellcmd($command) . " 2>&1";

            // exec command
            exec(
                $command,
                $output,
                $return_var
            );

            // check for return value
            if (count($output) != 0) {
                \cli\err("%yWARNING: %wUnable to stop $name");
                $warnings = true;
            } else {
                \cli\out("%gSUCCESS: %w$name stopped\n");
            }
        }

        if ($warnings) {
            \cli\err("%rERROR: %wUnable to stop all services");
        } else {
            \cli\out("%gSUCCESS: %wAll services stopped\n");
        }

        return !$warnings;
    }

    /**
     * Attempt to start the listed services
     *
     * @param string $launchctl    the path to the launchctl app
     * @param string $launchagents the path to the launchagents directory
     * @param array  $services     the list of services to stop
     *
     * @return bool true on alll services started, false if not
     */
    private function _startServices($launchctl, $launchagents, $services)
    {
        \cli\out("Starting services...\n");

        $warnings = false;

        // Loop through the services and stop them
        foreach ($services as $name => $plist) {

            $output = null;
            $return_var = null;

            // build the command to exec
            $command = "$launchctl load $launchagents/$plist";
            $command = escapeshellcmd($command) . " 2>&1";

            // exec command
            exec(
                $command,
                $output,
                $return_var
            );

            // check for return value
            if (count($output) != 0) {
                \cli\err("%yWARNING: %wUnable to start $name");
                $warnings = true;
            } else {
                \cli\out("%gSUCCESS: %w$name started\n");
            }
        }

        if ($warnings) {
            \cli\err("%rERROR: %wUnable to start all services");
        } else {
            \cli\out("%gSUCCESS: %wAll services started\n");
        }

        return !$warnings;
    }

}

// Make sure the script is run only
// on Mac OS X and the CLI
if (!System::isMacOSX() || !System::isOnCLI()) {
    die("This script can only be run on the CLI on Mac OS X");
} else {
    $app = new ServiceMgmt();
    $app->doTask();
}
