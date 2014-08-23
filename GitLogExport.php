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
 * This is a PHP script which can be used to export matching Git log entries
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
class GitLogExport
{
    /**
     * defines a name for the script
     */
    const SCRIPT_NAME = "Techxplorer's Git Log Export script";

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
                'description' => 'The pattern used to match log entires'
            )
        );

        $arguments->addOption(
            array('output', 'o'),
            array(
                'default' => '',
                'description' => 'The path to the output file'
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

        $arguments->addFlag(
            array('filter', 'f'),
            'Filter the list of commits'
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

        if (!$arguments['output']) {
            \cli\err("%rERROR: %wMissing required argument --output\n");
            \cli\err($arguments->getHelpScreen());
            \cli\err("\n");
            die(1);
        } else {
            $output_path = $arguments['output'];
            $output_path = realpath(dirname($output_path)) .
                '/' . basename($output_path);

            if (file_exists($output_path) == true) {
                \cli\err("%rERROR: %wThe specified output path already exists\n");
                \cli\err("\n");
                die(1);
            }
        }

        $filtered_list = false;

        if ($arguments['filter']) {
            $filtered_list = true;
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

        // filter out the merge commits
        $filter_function = function ($value) {
            if (strpos($value[1], '(merge)') === false) {
                return true;
            } else {
                return false;
            }
        };

        $commits = array_filter($commits, $filter_function);

        // filter the list of commits
        if ($filtered_list) {
            $tmp_list = array();

            // reverse the array
            // so oldest commit is first, not last
            $commits = array_reverse($commits);

            foreach ($commits as $commit) {
                array_shift($commit);

                if (!isset($tmp_list[$commit[0]])) {
                    $tmp_list[$commit[0]] = $commit;
                }
            }

            // sort the list of commits by key
            ksort($tmp_list, SORT_NATURAL);

            $commits = $tmp_list;
        }

        // write the output file
        $fh = fopen($output_path, 'w');

        if ($fh == false) {
            \cli\err("%rERROR: %wUnable to open output file\n");
            \cli\err("\n");
            die(1);
        }

        // output the data
        if (fputcsv($fh, array('JIRA Code', 'Description')) == false) {
            \cli\err("%rERROR: %wUnable to write to output file\n");
            \cli\err("\n");
            die(1);
        }

        foreach ($commits as $key => $commit) {
            if (!$filtered_list) {
                array_shift($commit); // drop commit hash
            }
            if (fputcsv($fh, $commit) == false) {
                \cli\err("%rERROR: %wUnable to write to output file\n");
                \cli\err("\n");
                die(1);
            }
        }

        fclose($fh);
        \cli\out(
            "%gSUCCESS: %wOutput file successfully created with " .
            count($commits) .
            " entries\n"
        );
        \cli\out("\n");
    }
}

// make sure script is only run on the cli
if (System::isOnCLI()) {
    // yes
    $app = new GitLogExport();
    $app->doTask();
} else {
    // no
    die("This script can only be run on the cli\n");
}
