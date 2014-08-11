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
 * This is a PHP script which compares language strings in a Moodle
 * installtion.
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
use \Techxplorer\Utils\Files;
use \Techxplorer\Utils\System;

use \Techxplorer\Moodle\MdlLangInfo;
use \Techxplorer\Moodle\MdlLangComparator;

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
class MdlLangCompare
{

    /**
     * defines a name for the script
     */
    const SCRIPT_NAME = "Techxplorer's Moodle Lang Compare script";

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
     * defines the default occurances limit for diff stats output
     */
    const DEFAULT_OCCURANCES = 5;

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
            array('moodle', 'm'),
            array(
                'default' => '',
                'description' => 'The path to the Moodle lang file(s)'
            )
        );

        $arguments->addOption(
            array('custom', 'c'),
            array(
                'default' => '',
                'description' => 'The path to the custom lang file(s)'
            )
        );

        $arguments->addOption(
            array('occurrences', 'o'),
            array(
                'default' => self::DEFAULT_OCCURANCES,
                'description' =>
                'Limit occurrences to this and above for output in stats'
            )
        );

        $arguments->addFlag(
            array('diff', 'd'),
            'Calculate diffs between Moodle and Custom lang file(s)'
        );

        $arguments->addFlag(
            array('stats', 's'),
            'Calculate word removal / replacement stats'
        );

        $arguments->addFlag(
            array('help', 'h'),
            'Show this help screen'
        );

        // parse the arguments and show the help screen if required
        $arguments->parse();

        if ($arguments['help']) {
            \cli\out($arguments->getHelpScreen());
            \cli\out("\n");
            die(0);
        }

        if (!$arguments['moodle']) {
            \cli\err("%rERROR: %wMissing required argument --moodle\n");
            \cli\err($arguments->getHelpScreen());
            \cli\err("\n");
            die(1);
        } else {
            $moodle_path = realpath($arguments['moodle']);

            if ($moodle_path == false) {
                \cli\err("%rERROR: %wUnable to find path specified by --moodle\n");
                \cli\err("\n");
                die(1);
            }
        }

        if (!$arguments['custom']) {
            \cli\err("%rERROR: %wMissing required argument --custom\n");
            \cli\err($arguments->getHelpScreen());
            \cli\err("\n");
            die(1);
        } else {
            $custom_path = realpath($arguments['custom']);

            if ($custom_path == false) {
                \cli\err("%rERROR: %wUnable to find path specified by --custom\n");
                \cli\err("\n");
                die(1);
            }
        }

        // determine the occurance limit
        if (!$arguments['occurrences']) {
            $occurances = self::DEFAULT_OCCURANCES;
        } else {
            if (!is_numeric($arguments['occurrences'])) {
                \cli\err("%rERROR: %wThe occurrences limit must be a number\n");
                \cli\err("\n");
                die(1);
            }
            $occurances = (int) $arguments['occurrences'];
            if ($occurances < 0) {
                \cli\err("%rERROR: %wThe occurrences limit must be grater than 0\n");
                \cli\err("\n");
                die(1);
            }
        }

        // fake a moodle install
        define('MOODLE_INTERNAL', true);

        // keep track of skipped files
        $skipped_paths = array();
        $paths = array();

        $comparator = new MdlLangComparator($moodle_path);

        // process directories of files
        if (is_dir($moodle_path) && is_dir($custom_path)) {
            \cli\out(
                "%yWARNING: %wProcessing all lang files in the --custom directory\n"
            );

            // get all of the custom langauge files
            $custom_paths = Files::findFiles($custom_path, '.php');

            list($paths, $skipped_paths) = $comparator->matchPaths(
                $custom_paths
            );

            // load all of the paths
            $data = array();
            foreach ($paths as $path) {
                try {
                    $datum = $comparator->loadLangFile($path[0], $path[1]);
                } catch (\Exception $e) {
                    \cli\err("%rERROR: %wAn exception occurding during loading\n");
                    \cli\err($e->getMessage());
                    \cli\err("\n\n");
                    die(1);
                }

                $data[] = $datum;
            }
        } else if (is_file($moodle_path) && is_file($custom_path)) {
            \cli\out(
                "%yWARNING: %wProcessing a single lang file\n"
            );

            // load the file
            try {
                $datum = $comparator->loadLangFile($moodle_path, $custom_path);
            } catch(\Exception $e) {
                \cli\err("%rERROR: %wAn exception occured during processing\n");
                \cli\err($e->getMessage());
                \cli\err("\n\n");
                die(1);
            }

            $data = array($datum);
        } else if (is_dir($moodle_path) && is_file($custom_path)) {
            $custom_paths = array($custom_path);

            list($paths, $skipped_paths) = $comparator->matchPaths(
                $custom_paths
            );

            if (count($paths) > 0) {
                //load the file
                try {
                    $datum = $comparator->loadLangFile($paths[0][0], $paths[0][1]);
                } catch(\Exception $e) {
                    \cli\err("%rERROR: %wAn exception occured during processing\n");
                    \cli\err($e->getMessage());
                    \cli\err("\n\n");
                    die(1);
                }

                $data = array($datum);
            } else {
                $data = array();
            }
        } else {
            \cli\err(
                "%rERROR: %wIncorrect --moodle and --custom parameters detected\n"
            );
            die(1);
        }

        $global_stats = array();

        // process the files as required
        foreach ($data as $datum) {

            // find any unused customisations
            $datum->getUnusedKeys();

            // process the differences between moodle and the customisations
            if ($arguments['diff']) {
                if ($datum->getUnusedCount() < $datum->getCustomCount()) {
                    $comparator->calculateDiffs($datum);
                }
            }

            if ($arguments['stats']) {
                if ($datum->getUnusedCount() < $datum->getCustomCount()) {
                    $comparator->calculateStats($datum);
                }
            }

            // output some helpful information
            \cli\out("\nMoodle lang file: '{$datum->getMoodlePath()}'\n");
            \cli\out("Custom lang file: '{$datum->getCustomPath()}'\n");
            \cli\out("Moodle strings: {$datum->getMoodleCount()}\n");
            \cli\out("Custom strings: {$datum->getCustomCount()}\n");

            if ($datum->getUnusedCount() > 0) {
                \cli\out(
                    'Unused customisations found: ' .
                    $datum->getUnusedKeys(true) . "\n"
                );
            }

            if ($datum->getDiffCount() > 0) {
                \cli\out("\n------ Diffs ------\n");

                foreach ($datum->getDiffs() as $key => $diff) {
                    \cli\out("---- $key ----\n");
                    \cli\out($diff . "\n");
                }
            }

            if ($datum->getStatsCount() > 0) {
                // build the data for the report
                $stats = $datum->getStats();

                $rows = array();

                foreach ($stats as $key => $s) {
                    if ($s['delete'] >= $occurances || $s['insert'] >= $occurances) {
                        $rows[] = array($key, $s['delete'], $s['insert']);
                    }

                    // add the stats to the global list
                    if (isset($global_stats[$key])) {
                        $global_stats[$key]['delete'] += $s['delete'];
                        $global_stats[$key]['insert'] += $s['insert'];
                    } else {
                        $global_stats[$key] = $s;
                    }
                }

                // output the table
                if (count($rows) > 0) {
                    \cli\out("\n------ Stats ------\n");
                    $table = new \cli\Table();
                    $table->setHeaders(array('Word / Phrase', 'Deletes', 'Inserts'));
                    $table->setRows($rows);
                    $table->display();
                    \cli\out("\n");
                }
            }
        }

        // output global stats

        if (count($global_stats) > 0) {
            $rows = array();

            foreach ($global_stats as $key => $s) {
                if ($s['delete'] >= $occurances || $s['insert'] >= $occurances) {
                    $rows[] = array($key, $s['delete'], $s['insert']);
                }
            }

            // output the table
            \cli\out("\n------ Global Stats ------\n");
            $table = new \cli\Table();
            $table->setHeaders(array('Word / Phrase', 'Deletes', 'Inserts'));
            $table->setRows($rows);
            $table->display();
            \cli\out("\n");
        }

        // output any skipped paths
        if (count($skipped_paths) > 0) {
            \cli\out("\n------ Skipped Files ------\n");
            $tree = new \cli\Tree;
            $tree->setData($skipped_paths);
            $tree->setRenderer(new \cli\tree\Markdown(2));
            $tree->display();
        }

    }
}

// make sure script is only run on the cli
if (System::isOnCLI()) {
    // yes
    $app = new MdlLangCompare();
    $app->doTask();
} else {
    // no
    die("This script can only be run on the cli\n");
}
