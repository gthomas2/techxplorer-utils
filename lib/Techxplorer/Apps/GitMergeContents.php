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

use \Techxplorer\Utils\Pasteboard;
use GitWrapper\GitWrapper;
use GitWrapper\GitBranches;

/**
 * A base class for the GitFetchReset app
 *
 * @package    Techxplorer
 * @subpackage Apps
 */
class GitMergeContents extends Application
{
    /** @var $application_name the name of the application */
    protected static $application_name = "Techxplorer's Git Merge Contents Script";

    /** @var $application_version the version of the application */
    protected static $application_version = "2.0.0";

    /** @var $pasteboard used to copy the list of commits to the pastboard */
    protected $pasteboard;

    /**
     * Construct a new GitFetchReset object
     */
    public function __construct()
    {
        $this->pasteboard = new Pasteboard();
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
        $this->validateOption('commit');

        $this->printInfo("Searching for commit: {$this->options['commit']}");

        // Get a GitWrapper object and associated with the repository path.
        $wrapper = new GitWrapper();
        $git = $wrapper->workingCopy($this->options['repository']);

        // Get the list of commits contained in the merge commit.
        try {
            $log = $wrapper->git("log --oneline {$this->options['commit']}^...{$this->options['commit']}");
        } catch (\GitWrapper\GitException $e) {
            $msg = trim($e->getMessage(), 'fatal: ');
            $this->printError("A fatal error occurred:\n  $msg");
            exit(1);
        }

        $log_entries = explode("\n", $log);
        $commits = array();

        // Build the list of commits.
        foreach ($log_entries as $entry) {
            $entry = trim($entry);
            if (empty($entry)) {
                continue;
            }

            $tmp = explode(' ', $entry);

            if ($tmp[0] == $this->options['commit']) {
                continue;
            }

            array_shift($tmp);

            if ($tmp[0] == 'Merge' || (!empty($tmp[1]) && $tmp[1] == 'Merge')) {
                continue;
            }

            $commits[] = implode(' ', $tmp);
        }

        if (count($commits) == 0) {
            $this->printWarning(
                "No commits found.\n" .
                "  Was {$this->options['commit']} a merge commit?"
            );
        }

        $commits = array_unique($commits);

        \cli\out("\nIncludes: \n");
        $tree = new \cli\Tree;
        $tree->setData($commits);
        $renderer = new \cli\tree\Markdown(2);
        $tree->setRenderer($renderer);
        $tree->display();

        $data = "Includes: \n" . $renderer->render($commits);
        if (!$this->pasteboard->copy($data)) {
            $this->printWarning('Unable to copy list of items to the pasteboard.');
        }
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

        $this->options->addOption(
            array('commit', 'c'),
            array(
                'default' => '',
                'description' => 'The hash of the merge commit of interestn'
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
