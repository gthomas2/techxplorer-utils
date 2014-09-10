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
 * This is a PHP script which can be used to automate using dos2unix on a
 * directory of files
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
class FixLineEndings
{
    /**
     * defines a name for the script
     */
    const SCRIPT_NAME = "Techxplorer's Fix Line Endings script";

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
     * defines the default list of extension to match
     */
    const DEFAULT_EXTENSIONS = 'php,css,js,txt,md';

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
            array('input', 'i'),
            array(
                'default' => '',
                'description' => 'The path to the input directory'
            )
        );

        $arguments->addOption(
            array('filter', 'f'),
            array(
                'default' => self::DEFAULT_EXTENSIONS,
                'description' => 'Comma separated list of file extensions to match'
            )
        );

        $arguments->addFlag(
            array('help', 'h'),
            'Show this help screen'
        );

        $arguments->addFlag(
            array('verbose', 'v'),
            'Show verbose output'
        );

        $arguments->addFlag(
            array('trim', 't'),
            'Trim trailing whitespace'
        );

        // parse the arguments
        $arguments->parse();

        if ($arguments['help']) {
            \cli\out($arguments->getHelpScreen());
            \cli\out("\n");
            die(0);
        }

        if (!$arguments['input']) {
            \cli\err("%rERROR: %wMissing required argument --input\n");
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
                \cli\err("%rERROR: %wThe --input path must be a directory\n");
                \cli\err("\n");
                die(1);
            }
        }

        $extensions = array();

        if (!$arguments['filter']) {
            $extensions = explode(',', self::DEFAULT_EXTENSIONS);
        } else {
            $extensions = explode(',', $arguments['filter']);

            // filter the list of extensions by:
            // triming, including '.'
            // removing empty strings
            $f = function ($value) {
                return trim($value, ' .');
            };

            $extensions = array_map($f, $extensions);
            $extensions = array_filter($extensions, 'strlen');

            if (count($extensions) == 0) {
                \cli\err("%rERROR: %wInvalid extension list detected\n");
                \cli\err("\n");
                die(1);
            }
        }

        $verbose = false;

        if ($arguments['verbose']) {
            $verbose = true;
        }

        $trim = false;

        if ($arguments['trim']) {
            $trim = true;
        }

        // determine the path to the dos2unix binary
        try {
            $dos2unix_path = Files::findApp('dos2unix');
        } catch (FileNotFoundException $ex) {
            \cli\err("%rERROR: %wUnable to locate dos2unix executable\n");
            die(1);
        }

        // output some help text
        \cli\out("Looking for files in:\n$input_path\n");
        \cli\out("Using this dos2unix:\n$dos2unix_path\n");
        \cli\out(
            "Filtering for files with extension:\n    " .
            implode(',', $extensions) .
            "\n"
        );

        if ($trim) {
            \cli\out("Trimming trailing whitespace\n");
        }

        \cli\out("\n");

        // get a list of files to operate on
        $file_list = Files::findFiles($input_path);

        // filter the list of files
        $file_list = Files::filterPathsByExt($file_list, $extensions);
        $fixed_list = array();

        // process the list of files
        foreach ($file_list as $file) {
            $command = "$dos2unix_path $file 2>&1";
            $output = array();
            $return_var = '';

            exec($command, $output, $return_var);

            if ($return_var != 0) {
                \cli\out("%yWARNING: %wUnable to process:\n$file\n");
                continue;
            }

            if ($trim) {
                // trim trailing whitespace
                $contents = file($file);
                $trimmed = array();

                foreach ($contents as $line) {
                    $line = preg_replace('# +$|\t+$#', '', $line);
                    $trimmed[] = $line;
                }

                if (file_put_contents($file, $trimmed) === false) {
                    \cli\out("%yWARNING: %wUnable to trim whitspace from:\n$file\n");
                    continue;
                }
            }

            $fixed_list[] = $file;

            if ($verbose) {
                \cli\out("Processed file:\n$file\n");
            }
        }

        // output some more information
        \cli\out(
            "%gSUCCESS: %w" .
            count($fixed_list) . ' of ' .
            count($file_list) . " files successfully fixed.\n\n"
        );
    }
}

// make sure script is only run on the cli
if (System::isOnCLI()) {
    // yes
    $app = new FixLineEndings();
    $app->doTask();
} else {
    // no
    die("This script can only be run on the cli\n");
}
