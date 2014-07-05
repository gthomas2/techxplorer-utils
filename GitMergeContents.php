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
 * This is a PHP script which can be used to list the contents of a
 * merge commit in a format for easy copy and paste into other systems
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
class GitMergeContents
{
    /** 
     * defines a name for the script
     */
    const SCRIPT_NAME = "Techxplorer's Git Merge Contents script";

    /** 
     * defines the version of the script
     */
    const SCRIPT_VERSION = 'v1.0.1';

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
            array('commit', 'c'),
            array(
                'default' => '',
                'description' => 'The commit hash'
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
        }

        if (!$arguments['commit']) {
            \cli\err("%rERROR: %wMissing required argument --commit\n");
            \cli\err($arguments->getHelpScreen());
            \cli\err("\n");
            die(1);
        } else {
            $commit_hash = $arguments['commit'];
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

        // output some helper text
        \cli\out("Searching for commit: {$commit_hash}\n");
        \cli\out("Using this git: {$git_path}\n");

        // change to the repo path
        if (!chdir($repo_path)) {
            \cli\err("%rERROR: %wUnable to change to repository directory\n");
            die(1);
        }

        $git = new Git($git_path);

        $commits = $git->getMergeContents($commit_hash);

        // output the list of commits
        if (count($commits) == 0) {
            \cli\out(
                "%yWARNING: %wno commits found. " .
                "Was '{$commit_hash}' really a merge commit?\n"
            );
            die(0);
        }

        // don't output the same item description twice
        $commits = array_unique($commits);

        // output the information to the user
        \cli\out("\nImplements: \n");
        $tree = new \cli\Tree;
        $tree->setData($commits);
        $tree->setRenderer(new \cli\tree\Markdown(2));
        $tree->display();
        \cli\out("\n");
        \cli\out("Number of items: " . count($commits) . "\n");
    }
} 

// make sure script is only run on the cli
if (System::isOnCLI()) {
    // yes
    $app = new GitMergeContents();
    $app->doTask();
} else {
    // no
    die("This script can only be run on the cli\n");
}
