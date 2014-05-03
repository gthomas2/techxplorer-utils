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
 * This is a PHP script which can be used to collect the pix
 * used by Moodle plugins and copy them into a structure used
 * by themes to override them.
 *
 * It is useful during upgrade projects when you want to use the 
 * icons from a previous version of the plugin.
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
class MdlPluginPixCollector
{
    /**
     * defines a name for the script
     */
    const SCRIPT_NAME = "Techxplorer's Moodle Plugin Pix Collector";

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
     * main driving function
     *
     * @return void
     */
    public function doTask()
    {
        // output some helper text
        \cli\out(self::SCRIPT_NAME . ' - ' . self::SCRIPT_VERSION . "\n");
        \cli\out('License: ' . self::LICENSE_URI . "\n\n");

        // maintain a list of plugin types
        $allowed_plugin_types = array(
            'mod' => 'mod'
        );

        $allowed_pugin_type_keys = array_keys($allowed_plugin_types);

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
                'description' => 'Set the path to the input directory'
            )
        );

        $arguments->addOption(
            array('type', 't'),
            array(
                'default' => $allowed_pugin_type_keys[0],
                'description' => 'Set the type of plugin to work with'
            )
        );

        $arguments->addFlag(array('help', 'h'), 'Show this help screen');

        $arguments->addFlag(array('verbose', 'v'), 'Enable verbose output');

        // parse the arguments
        $arguments->parse();

        // show the help screen if required
        if ($arguments['help']) {
            \cli\out($arguments->getHelpScreen() . "\n\n");
            \cli\out("Allowed plugin types:\n");
            $tree = new \cli\Tree;
            $tree->setData($allowed_pugin_type_keys);
            $tree->setRenderer(new \cli\tree\Markdown(2));
            $tree->display();
            \cli\out("\n\n");
            die(0);
        }

        // check the arguments
        if (!$arguments['output']) {
            \cli\err("%rERROR: %wMissing required argument --output\n");
            \cli\err($arguments->getHelpScreen());
            \cli\err("\n");
            die(1);
        } else {
            $output_path = $arguments['output'];
            $output_path = realpath($output_path);

            if (!is_dir($output_path)) {
                \cli\err("%rERROR: %wUnable to locate output directory\n");
                die(1);
            }
        }

        if (!$arguments['input']) {
            \cli\err("%rERROR: %wMissing required argument --input\n");
            \cli\err($arguments->getHelpScreen());
            \cli\err("\n");
            die(1);
        } else {
            $input_path = $arguments['input'];
            $input_path = realpath($input_path);

            if (!is_dir($input_path)) {
                \cli\err("%rERROR: %wUnable to locate input directory\n");
                die(1);
            }
        }

        if (!$arguments['type']) {
            \cli\out(
                '%yWARNING: %wusing default plugin type ' . 
                "'{$allowed_pugin_type_keys[0]}'\n"
            );
            $plugin_type = $allowed_pugin_type_keys[0];
        } else {
            if (!in_array($arguments['type'], $allowed_pugin_type_keys)) {
                \cli\err("%rERROR: %wunknown plugin type detected\n");
                \cli\out("Allowed plugin types:\n");
                $tree = new \cli\Tree;
                $tree->setData($allowed_pugin_type_keys);
                $tree->setRenderer(new \cli\tree\Markdown(2));
                $tree->display();
                \cli\out("\n\n");
            }
        }

        $verbose = false;

        if ($arguments['verbose']) {
            $verbose = true;
        }

        // check to see if the input directory exists
        $input_path .= '/' . $allowed_plugin_types[$plugin_type];
        if (!is_dir($input_path)) {
            \cli\err("%rERROR: %wunable to find input path:\n$input_path\n");
            die(1);
        }

        // check to see if the output directory exists
        // if not, create it
        $output_path .= '/pix_plugins/' . $allowed_plugin_types[$plugin_type];
        if (!is_dir($output_path)) {
            if (!mkdir($output_path, 0777, true)) {
                \cli\err("%rERROR: %wunable to create output path:\n$output_path\n");
                die(1);
            }
        }

        // get a list of directories representing the list of plugins
        $dir_list = new DirectoryIterator($input_path);

        // loop through each directory
        foreach ($dir_list as $dir) {
            if ($dir->isDot() || !$dir->isDir()) {
                continue;
            }

            $pix_path = $dir->getPathname() . '/pix/';

            // see if this plugin has a pix folder
            if (is_dir($pix_path)) {
                // yes it does

                // build the destination path
                $dest_path = $output_path . '/' . $dir->getBasename();

                // see if the destination path exists
                // if not, create it
                if (!is_dir($dest_path)) {
                    if (!mkdir($dest_path)) {
                        \cli\err(
                            "%rERROR: %wunable to create output path:\n" .
                            "$dest_path\n"
                        );
                        die(1);
                    }
                }

                // copy the images
                foreach (new DirectoryIterator($pix_path) as $pix) {
                    if ($pix->isDot()) {
                        continue;
                    }

                    if ($pix->isDir()) {
                        \cli\out(
                            "%yWARNING: %wskipping unexpected directory:\n" .
                            "{$pix->getPathName()}\n"
                        );
                        continue;
                    }

                    // does the destination file already exists?
                    $dest_pix = $dest_path . '/' . $pix->getBaseName();

                    if (is_file($dest_pix)) {
                        if ($verbose) {
                            \cli\out(
                                "%yWARNING: %wskipping file, it already exists:\n" .
                                "{$dest_pix}\n"
                            );
                            continue;
                        }
                    }

                    // copy the file
                    $dest_pix = $dest_path . '/' . $pix->getBaseName();
                    if (!copy($pix->getPathname(), $dest_pix)) {
                        \cli\err(
                            "%rERROR: %wunable to copy file:\n" . 
                            "{$pix->getPathname()} -> {$dest_pix}\n"
                        );
                        die(1);
                    } else {
                        if ($verbose) {
                            \cli\out(
                                "INFO: copied:\n" . 
                                "{$pix->getPathname()}\n->\n$dest_pix\n"
                            );
                        }
                    }
                }

            } else {
                \cli\out("INFO: no pix found for:\n{$dir->getPathname()}\n");
            }

        }

        \cli\out("\n%gSUCCESS: %wplugin pix successfully copied\n\n");
    }
}

// Make sure the script is run only
// on Mac OS X and the CLI
if (!System::isMacOSX() || !System::isOnCLI()) {
    die("This script can only be run on the CLI on Mac OS X");
} else {
    $app = new MdlPluginPixCollector();
    $app->doTask();
}
