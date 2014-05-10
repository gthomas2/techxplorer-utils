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
 * This is a PHP script which can be used to list issues in JIRA
 * related to a specific project and version
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
use \Techxplorer\Utils\JiraClient as JiraClient;
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
class JiraListIssues
{
    /**
     * defines a name for the script
     */
    const SCRIPT_NAME = "Techxplorer's Jira List Issues script";

    /**
     * defines the version of the script
     */
    const SCRIPT_VERSION = 'v1.1.0';

    /**
     * defines the uri for more information
     */
    const MORE_INFO_URI = 'https://github.com/techxplorer/techxplorer-utils';

    /**
     * defines the license uri
     */
    const LICENSE_URI = 'http://www.gnu.org/copyleft/gpl.html';

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
            array('project', 'p'),
            array(
                'default' => '',
                'description' => 'The JIRA project of interest'
            )
        );

        $arguments->addOption(
            array('version', 'v'),
            array(
                'default' => '',
                'description' => 'The JIRA version of interest'
            )
        );

        $arguments->addFlag(
            array('help', 'h'), 
            'Show this help screen'
        );

        // parse the arguments
        $arguments->parse();

        // show the help screen if required
        if ($arguments['help']) {
            \cli\out($arguments->getHelpScreen());
            \cli\out("\n\n");
            die(0);
        }

        // check the arguments
        if (!$arguments['project']) {
            \cli\err("%rERROR: %wMissing required argument --project\n");
            \cli\err($arguments->getHelpScreen());
            \cli\err("\n");
            die(1);
        }

        if (!$arguments['version']) {
            \cli\err("%rERROR: %wMissing required argument --version\n");
            \cli\err($arguments->getHelpScreen());
            \cli\err("\n");
            die(1);
        }

        $jira_project = trim($arguments['project']);
        $jira_version = trim($arguments['version']);

        // output some helper text
        \cli\out("Searching for project: {$jira_project}\n");
        \cli\out("Searching for version: {$jira_version}\n");

        // get JIRA helper class
        $jira_client = new JiraClient();

        try {
            $jira_client->loadConfig(__DIR__ . '/data/');
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

        // get a list of issues
        $issues = $jira_client->getIssues(
            $arguments['project'],
            $arguments['version']
        );

        if ($issues === false || count($issues) == 0) {
            \cli\out("%yWARNING: %wNo issues found.\n");
            die(0);
        }

        // reindex the list of issues for display
        $rows = array();
        foreach ($issues as $issue) {
            $rows[] = array_values($issue);
        }

        // output a list of issues
        $table = new \cli\Table();
        $table->setHeaders(array('Key', 'Summary', 'Status'));
        $table->setRows($rows);
        $table->display();

        \cli\out("Issues found: " . count($rows) . "\n");
    }
}

// make sure script is only run on the cli
if (System::isOnCLI()) {
    // yes
    $app = new JiraListIssues();
    $app->doTask();
} else {
    // no
    die("This script can only be run on the cli\n");
}

?>
