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
 * This is a PHP script which can be used to create identicon images
 * for users using the output of the MdlUserListCreator script as input
 *
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
use \Techxplorer\Utils\System as System;

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
class MdlUserPixCreator
{
    /**
     * defines a name for the script
     */
    const SCRIPT_NAME = "Techxplorer's Moodle User Pix Creator Script";

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
     * defines the default size of the user pix
     */
    const DEFAULT_SIZE = 100;

    /**
     * main driving function
     *
     * @return void
     */
    public function doTask()
    {
        // output some helper text
        \cli\out(self::SCRIPT_NAME . ' - ' . self::SCRIPT_VERSION . "\n");
        \cli\out('License: ' . self::LICENSE_URI . "\n\n");

        // improve handling of arguments
        $arguments = new \cli\Arguments($_SERVER['argv']);

        $arguments->addOption(
            array('output', 'o'),
            array(
                'default' => '',
                'description' => 'Set the path to the output directory'
            )
        );

        $arguments->addOption(
            array('input', 'i'),
            array(
                'default' => '',
                'description' => 'Set the path to the input file'
            )
        );

        $arguments->addFlag(array('help', 'h'), 'Show this help screen');

        // parse the arguments
        $arguments->parse();

        // show the help screen if required
        if ($arguments['help']) {
            \cli\out($arguments->getHelpScreen());
            \cli\out("\n\n");
            die(0);
        }

        // check the arguments
        if (!$arguments['output']) {
            \cli\err("Error: Missing required argument --output\n");
            \cli\err($arguments->getHelpScreen());
            \cli\err("\n");
            die(1);
        } else {
            $output_path = $arguments['output'];
            $output_path = realpath($output_path);

            if (!is_dir($output_path)) {
                \cli\err("ERROR: Unable to locate output directory\n");
                \cli\err("\n");
                die(1);
            }
        }

        if (!$arguments['input']) {
            \cli\err("Error: Missing required argument --input\n");
            \cli\err($arguments->getHelpScreen());
            \cli\err("\n");
            die(1);
        } else {
            $input_path = $arguments['input'];
            $input_path = realpath($input_path);

            if (!file_exists($input_path)) {
                \cli\err("ERROR: Unable to locate input file\n");
                \cli\err("\n");
                die(1);
            }
        }

        // check to see if the output directory is empty
        if (count(glob($output_path . '/*')) !== 0 ) {
            \cli\err("ERROR: The output directory is not empty\n");
            \cli\err("\n");
            die(1);
        }

        // open the input file
        $fh = fopen($input_path, 'r');

        if ($fh == false) {
            \cli\err("ERROR: Unable to open the input file\n");
            \cli\err("\n");
            die(1);
        }

        // get the first line
        $line = fgetcsv($fh, 1024, ",");

        // find the index of the user column
        $user_index = array_search('username', $line);

        if ($user_index === false) {
            \cli\err("ERROR: Unable to find the 'username' column\n");
            \cli\err("\n");
            die(1);
        }

        // get class for identicon generation
        $generator = new \Identicon\Identicon();
        $pix_files = array();

        // create the user pix
        while (($line = fgetcsv($fh, 1024, ",")) !== false) {

            $user_name = $line[$user_index];

            $image_data = $generator->getImageData(
                implode('', $line),
                self::DEFAULT_SIZE
            );

            $pix_file = $output_path . "/$user_name.png";

            $ph = fopen($pix_file, 'wb');

            if ($ph == false) {
                \cli\err("ERROR: Unable to open the output file\n");
                \cli\err($pix_file . "\n");
                die(1);
            }

            fwrite($ph, $image_data);

            $pix_files[] = $pix_file;

            fclose($ph);
        }

        // play nice and tidy up
        fclose($fh);

        // create the zip file
        $zip_file = new ZipArchive();
        $zip_path = $output_path . '/_user-pix.zip';

        if ($zip_file->open($zip_path, ZIPARCHIVE::CREATE) !== true) {
            \cli\err("ERROR: Unable to create the zip file\n");
            \cli\err($zip_path . "\n");
            die(1);
        }

        foreach ($pix_files as $pix_file) {
            if (!$zip_file->addFile($pix_file)) {
                \cli\err("WARNING: Unable to add file to zip archive\n");
                \cli\err($pix_file . "\n");
            }
        }

        $zip_file->close();

        \cli\out("SUCCESS: The user pictures were successfully created\n");
    }
}

// Make sure the script is run only
// on Mac OS X and the CLI
if (!System::isOnCLI()) {
    die("This script can only be run on the CLI on Mac OS X");
} else {
    $app = new MdlUserPixCreator();
    $app->doTask();
}
