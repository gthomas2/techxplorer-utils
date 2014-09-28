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
 * This is a PHP script which can be used to check if a set of PHP files
 * use deprecated Moodle functions
 *
 * PHP Version 5.5
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
use \Techxplorer\Utils\FunctionList as FunctionList;
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
class MdlDeprecatedCheck
{
    /**
     * defines a name for the script
     */
    const SCRIPT_NAME = "Techxplorer's Moodle Deprecated Function Check script";

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
            array('moodle', 'm'),
            array(
                'default' => '',
                'description' => 'The path to the Moodle directory'
            )
        );

        $arguments->addOption(
            array('input', 'i'),
            array(
                'default' => '',
                'description' => 'The path to the input file or directory'
            )
        );

        $arguments->addFlag(
            array('help', 'h'),
            'Show this help screen'
        );

        // parse the arguments
        $arguments->parse();

        if ($arguments['help']) {
            \cli\out($arguments->getHelpScreen());
            \cli\out("\n");
            die(0);
        }

        if (!$arguments['moodle']) {
            \cli\err("%rERROR: %wMissing required option --moodle\n");
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

            if (!is_dir($moodle_path)) {
                \cli\err(
                    "%rERROR: %wThe path specified by --moodle must be a directory\n"
                );
                \cli\err("\n");
                die(1);
            }

            $deprecated_path = $moodle_path . '/lib/deprecatedlib.php';

            if (!is_file($deprecated_path)) {
                \cli\err(
                    "%rERROR: %wUnable to locate deprecatedlib.php file\n"
                );
                \cli\err("\n");
                die(1);
            }
        }

        $input_path = "";
        $input_files = array();

        if (!$arguments['input']) {
            \cli\err("%rERROR: %wMissing required option --input\n");
            \cli\err($arguments->getHelpScreen());
            \cli\err("\n");
            die(1);
        } else {
            $input_path = realpath($arguments['input']);

            if ($input_path == false) {
                \cli\err("%rError: %wUnable to find path specified by --input\n");
                \cli\err("\n");
                die(1);
            }

            if (!is_dir($input_path)) {
                $input_files[] = $input_path;
            } else {
                $input_files = Files::findFiles($input_path, '.php');
            }
        }

        // output some help information
        \cli\out("Using list of deprecated functions from:\n$deprecated_path\n");
        \cli\out("Searching for use of deprecated fucntions in:\n$input_path\n");

        if (count($input_files) > 1) {
            \cli\out("There are " . count($input_files) . " files to process:\n");
        }

        // build the list of deprecated functions
        $functions = new FunctionList($deprecated_path);

        if (!$functions->buildList()) {
            \cli\out("%rERROR: %wNo functions found in:\n$deprecated_path\n");
            die(0);
        }

        $deprecated_functions = $functions->getList();
        $dep_func_keys = array();

        // build a list of deprecated function keys
        foreach ($deprecated_functions as $function) {
            $dep_func_keys[] = $function['name'];
        }

        // process each of the input source files
        foreach ($input_files as $input_file) {

            $processor = new FunctionList($input_file);

            $used_functions = $processor->getList(FunctionList::USED_FUNCTIONS);
            $for_display = array();

            foreach ($used_functions as $function) {
                if (in_array($function['name'], $dep_func_keys)) {
                    $for_display[] = $function;
                }
            }

            if (count($for_display) > 0) {
                \cli\out(
                    "\n%yWARNING: %wdeprecated functions found in:\n$input_file\n"
                );

                $rows = array();
                foreach ($for_display as $data) {
                    $row = array($data['name'], $data['line']);
                    $rows[] = $row;
                }

                $table = new \cli\Table();
                $table->setHeaders(array('Function', 'Line'));
                $table->setRows($rows);
                $table->display();
            }
        }
    }
}

// make sure script is only run on the cli
if (System::isOnCLI()) {
    // yes
    $app = new MdlDeprecatedCheck();
    $app->doTask();
} else {
    // no
    die("This script can only be run on the cli\n");
}
