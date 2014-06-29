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
}
