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
 * A base class for the GerritPush app
 *
 * @package    Techxplorer
 * @subpackage Apps
 */
class GitFetchReset extends Application
{
    /** @var $application_name the name of the application */
    protected static $application_name = "Techxplorer's Push to Gerrit Script";

    /** @var $application_version the version of the application */
    protected static $application_version = "2.0.0";

    /**
     * Construct a new GerritPush object
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
        $branches = new GitBranches($git);

        // Check he status of the repository
        try {
            if (!empty($git->status())) {
                $this->printError('The repository has untracked changes.');
                exit(1);
            }
        } catch (\GitWrapper\GitException $e) {
            $this->printError('Unable to determine the status of the branch.');
            exit(1);
        }

        // Push the changes to Gerrit
        try {
            $this->printInfo('Pushing the changes to Gerrit...');
            $output = $wrapper->git('push origin HEAD:refs/for/' . $branches->head());
            \cli\out($output . "\n");
        } catch (\GitWrapper\GitException $e) {
            $this->printError('Unable to push the changes.');
            exit(1);
        }

        $this->printSuccess('Most recent commit has been pushed to Gerrit');
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
