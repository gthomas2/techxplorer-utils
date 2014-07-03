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
 * This is a PHP script which can be used to reset a git repository
 *
 * PHP Version 5.4 
 *
 * @category TechxplorerUtils
 * @package  TechxplorerUtils
 * @author   techxplorer <corey@techxplorer.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     https://github.com/techxplorer/techxplorer-utils
 */

namespace Techxplorer\Utils;

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
class GitResetRepo
{
    /** 
     * defines a name for the script
     */
    const SCRIPT_NAME = "Techxplorer's Git Reset Repo script";

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
            array('branch', 'b'),
            array(
                'default' => '',
                'description' => 'The default branch'
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

        if (!$arguments['branch']) {
            \cli\err("%rERROR: %wMissing required argument --branch\n");
            \cli\err($arguments->getHelpScreen());
            \cli\err("\n");
            die(1);
        } else {
            $default_branch = $arguments['branch'];
        }

        if (!$arguments['repository']) {
            \cli\err("%rERROR: %wMissing required argument --repository\n");
            \cli\err($arguments->getHelpScreen());
            \cli\err("\n");
            die(1);
        } else {
            $repo_path = $arguments['repository'];
            $repo_path = realpath($repo_path);

            if (!is_dir($repo_path)) {
                \cli\err("%rERROR: %wUnable to locate Git repository\n");
                die(1);
            }
        }

        // determine the path to git
        try {
            $git_path = Files::findApp('git');
        } catch (FileNotFoundException $ex) {
            \cli\err("%rERROR: %wUnable to locate git executable\n");
            die(1);
        }

        // output some helper text
        \cli\out("Resetting to branch: {$default_branch}\n");
        \cli\out("Using this git: {$git_path}\n");

        // change to the repo path
        if (!chdir($repo_path)) {
            \cli\err("%rERROR: %wUnable to change to repository directory\n");
            die(1);
        }

        // get a reference to the git library
        $git = new Git($git_path);

        // delete all the branches except the default one
        if (!$git->deleteBranches($default_branch)) {
            die(1);
        }

        // download the latest changes
        if (!$git->fetchChanges()) {
            die(1);
        }

        // reset the branch
        if (!$git->resetBranch()) {
            die(1);
        }

        // clean the branch
        if (!$git->cleanBranch()) {
            die(1);
        }

        // prune the reflog
        if (!$git->pruneRefLog()) {
            die(1);
        }

        // clean and optimise repository
        if (!$git->cleanRepo()) {
            die(1);
        }

        // repack repository objects
        if (!$git->repackRepo()) {
            die(1);
        }
    }
}

// make sure script is only run on the cli
if (System::isOnCLI()) {
    // yes
    $app = new GitResetRepo();
    $app->doTask();
} else {
    // no
    die("This script can only be run on the cli\n");
}