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
use \Techxplorer\Utils\Logger;

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
    const SCRIPT_VERSION = 'v1.0.2';

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
            array('skip', 's'),
            array(
                'default' => '',
                'description' => 'Comma separated list of replacements to skip'
            )
        );

        $arguments->addOption(
            array('log', 'l'),
            array(
                'default' => '',
                'description' => 'The path to the log file'
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
            \cli\out("The skip list is a list of terms that when they are the\n");
            \cli\out("only delettions & insertions the customisation is skipped.\n");
            \cli\out("For example: course,site\n");
            \cli\out("If %rcourse%w is replaced with %gsite%w and this is the\n");
            \cli\out("only difference, the customisation would be skipped\n");
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

        $skip = false;
        $skip_inserts = array();
        $skip_deletes = array();

        if ($arguments['skip']) {
            $skip_list = explode(',', $arguments['skip']);

            if (count($skip_list) % 2 != 0) {
                \cli\err(
                    "%rERROR: %wThe skip list must be an even number of terms\n"
                );
                \cli\err("\n");
                die(1);
            }

            for ($i = 0; $i < count($skip_list); $i++) {
                if ($i %2 == 0) {
                    $skip_inserts[] = $skip_list[$i] . ' ';
                } else {
                    $skip_deletes[] = $skip_list[$i] . ' ';
                }
            }

            $skip = true;
        }

        $log = null;

        if ($arguments['log']) {
            try {
                $log = new Logger($arguments['log']);
            } catch (Exception $e) {
                \cli\err("%rERROR: %wUnable to start logging\n");
                \cli\err($e->getMessage() . "\n");
                die(1);
            }
        }

        if ($log != null && $log->isNewLog()) {
            $log->writeHeading(
                'Log created by: ' .
                self::SCRIPT_NAME .
                ' - ' .
                self::SCRIPT_VERSION,
                1
            );
        }

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

            try {
                $xmllint_path = Files::findApp('xmllint');

                $command = "{$xmllint_path} --noout {$xml_path} 2>&1";

                $output = array();
                $return_var = '';
                exec($command, $output, $return_var);

                // check to see if the xml is well formed
                if ($return_var != 0) {
                    \cli\err(
                        "%rERROR: %wThe file specified by --xml" .
                        " is not well formed xml\n"
                    );
                    die(1);
                }
            } catch (FileNotFoundException $ex) {
                \cli\err(
                    "%yWARNING: unable to find xmllint, assuming xml is valid\n"
                );
            }
        }

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

        $added   = array();
        $skipped = array();

        // process each of the customisations
        foreach ($lang_data->getDiffs() as $key => $diff) {

            \cli\out("Key: $key\n");

            if ($skip && $this->_shouldSkip($skip_deletes, $skip_inserts, $diff)) {
                \cli\out("Skipping customisation\n");
                $skipped[] = $key;
                continue;
            }

            \cli\out("$diff\n");
            $confirmed = \cli\confirm("Add this customisation");

            if ($confirmed) {
                $added[] = $key;
            } else {
                \cli\out("Skipping customisation\n");
                $skipped[] = $key;
            }
        }

        // check to see if updating the xml file is necessary
        if (count($added) == 0) {
            \cli\out("%gSUCCESS: %wNo customisations need to be converted\n");
            die();
        }

        // update the xml file
        try {
            $this->_updateXML($xml_path, $lang_data, $added);
        } catch (Exception $e) {
            \cli\err("%rERROR: %wUnable to update the xml file\n");
            \cli\err($e->getMessage());
            die(1);
        }

        // Write the log file
        if ($log != null) {
            $log->writeHeading('Processed lang file', 2);
            $log->writeParagraph(date('Y-m-d'));
            $log->writeParagraph($custom_path);
            $log->writeHeading('Skipped keys', 3);
            $log->writeList($skipped);
            $log->writeHeading('Added keys', 3);
            $log->writeList($added);
        }

        \cli\out("%gSUCCESS: %wSuccessfully updated translation file\n");
        \cli\out("         Customisations added: " . count($added) ."\n");
        \cli\out("         Customisations skipped: " . count($skipped) ."\n");
    }

    /**
     * Update the xml file with the translations
     *
     * @param string      $xml_path  path to the xml file
     * @param MdlLangInfo $lang_data the string cusomisation data
     * @param array       $added     keys of the added customisations
     *
     * @return void;
     */
    private function _updateXML($xml_path, $lang_data, $added)
    {
        // determine the name of the node
        $node_name = basename($lang_data->getCustomPath(), '.php');
        $translations = $lang_data->getCustomStrings();

        // adjust the node name if required
        if ($lang_data->getPluginType() == $lang_data::TYPE_CORE) {
            $node_name = 'core_' . $node_name;
        } else if ($lang_data->getPluginType() == $lang_data::TYPE_MOD) {
            $node_name = 'mod_' . $node_name;
        }

        if ($node_name == 'core_moodle') {
            $node_name = 'core';
        }

        // build a list of new nodes
        $new_dom = new \DOMDocument('1.0', 'UTF-8');
        $new_translation = $new_dom->createElement($node_name);
        $new_dom->appendChild($new_translation);

        foreach ($added as $to_add) {
            $replace_node = $new_dom->createElement('replace');
            $replace_attr = $new_dom->createAttribute('id');
            $replace_attr->value = $to_add;
            $replace_node->appendChild($replace_attr);
            $replace_node->nodeValue = $translations[$to_add];
            $new_translation->appendChild($replace_node);
        }

        // load the old xml and import the new translations
        $old_dom = new \DOMDocument('1.0', 'UTF-8');
        $old_dom->preserveWhiteSpace = false;
        $old_dom->formatOutput = true;
        $old_dom->load($xml_path);
        $root_node = $old_dom->getElementsByTagName('translation')->item(0);
        $imported_node = $old_dom->importNode($new_translation, true);
        $root_node->appendChild($imported_node);
        $old_dom->save($xml_path);
    }

    /**
     * Determine if this customisation should be skipped
     *
     * @param array  $skip_inserts a list of inserts
     * @param array  $skip_deletes a list of deletes
     * @param string $diff         the diff of the customisation
     *
     * @return boolean true if it should be skipped, false if it should not
     */
    private function _shouldSkip($skip_inserts, $skip_deletes, $diff)
    {
        $diff = strtolower($diff);
        foreach ($skip_deletes as $delete) {
            $diff = $this->_filterDiff(
                '%r',
                $delete,
                $diff
            );
        }

        foreach ($skip_inserts as $insert) {
            $diff = $this->_filterDiff(
                '%g',
                $insert,
                $diff
            );
        }

        if (strpos($diff, '%w') === false) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * private function to help in determining if a change should be skipped
     *
     * @param string $prefix the prefix of the change
     * @param string $change the text of the change
     * @param string $diff   the actual diff
     *
     * @return string the filtered diff
     */
    private function _filterDiff($prefix, $change, $diff)
    {
        $diff = str_replace(
            $prefix . $change . '%w',
            '',
            $diff
        );

        $diff = str_replace(
            $prefix . trim($change) . '%w',
            '',
            $diff
        );

        static $punctuation = array(
            '.',
            ')',
        );

        foreach ($punctuation as $p) {

            $diff = str_replace(
                $prefix . trim($change) . $p . '%w',
                '',
                $diff
            );
        }

        return $diff;
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
