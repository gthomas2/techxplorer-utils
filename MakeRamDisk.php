#!/usr/bin/env php 
<?php
/**
 * This file is part of Techxplorer's Make RAM Disk script.
 *
 * Techxplorer's Make RAM Disk script is free software: you can redistribute it
 * and/or modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * Techxplorer's Make RAM Disk script is distributed in the hope that it will
 * be useful, but WITHOUT ANY WARRANTY; without even the implied
 * warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Techxplorer's Make RAM Disk script.
 * If not, see <http://www.gnu.org/licenses/>
 *
 * This is a PHP script which can be used to create a RAM disk on Mac OS X
 *
 * PHP version 5
 *
 * @category TechxplorerUtils
 * @package  TechxplorerUtils
 * @author   techxplorer <corey@techxplorer.com>
 * @license  http://opensource.org/licenses/GPL-3.0 GNU Public License v3.0
 * @link     https://github.com/techxplorer/techxplorer-utils
 */

// adjust error reporting to aid in development
error_reporting(E_ALL);

// include the required libraries
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/lib/FileUtils.php';

/**
 * Main driving class of the script
 *
 * @category TechxplorerUtils
 * @package  TechxplorerUtils
 * @author   techxplorer <corey@techxplorer.com>
 * @license  http://opensource.org/licenses/GPL-3.0 GNU Public License v3.0
 * @link     https://github.com/techxplorer/techxplorer-utils
 *
 */
class MakeRamDisk
{

    /**
     * defines a name for the script
     */
    const SCRIPT_NAME = "Techxplorer's Make RAM Disk script";

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
    const LICENSE_URI = 'http://opensource.org/licenses/GPL-3.0';

    /**
     * defines the default RAM disk name
     */
    const DEFAULT_NAME = 'RAM Disk';

    /**
     * defines the default size in MB
     */
    const DEFAULT_SIZE = 1024;

    /**
     * main driving function
     *
     * @return void
     */
    public function doTask() 
    {
        // output some helpful text
        \cli\out(self::SCRIPT_NAME . ' - ' . self::SCRIPT_VERSION . "\n");
        \cli\out('License: ' . self::LICENSE_URI . "\n\n");

        // prepare arguments
        $arguments = new \cli\Arguments($_SERVER['argv']);

        $arguments->addOption(
            array('name', 'n'),
            array(
                'default' => self::DEFAULT_NAME,
                'description' => 'The name of the new RAM disk'
            )
        );

        $arguments->addOption(
            array('size', 's'),
            array(
                'default' => self::DEFAULT_SIZE,
                'description' => 'The size of the new RAM disk in MB'
            )
        );

        $arguments->addFlag(array('help', 'h'), 'Show this help screen');

        // parse arguments
        $arguments->parse();

        if ($arguments['help']) {
            \cli\out($arguments->getHelpScreen() . "\n\n");
            die(0);
        }

        $disk_name = self::DEFAULT_NAME;
        $disk_size = self::DEFAULT_SIZE;

        if ($arguments['name']) {
            $disk_name = $arguments['name'];
        }

        if ($arguments['size']) {
            $disk_size = $arguments['size'];
            if (!is_numeric($disk_size)) {
                \cli\err("%rERROR: %wthe size argument must be numeric\n");
                die(-1);
            }
            
            $disk_size = (int)$disk_size;

            if (!is_int($disk_size)) {
                \cli\err("%rERROR: %wthe size argument must be an integer\n");
                die(-1);
            }
        }

        // output some more info 
        \cli\out("INFO: using disk name: $disk_name\n");
        \cli\out(
            "INFO: using disk size: " .
            FileUtils::humanReadableSize($disk_size * 1024 * 1024) .
            "\n"
        );

        // convert the disk size to blocks
        $disk_size = $disk_size * 2048;

        // find the necessary apps
        try {
            $diskutil_path = FileUtils::findApp('diskutil');
        } catch (FileNotFoundException $ex) {
            \cli\err("%rERROR: %wthe 'diskutil' app could not be found.\n");
            die(-1);
        }

        try {
            $hdiutil_path = FileUtils::findApp('hdiutil');
        } catch (FileNotFoundException $ex) {
            \cli\err("%rERROR: %wthe 'hdiutil' app could not be found.\n");
            die(-1);
        }

        // create the volume
        $command = escapeshellcmd(
            $hdiutil_path .
            ' attach -nomount ram://' .
            $disk_size
        );

        $output;
        $return_var;

        exec($command, $output, $return_var);

        if ($return_var != 0) {
            \cli\err("%rERROR: %w unable to create the volume\n");
            die(-1);
        }

        $volume_id = $output[0];

        // make the file system
        // diskutil erasevolume HFS+ 'RAM Disk'
        $command = escapeshellcmd(
            $diskutil_path . 
            " erasevolume HFS+ '$disk_name' $volume_id"
        );

        $output = null;
        $return_var = null;

        exec($command, $output, $return_var);

        if ($return_var != 0) {
            \cli\err("%rERROR: %wunable to format the volume\n");
            die(-1);
        }

        // output some info
        \cli\out(
            "%gSUCCESS: %wthe volume '" . 
            $volume_id .
            "' named '" . 
            $disk_name . 
            "' has been created\n"
        );
    }
}

// make sure script is only run on the cli
if (substr(php_sapi_name(), 0, 3) == 'cli') {
    // yes
    $app = new MakeRamDisk();
    $app->doTask();
} else {
    // no
    die("This script can only be run on the cli\n");
}
