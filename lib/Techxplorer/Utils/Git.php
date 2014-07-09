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
 * PHP Version 5.4
 *
 * @category TechxplorerUtils
 * @package  TechxplorerUtils
 * @author   techxplorer <corey@techxplorer.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     https://github.com/techxplorer/techxplorer-utils
 */

namespace Techxplorer\Utils;
use InvalidArgumentException;
use \Techxplorer\Utils\Files as Files;
use \Techxplorer\Utils\FileNotFoundException as FileNotFoundException;

/**
 * A class of Git related utility methods
 *
 * @category TechxplorerUtils
 * @package  TechxplorerUtils
 * @author   techxplorer <corey@techxplorer.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     https://github.com/techxplorer/techxplorer-utils
 */
class Git
{
    // store the path to the gitexecutable
    private $_git_path = null;

    /**
     * Class constructor
     *
     * @param string $git_path the path to the git executable
     *
     * @throws InvalidArgumentException if the parameter is invalid
     * @throws FileNotFoundException if the git executable cannot be found
     */
    public function __construct($git_path)
    {

        // check the parameter
        if (trim($git_path) == '' || $git_path == null) {
            throw new InvalidArgumentException('The git_path parameter is required');
        }

        if (!is_readable($git_path) || !is_executable($git_path)) {
            throw new FileNotFoundException(
                "The specified path '$git_path' cannot be accessed"
            );
        }

        $this->_git_path = $git_path;
    }

    /** 
     * Fetch the latest changes from the remote repository
     *
     * @return boolean true on success, false on failure
     */
    public function fetchChanges()
    {   
        // keep the user informed
        \cli\out("INFO: Fetching latest changes...\n");

        // fetch the latest changes
        $command = "{$this->_git_path} fetch 2>&1";
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
     * Reset the branch to the remote HEAD
     *
     * @param boolean $verbose if true output extra information
     *
     * @return boolean true on success, false on failure
     */
    public function resetBranch($verbose = false)
    {
        // keep the user informed
        \cli\out("INFO: Reseting branch to remote HEAD...\n");

        // work out the current branch
        $command = "{$this->_git_path} rev-parse --abbrev-ref HEAD";
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
        $command = "{$this->_git_path} reset --hard origin/$branch_name 2>&1";
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
     * Delete all branches in a repository except the
     * one specified
     *
     * @param string $default_branch the default branch
     *
     * @return boolean true on success, false on failure
     */
    public function deleteBranches($default_branch)
    {   
        // keep the user informed
        \cli\out("INFO: Deleting branches...\n");

        // get a list of branches
        $command = "{$this->_git_path} branch";
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
        $command = "{$this->_git_path} checkout $default_branch 2>&1";
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

            $command = "{$this->_git_path} branch -D $branch 2>&1";
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

    /**
     * Clean the branch of untracked files
     *
     * @return boolean true on sucess, false on failure
     */
    public function cleanBranch()
    {
        // keep the user informed
        \cli\out("INFO: Cleaning branch of untracked files...\n");

        // clean the branch of untracked files
        $command = "{$this->_git_path} clean -fd 2>&1";
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
     * Prune the reflog of expired entries
     *
     * @return boolean true on success, false on failure
     */
    public function pruneRefLog()
    {
        // keep the user informed
        \cli\out("INFO: Pruning the reflog...\n");

        // clean the branch of untracked files
        $command = "{$this->_git_path} reflog expire --all --expire=now 2>&1";
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
     * Repack the repository
     *
     * @return boolean true on success, false on failure
     */
    public function repackRepo()
    {
        // keep the user informed
        \cli\out("INFO: Repacking the repository...\n");

        // clean the branch of untracked files
        $command = "{$this->_git_path} repack -ad 2>&1";
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
     * @return boolean true on success, false on failure
     */
    public function cleanRepo()
    {
        // keep the user informed
        \cli\out("INFO: Cleaning and optimising repository...\n");

        // clean the branch of untracked files
        $command = "{$this->_git_path} gc --prune=now 2>&1";
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
     * Get the list of commits contained in a merge commit
     *
     * @param string  $hash           the commit hash id
     * @param boolean $include_hashes if true, include hashes in list of commits
     * @param boolean $skip_merges    if true, skip any merge commits
     *
     * @return mixed array() on success or false on failure
     */
    public function getMergeContents($hash,
        $include_hashes = false,
        $skip_merges = true
    ) {
        //git log --oneline e9117f6^...e9117f6
        $command = "{$this->_git_path} log --oneline {$hash}^...{$hash}";
        $result = trim(shell_exec($command));

        if ($result == null || $result == '') {
            \cli\err("%rERROR: %wUnable to execute git command:\n");
            \cli\err($command . "\n");
            die(1);
        }

        // process the results
        $results = explode("\n", $result);
        $commits = array();

        foreach ($results as $commit) {

            // skip empty lines
            $commit = trim($commit);
            if (!strlen($commit) > 0) {
                continue;
            };

            $tmp = explode(' ', $commit);

            // skip references to the merge commit itself
            if ($tmp[0] == $hash) {
                continue;
            }

            // return the commit hash?
            if (!$include_hashes) {
                array_shift($tmp);
            }

            // return merge commits?
            if ($tmp[0] == 'Merge' && $skip_merges && !$include_hashes) {
                continue;
            } else if ($tmp[1] == 'Merge' && $skip_merges && $include_hashes) {
                continue;
            }

            // now we have something to return
            $commits[] = implode(' ', $tmp);
        }

        return $commits;
    }

    /**
     * Check of a clean repository, in that git status returns no entries
     *
     * @return boolean true if clean, false if not
     */
    public function isClean()
    {
        // run git status and check for output
        $command = "{$this->_git_path} status --porcelain";
        $output = array();
        $return_var = '';
        exec($command, $output, $return_var);

        // check to make sure the command executed successfully
        if ($return_var != 0) {
            \cli\err("%rERROR: %wUnable to check if the repository is clean\n");
            return false;
        }

        if (count($output) > 0) {
            return false;
        } else {
            return true;
        }
    }
}
