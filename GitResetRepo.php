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

        // delete all the branches except the default one
        if (!$this->deleteBranches($git_path, $default_branch)) {
            die(1);
        }

        // download the latest changes
        if (!$this->fetchChanges($git_path)) {
            die(1);
        }

        // reset the branch
        if (!$this->resetBranch($git_path)) {
            die(1);
        }

        // clean the branch
        if (!$this->cleanBranch($git_path)) {
            die(1);
        }

        // prune the reflog
        if (!$this->pruneReflog($git_path)) {
            die(1);
        }

        // clean and optimise repository
        if (!$this->cleanRepo($git_path)) {
            die(1);
        }

        // repack repository objects
        if (!$this->repackRepo($git_path)) {
            die(1);
        }
    }

    /**
     * Repack the repository
     *
     * @param string $git_path the path to the git binary
     *
     * @return boolean true on success, false on failure
     */
    public function repackRepo($git_path)
    {
        // keep the user informed
        \cli\out("INFO: Repacking the repository...\n");

        // clean the branch of untracked files
        $command = "{$git_path} repack -ad 2>&1";
        $output = '';
        $return_var = '';
        exec($command, $output, $return_var);

        // check to make sure the command executed successfully
        if ($return_var != 0) {
            \cli\err("%rERROR: %wUnable to repack the repository\n");
            return false;
        }

        \cli\out(
            "%gSUCCESS: %wRepository repacked\n"
        );

        return true;
    }

    /**
     * Clean and optimise the repository
     *
     * @param string $git_path the path to the git binary
     *
     * @return boolean true on success, false on failure
     */
    public function cleanRepo($git_path)
    {
        // keep the user informed
        \cli\out("INFO: Cleaning and optimising repository...\n");

        // clean the branch of untracked files
        $command = "{$git_path} gc --prune=now 2>&1";
        $output = '';
        $return_var = '';
        exec($command, $output, $return_var);

        // check to make sure the command executed successfully
        if ($return_var != 0) {
            \cli\err("%rERROR: %wUnable to clean and optimise the repository\n");
            return false;
        }

        \cli\out(
            "%gSUCCESS: %wRepository cleaned and optimised\n"
        );

        return true;
    }

    /**
     * Prune the reflog of expired entries
     *
     * @param string $git_path the path to the git binary
     *
     * @return boolean true on success, false on failure
     */
    public function pruneReflog($git_path)
    {
        // keep the user informed
        \cli\out("INFO: Pruning the reflog...\n");

        // clean the branch of untracked files
        $command = "{$git_path} reflog expire --all --expire=now 2>&1";
        $output = '';
        $return_var = '';
        exec($command, $output, $return_var);

        // check to make sure the command executed successfully
        if ($return_var != 0) {
            \cli\err("%rERROR: %wUnable to prune the reflog\n");
            return false;
        }

        \cli\out(
            "%gSUCCESS: %wReflog pruned\n"
        );

        return true;
    }

    /**
     * Clean the branch of untracked files
     *
     * @param string $git_path the path to the git binary
     *
     * @return boolean true on sucess, false on failure
     */
    public function cleanBranch($git_path)
    {
        // keep the user informed
        \cli\out("INFO: Cleaning branch of untracked files...\n");

        // clean the branch of untracked files
        $command = "{$git_path} clean -fd 2>&1";
        $output = '';
        $return_var = '';
        exec($command, $output, $return_var);

        // check to make sure the command executed successfully
        if ($return_var != 0) {
            \cli\err("%rERROR: %wUnable to clean branch\n");
            return false;
        }

        \cli\out(
            "%gSUCCESS: %wBranch cleaned\n"
        );

        return true;
    }

    /**
     * Reset the branch to the remote HEAD
     *
     * @param string  $git_path the path to the git binary
     * @param boolean $verbose  if true output extra information
     *
     * @return boolean true on success, false on failure
     */
    public function resetBranch($git_path, $verbose = false)
    {
        // keep the user informed
        \cli\out("INFO: Reseting branch to remote HEAD...\n");

        // work out the current branch
        $command = "{$git_path} rev-parse --abbrev-ref HEAD";
        $branch_name = trim(shell_exec($command));

        if ($branch_name == null || $branch_name == '') {
            \cli\err("%rERROR: %wUnable to execute git command:\n");
            \cli\err($command . "\n");
            return false;
        }

        // keep the user informed
        if ($verbose) {
            \cli\out("INFO: Branch name: $branch_name\n");
        }

        // reset the branch
        $command = "{$git_path} reset --hard origin/$branch_name 2>&1";
        $output = '';
        $return_var = '';
        exec($command, $output, $return_var);

        // check to make sure the command executed successfully
        if ($return_var != 0) {
            \cli\err("%rERROR: %wUnable to reset branch\n");
            return false;
        }

        // keep the user informed
        if ($verbose) {
            \cli\out(
                "%gSUCCESS: %wBranch reset: {$output[0]}\n"
            );
        } else {
            \cli\out(
                "%gSUCCESS: %wReset branch to latest HEAD\n"
            );
        }

        return true;
    }

    /**
     * Fetch the latest changes from the remote repository
     *
     * @param string $git_path path to the git binary
     *
     * @return boolean true on success, false on failure
     */
    public function fetchChanges($git_path)
    {
        // keep the user informed
        \cli\out("INFO: Fetching latest changes...\n");

        // fetch the latest changes
        $command = "{$git_path} fetch 2>&1";
        $output = '';
        $return_var = '';
        exec($command, $output, $return_var);

        // check to make sure the command executed successfully
        if ($return_var != 0) {
            \cli\err("%rERROR: %wUnable to fetch changes\n");
            return false;
        }

        \cli\out(
            "%gSUCCESS: %wFetched the latest changes\n"
        );

        return true;
    }

    /**
     * Delete all branches in a repository except the
     * one specified
     *
     * @param string $git_path       path to the git binary
     * @param string $default_branch the default branch
     *
     * @return boolean true on success, false on failure
     */
    public function deleteBranches($git_path, $default_branch)
    {
        // keep the user informed
        \cli\out("INFO: Deleting branches...\n");

        // get a list of branches
        $command = "{$git_path} branch";
        $result = trim(shell_exec($command));

        if ($result == null || $result == '') {
            \cli\err("%rERROR: %wUnable to execute git command:\n");
            \cli\err($command . "\n");
            return false;
        }

        // process the list of branches
        $results = explode("\n", $result);
        $branches = array();

        foreach ($results as $branch) {
            // skip empty lines
            if (!strlen($branch) > 0) {
                continue;
            };

            // trim the branch entry
            $branch = trim($branch, '* ');

            $branches[] = $branch;
        }

        // see if the default branch is in the list
        if (!in_array($default_branch, $branches)) {
            \cli\err("%rERROR: %wUnable to find the branch '$default_branch'\n");
            return false;
        }

        // ensure we're on the default branch
        $command = "{$git_path} checkout $default_branch 2>&1";
        $output = '';
        $return_var = '';
        exec($command, $output, $return_var);

        // check to make sure the command executed successfully
        if ($return_var != 0) {
            \cli\err("%rERROR: %wUnable to change branch '$default_branch'\n");
            return false;
        }

        // remove the default branch from the list of branches
        $branches = array_diff($branches, array($default_branch));

        // delete all of the other branches
        foreach ($branches as $branch) {

            $command = "{$git_path} branch -D $branch 2>&1";
            $output = '';
            $return_var = '';
            exec($command, $output, $return_var);

            if ($return_var != 0) {
                \cli\err("%rERROR: %wUnable to delete branch '{$branch}'\n");
                return false;
            }
        }

        \cli\out(
            "%gSUCCESS: %wDeleted " . count($branches)
            . " branches. Now on $default_branch\n"
        );

        return true;
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
