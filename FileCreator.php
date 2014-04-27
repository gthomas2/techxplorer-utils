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
 * This is a PHP script which creates a file of arbitrary size
 * containing random binary data
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
require __DIR__ . '/vendor/autoload.php';

// shorten namespaces
use \Techxplorer\Utils\Files as Files;
use \Techxplorer\Utils\System as System;

/**
 * main driving class of the script
 *
 * @category TechxplorerUtils
 * @package  TechxplorerUtils
 * @author   techxplorer <corey@techxplorer.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     https://github.com/techxplorer/techxplorer-utils
 *
 */
class FileCreator
{
    /**
     * defines a name for the script
     */
    const SCRIPT_NAME = "Techxplorer's File Creator Script";

    /**
     * defines the version of the script
     */
    const SCRIPT_VERSION = 'v1.0.2';

    /**
     * deines the uri for more information
     */
    const MORE_INFO_URI = 'https://github.com/techxplorer/techxplorer-utils';

    /**
     * defines the license uri
     */
    const LICENSE_URI = 'http://opensource.org/licenses/GPL-3.0';

    /**
     * defines the source of data for the newly created file
     */
    const SOURCE_DATA_PATH = '/dev/urandom';

    /**
     * defines the number of bytes copied at a time
     */
    const CHUNK_SIZE = '1024';

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
                'description' => 'Set the path to the output file'
            )
        );

        $arguments->addOption(
            array('size', 's'),
            array(
                'default' => '',
                'description' => 'Set the size of the output file'
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

        // check the output and size arguments
        if (!$arguments['output']) {
            \cli\out("Error: Missing required argument --output\n");
            \cli\out($arguments->getHelpScreen());
            \cli\out("\n\n");
            die(1);
        } else {
            $output_path = $arguments['output'];
            $output_path = realpath(dirname($output_path)) .
                '/' . 
                basename($output_path);
        }

        // check to make sure the file doesn't already exist
        if (file_exists($output_path) == true) {
            \cli\err("ERROR: The output file already exists\n");
            die(1);
        }

        if (!$arguments['size']) {
            \cli\out("Error: Missing required argument --size\n");
            \cli\out($arguments->getHelpScreen());
            \cli\out("\n\n");
            die(1);
        } else {
            // convert the output size
            try {
                $output_size = Files::convertSize($arguments['size']);
            } catch (InvalidArgumentException $ex) {
                \cli\err("ERROR: unrecognised size format\n");
                die(1);
            }
        }

        // output some information
        \cli\out(
            "Creating file of at least " . 
            $output_size .
            " bytes in size to:\n $output_path\n"
        );

        // check to make sure the data source is available
        if (is_readable(self::SOURCE_DATA_PATH) === false) {
            \cli\err(
                "ERROR: unable to access the data source path: " .
                self::SOURCE_DATA_PATH . "\n"
            );
            die(1);
        }

        // open the source data file
        $source_handle = fopen(self::SOURCE_DATA_PATH, 'rb');

        if ($source_handle == false) {
            \cli\err(
                "ERROR: unable to open the data source path: " .
                self::SOURCE_DATA_PATH . "\n"
            );
            die(1);
        }

        // open the output data file
        $output_handle = fopen($output_path, 'wb');

        if ($output_handle == false) {
            \cli\err("ERROR: unable to open the output path\n");
            die(1);
        }

        // copy the data
        $file_size = 0;
        $notify_size = 0;
        $notify_size_limit = 1024 * self::CHUNK_SIZE;

        //debug code
        $notify = new \cli\notify\Spinner('Creating File:');
        $notify->display();

        while ($file_size < $output_size) {
            $data = fread($source_handle, self::CHUNK_SIZE);
            fwrite($output_handle, $data);

            $file_size += self::CHUNK_SIZE;

            // update the notifier
            $notify->tick();
        }

        // play nice and tidy up
        fclose($output_handle);
        fclose($source_handle);

        $notify->finish();

        \cli\out(
            "SUCCESS: File successfully created with final size: " .
            Files::humanReadableSize(filesize($output_path)) . "\n"
        );
    }
}

// Make sure the script is run only
// on Mac OS X and the CLI
if (!System::isMacOSX() || !System::isOnCLI()) {
    die("This script can only be run on the CLI on Mac OS X");
} else {
    $app = new FileCreator();
    $app->doTask();
}
