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
class MdlLangConvert
{

    /**
     * defines a name for the script
     */
    const SCRIPT_NAME = "Techxplorer's Moodle Lang Convert script";

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
                'description' => 'The path to the Moodle lang dir'
            )
        );

        $arguments->addOption(
            array('custom', 'c'),
            array(
                'default' => '',
                'description' => 'The path to the custom lang file'
            )
        );

        $arguments->addOption(
            array('xml', 'x'),
            array(
                'default' => '',
                'description' => 'The path to the translation.xml file'
            )
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

            if (!is_dir($moodle_path)) {
                \cli\err(
                    "%rERROR: %wThe path specified by --moodle must be a directory\n"
                );
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

            if (!is_file($custom_path)) {
                \cli\err(
                    "%rERROR: %wThe path specified by --custom must be a file\n"
                );
                \cli\err("\n");
                die(1);
            }
        }

        /*
        if (!$arguments['xml']) {
            \cli\err("%rERROR: %wMissing required argument --xml\n");
            \cli\err($arguments->getHelpScreen());
            \cli\err("\n");
            die(1);
        } else {
            $xml_path = realpath($arguments['xml']);

            if ($xml_path == false) {
                \cli\err("%rERROR: %wUnable to find path specified by --xml\n");
                \cli\err("\n");
                die(1);
            }

            if (!is_file($xml_path)) {
                \cli\err(
                    "%rERROR: %wThe path specified by --xml must be a file\n"
                );
                \cli\err("\n");
                die(1);
            }
        }
        */

         // fake a moodle install
        define('MOODLE_INTERNAL', true);

        // instantiate the comparator class, which will do the comparison
        $comparator = new MdlLangComparator($moodle_path);

        // load the files
        $custom_paths = array($custom_path);

        list($paths, $skipped_paths) = $comparator->matchPaths(
            $custom_paths
        );

        if (count($paths) > 0) {
            //load the file
            try {
                $lang_data = $comparator->loadLangFile($paths[0][0], $paths[0][1]);
            } catch(\Exception $e) {
                \cli\err("%rERROR: %wAn exception occured during processing\n");
                \cli\err($e->getMessage());
                \cli\err("\n\n");
                die(1);
            }
        }

        //TODO load the xml file

        // calculate the differences in the strings if required
        $lang_data->getUnusedKeys();

        if ($lang_data->getUnusedCount() < $lang_data->getCustomCount()) {
            $comparator->calculateDiffs($lang_data);
        } else {
            \cli\out("%yWARNING: %wAll string customisations appear not be used\n");
            \cli\out("         In this version of Moodle.\n");
            \cli\out("\n");
            die(0);
        }

        // output some helpful information
        \cli\out("\nMoodle lang file: '{$lang_data->getMoodlePath()}'\n");
        \cli\out("Custom lang file: '{$lang_data->getCustomPath()}'\n");
        \cli\out("Moodle strings: {$lang_data->getMoodleCount()}\n");
        \cli\out("Custom strings: {$lang_data->getCustomCount()}\n");

        if ($lang_data->getUnusedCount() > 0) {
            \cli\out(
                'Unused customisations found: ' .
                $lang_data->getUnusedKeys(true) . "\n"
            );
        }

        $added   = 0;
        $skipped = 0;

        // process each of the customisations
        foreach ($lang_data->getDiffs() as $key => $diff) {
            \cli\out("Key: $key\n");
            \cli\out("$diff\n");
            $confirmed = \cli\confirm("Add this customisation");

            if ($confirmed) {
                //TODO add the string to the xml file
                \cli\out("%yWARNING: %wnot written yet\n");
                $added++;
            } else {
                \cli\out("Skipping customisation\n");
                $skipped++;
            }
        }

        // TODO close the xml file
        //
        \cli\out("%gSUCCESS: %wSuccessfully update translation file\n");
        \cli\out("         Customisations added: $added\n");
        \cli\out("         Customisations skipped: $skipped\n");
    }
}

// make sure script is only run on the cli
if (System::isOnCLI()) {
    // yes
    $app = new MdlLangConvert();
    $app->doTask();
} else {
    // no
    die("This script can only be run on the cli\n");
}
