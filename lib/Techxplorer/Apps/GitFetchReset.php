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
 * @author techxplorer <corey@techxplorer.com>
 * @copyright techxplorer 2015
 * @license GPL-3.0+
 * @see https://github.com/techxplorer/techxplorer-utils
 * @version 2.0
 */

namespace Techxplorer\Apps;

use GitWrapper\GitWrapper;
use GitWrapper\GitBranches;

/**
 * A base class for the GitFetchReset app
 *
 * @package    Techxplorer
 * @subpackage Apps
 */
class GitFetchReset extends Application
{
    /** @var $application_name the name of the application */
    protected static $application_name = "Techxplorer's Git Fetch and Reset Script";

    /** @var $application_version the version of the application */
    protected static $application_version = "2.0.0";

    /**
     * Construct a new GitFetchReset object
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Many entry point for the application
     *
     * @return void
     */
    public function doTask()
    {
        // Output the header information.
        $this->printHeader();

        // Parse and validate the command line options.
        $this->parseOptions();
        $this->printHelpScreen();
        $this->validateOption('repository', getcwd());

        // Get a GitWrapper object and associated with the repository path.
        $wrapper = new GitWrapper();
        $git = $wrapper->workingCopy($this->options['repository']);

        // Fetch the latest changes.
        try {
            $this->printInfo('Fetching latest changes...');
            $git->fetch('origin');
        } catch (\GitWrapper\GitException $e) {
            $msg = trim($e->getMessage(), 'fatal: ');
            $this->printError("A fatal error occurred:\n  $msg");
            exit(1);
        }

        $branches = new GitBranches($git);
        $this->printInfo("Reseting the current branch: {$branches->head()}");

        // Reset the branch.
        try {
            $this->printInfo('Reseting the branch...');
            $wrapper->git('reset --hard origin/' . $branches->head());
        } catch (\GitWrapper\GitException $e) {
            $this->printError('Unable to reset branch changes.');
            exit(1);
        }

        // Cleaning the branch.
        try {
            $this->printInfo('Cleaning the branch...');
            $git->clean('-d', '-f');
        } catch (\GitWrapper\GitException $e) {
            $this->printError('Unable to clean the branch.');
            exit(1);
        }

        $this->printSuccess('Latest changes fetched, and branch reset.');
    }

    /**
     * Parse the list of command line options
     *
     * @return void
     */
    protected function parseOptions()
    {
        $this->options = new \cli\Arguments($_SERVER['argv']);


        $this->options->addOption(
            array('repository', 'r'),
            array(
                'default' => 'The current working directory',
                'description' => 'The path to the git repository'
            )
        );

        $this->options->addFlag(
            array('help', 'h'),
            'Show this help screen'
        );

        // Parse the Options.
        $this->options->parse();
    }
}
