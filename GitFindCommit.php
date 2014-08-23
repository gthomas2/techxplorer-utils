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
 * This is a PHP script which can be used to find commits with messages
 * matching a specified pattern
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
use \Techxplorer\Utils\Git as Git;
use \Techxplorer\Utils\Pasteboard as Pasteboard;

use \Techxplorer\Utils\FileNotFoundException;

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
class GitFindCommit
{
    /**
     * defines a name for the script
     */
    const SCRIPT_NAME = "Techxplorer's Git Find Commit script";

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

        // improve handling of arguments
        $arguments = new \cli\Arguments($_SERVER['argv']);

        $arguments->addOption(
            array('pattern', 'p'),
            array(
                'default' => '',
                'description' => 'The pattern used to match against commit messages'
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
            \cli\out("\n");
            die(0);
        }

        if (!$arguments['pattern']) {
            \cli\err("%rERROR: %wMissing required argument --pattern\n");
            \cli\err($arguments->getHelpScreen());
            \cli\err("\n");
            die(1);
        } else {
            $log_pattern = $arguments['pattern'];
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
            \cli\err("%rERROR: %wUnable to locate grep executable\n");
            die(1);
        }

        // initialise the pasteboard class
        try {
            $pasteboard = new PasteBoard();
        } catch (Exception $e) {
            $pasteboard = false;
            \cli\out("\nAutomatic copying to the pasteboard is disabled\n");
        }

        // output some helper text
        \cli\out("Searching log with pattern: {$log_pattern}\n");
        \cli\out("Using this git: {$git_path}\n");
        \cli\out("Using this grep: {$grep_path}\n");

        // change to the repo path
        if (!chdir($repo_path)) {
            \cli\err("%rERROR: %wUnable to change to repository directory\n");
            die(1);
        }

        $git = new Git($git_path);

        // get the list of commits
        list($commits, $merges) = $git->getCommitList($log_pattern, $grep_path);

        if (count($commits) == 0) {
            \cli\out("%yWARNING: %wno commits found.\n");
            \cli\out("\n");
            die(0);
        }

        if (count($commits) == 1 && $pasteboard != false) {
            $pasteboard->put($commits[0][0]);
        }

        // show a table listing the commits
        $table = new \cli\Table();
        $table->setHeaders(array('Hash', 'JIRA Code', 'Description'));
        $table->setRows($commits);
        $table->display();
        \cli\out(count($commits) . " commits found.\n");
    }
}

// make sure script is only run on the cli
if (System::isOnCLI()) {
    // yes
    $app = new GitFindCommit();
    $app->doTask();
} else {
    // no
    die("This script can only be run on the cli\n");
}
