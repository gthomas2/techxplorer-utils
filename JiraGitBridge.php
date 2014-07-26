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
 * This is a PHP script which can be used to find commits in a Git 
 * repository that are related to issues in JIRA for a specific project
 * and version
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
use \Techxplorer\Utils\Files as Files;
use \Techxplorer\Utils\System as System;
use \Techxplorer\Utils\Git as Git;

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
class JiraGitBridge
{
    /**
     * defines a name for the script
     */
    const SCRIPT_NAME = "Techxplorer's Jira Git Bridge script";

    /**
     * defines the version of the script
     */
    const SCRIPT_VERSION = 'v1.2.0';

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
     *
     */
    public function doTask()
    {
        // output some helper text
        \cli\out(self::SCRIPT_NAME . ' - ' . self::SCRIPT_VERSION . "\n");
        \cli\out('License: ' . self::LICENSE_URI . "\n\n");

        // define a list of valid actions
        $valid_actions = array(
            'find-commits' => 'List the commits in a JIRA project and version',
        );

        // improve handling of arguments
        $arguments = new \cli\Arguments($_SERVER['argv']);

        $arguments->addOption(
            array('action', 'a'),
            array(
                'default' => 'list-commits',
                'description' => 'The action to undertake'
            )
        );

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

        $arguments->addOption(
            array('repository', 'r'),
            array(
                'default' => '',
                'description' => 'The path to the git repository'
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

        // check the arguments
        $action = 'list-commits';

        if (!$arguments['action']) {
            \cli\out("%yWARNING: %wUsing default action 'list-commits'\n\n");
        } else {
            $action = trim($arguments['action']);

            if (!array_key_exists($action, $valid_actions)) {
                \cli\out(
                    "%rERROR: %wInvalid action detected.\n" . 
                    "Use the -h flag to see a list of valid actions.\n\n"
                );
                die(1);
            }
        }

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

        if (!$arguments['repository']) {

            // get the current directory if a path isn't provided
            $repo_path = realpath(getcwd());

            if ($repo_path === false) {
                \cli\err("%rERROR: %wMissing required argument --repository\n");
                \cli\err($arguments->getHelpScreen());
                \cli\err("\n");
                die(1);
            } else {
                \cli\out(
                    "%yWARNING: %wUsing current working directory\n" .
                    "{$repo_path}\n\n"
                );
            }
        } else {
            $repo_path = $arguments['repository'];
            $repo_path = realpath($repo_path);

            if (!is_dir($repo_path)) {
                \cli\err("%rERROR: %wUnable to locate Git repository\n");
                die(1);
            }
        }

        //determine the path to the helper applications
        try {
            $git_path = Files::findApp('git');
        } catch (FileNotFoundException $ex) {
            \cli\err("%rERROR: %wUnable to locate git executable\n");
            die(1);
        }

        try {
            $grep_path = Files::findApp('grep');
        } catch (FileNotFoundException $ex) {
            \cli\err("%rERROR: %wUnable to location grep executable\n");
            die(1);
        }

        $jira_project = trim($arguments['project']);
        $jira_version = trim($arguments['version']);

        // output some helper text
        \cli\out("Searching for project: {$jira_project}\n");
        \cli\out("Searching for version: {$jira_version}\n");
        \cli\out("Using this git: {$git_path}\n");
        \cli\out("Using this grep: {$grep_path}\n");

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

        // build a list of keys
        $keys = array();

        foreach ($issues as $issue) {
            $keys[] = $issue['key'];
        }

        // build the pattern
        $pattern = implode('\|', $keys);

        // change to the repo path
        if (!chdir($repo_path)) {
            \cli\err("%rERROR: %wUnable to change to repository directory\n");
            die(1);
        }

        $git = new Git($git_path);

        $data = $git->getCommitList($pattern, $grep_path);

        if ($data == false) {
            die(1);
        }

        // output a list of commits
        $table = new \cli\Table();
        $table->setHeaders(array('Hash', 'JIRA Code', 'Description'));
        $table->setRows($data[0]);
        $table->display();

        \cli\out("Commits found: " . count($data[0]) . "\n");
        \cli\out("Merge commits: {$data[1]}\n");
        \cli\out("Apply commits in reverse order (bottom to top)\n");
    }
}

// make sure script is only run on the cli
if (System::isOnCLI()) {
    // yes
    $app = new JiraGitBridge();
    $app->doTask();
} else {
    // no
    die("This script can only be run on the cli\n");
}

?>
