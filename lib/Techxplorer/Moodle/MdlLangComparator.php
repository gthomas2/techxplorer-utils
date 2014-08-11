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
 * PHP Version 5.4
 *
 * @category TechxplorerUtils
 * @package  TechxplorerUtils
 * @author   techxplorer <corey@techxplorer.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     https://github.com/techxplorer/techxplorer-utils
 */

namespace Techxplorer\Moodle;

use \Techxplorer\Utils\FileNotFoundException;

use \Techxplorer\Moodle\MdlLangInfo;

use \Techxplorer\FineDiff\StatsRenderer;
use \Techxplorer\FineDiff\TextColourRenderer;

use \cogpowered\FineDiff\Diff;
use \cogpowered\FineDiff\Granularity\Word;

/**
 * A class of convenience methods used for comparing Moodle language files
 *
 * TODO add unit tests
 *
 * @category TechxplorerUtils
 * @package  TechxplorerUtils
 * @author   techxplorer <corey@techxplorer.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     https://github.com/techxplorer/techxplorer-utils
 *
 */
class MdlLangComparator
{
    private $_moodle_path;

    private $_lookup_table;

    /**
     * Constructor for the class
     *
     * @param string $moodle_path full path to Moodle lang dir or lang file
     */
    public function __construct($moodle_path)
    {
        if (!is_readable($moodle_path)) {
            throw new \InvalidArgumentException(
                'The path specified by $moodle_path must be readable'
            );
        }

        $this->_moodle_path = $moodle_path;
        $this->_lookup_table = array();
    }

    /**
     * Match a list of custom paths to Moodle paths
     *
     * @param array $custom_paths the paths to the custom lang files
     *
     * @return array array[0] matched paths array[1] skipped paths
     */
    public function matchPaths($custom_paths)
    {
        if (!is_array($custom_paths)) {
            throw new \InvalidArgumentException(
                'The $custom_paths argument must be an array'
            );
        }

        // path prefixes
        static $block_path = null;
        static $mod_path;
        static $enrol_path;
        static $repo_path;
        static $report_path;
        static $auth_path;

        if ($block_path == null) {
            $block_path  = realpath($this->_moodle_path . '/../../blocks');
            $mod_path    = realpath($this->_moodle_path . '/../../mod');
            $enrol_path  = realpath($this->_moodle_path . '/../../enrol');
            $repo_path   = realpath($this->_moodle_path . '/../../repository');
            $report_path = realpath($this->_moodle_path . '/../../report');
            $auth_path   = realpath($this->_moodle_path . '/../../auth');
        }

        // store the paths for later
        $paths = array();
        $skipped_paths = array();

        // find matching Moodle lang files
        foreach ($custom_paths as $custom_path) {
            $file_name = basename($custom_path);

            if (!strncmp($file_name, 'block_', 6)) {
                // this is a block
                $new_path = $this->_findLangFile(
                    'block_',
                    $file_name,
                    $block_path
                );
                if ($new_path != false) {
                    $paths[] = array($new_path, $custom_path);
                    $hash = md5($custom_path);
                    $this->_lookup_table[$hash] = array(
                        'type' => MdlLangInfo::TYPE_BLOCK
                    );
                } else {
                    $skipped_paths[] = $custom_path;
                }
            } else if (!strncmp($file_name, 'enrol_', 6)) {
                // this is an enrol plugin
                $new_path = $this->_findLangFile(
                    'enrol_',
                    $file_name,
                    $enrol_path
                );
                if ($new_path != false) {
                    $paths[] = array($new_path, $custom_path);
                    $hash = md5($custom_path);
                    $this->_lookup_table[$hash] = array(
                        'type' => MdlLangInfo::TYPE_ENROL
                    );
                } else {
                    $skipped_paths[] = $custom_path;
                }
            } else if (!strncmp($file_name, 'report_', 7)) {
                // this is a report plugin
                $new_path = $this->_findLangFile(
                    'report_',
                    $file_name,
                    $report_path
                );
                if ($new_path != false) {
                    $paths[] = array($new_path, $custom_path);
                    $hash = md5($custom_path);
                    $this->_lookup_table[$hash] = array(
                        'type' => MdlLangInfo::TYPE_REPORT
                    );
                } else {
                    $skipped_paths[] = $custom_path;
                }
            } else if (!strncmp($file_name, 'repository_', 11)) {
                // this is a repository plugin
                $new_path = $this->_findLangFile(
                    'repository_',
                    $file_name,
                    $repo_path
                );
                if ($new_path != false) {
                    $paths[] = array($new_path, $custom_path);
                    $hash = md5($custom_path);
                    $this->_lookup_table[$hash] = array(
                        'type' => MdlLangInfo::TYPE_REPOSITORY
                    );
                } else {
                    $skipped_paths[] = $custom_path;
                }
            } else if (!strncmp($file_name, 'auth_', 5)) {
                // this is an auth plugin
                $new_path = $this->_findLangFile(
                    'auth_',
                    $file_name,
                    $repo_path
                );
                if ($new_path != false) {
                    $paths[] = array($new_path, $custom_path);
                    $hash = md5($custom_path);
                    $this->_lookup_table[$hash] = array(
                        'type' => MdlLangInfo::TYPE_AUTH
                    );
                } else {
                    $skipped_paths[] = $custom_path;
                }
            } else if (is_file($this->_moodle_path . "/$file_name")) {
                // this is a core moodle file
                $paths[] = array($this->_moodle_path . "/$file_name", $custom_path);
                $hash = md5($custom_path);
                $this->_lookup_table[$hash] = array(
                    'type' => MdlLangInfo::TYPE_CORE
                );
            } else {
                // is this a module?
                $dir = basename($file_name, '.php');
                $new_path = $mod_path . "/$dir/lang/en/$file_name";
                if (is_file($new_path)) {
                    $paths[] = array($new_path, $custom_path);
                    $hash = md5($custom_path);
                    $this->_lookup_table[$hash] = array(
                        'type' => MdlLangInfo::TYPE_MOD
                    );
                } else {
                    // must be something else
                    $skipped_paths[] = $custom_path;
                }
            }
        }

        return array($paths, $skipped_paths);
    }

    /**
     * Determine the path to a language file
     *
     * @param string $prefix      the language file prefix
     * @param string $file_name   the name of the file
     * @param string $parent_path the parent path to the file
     *
     * @return mixed string|bool the path to the file, or false on failure
     */
    private function _findLangFile($prefix, $file_name, $parent_path)
    {
        $dir = str_replace($prefix, '', basename($file_name, '.php'));
        $new_path = $parent_path . "/$dir/lang/en/$file_name";

        if (is_file($new_path)) {
            return $new_path;
        } else {
            return false;
        }
    }

    /**
     * Calculate the differences between Moodle and the customisations
     *
     * @param MdlLangInfo &$data the already loaded lang data
     *
     * @return void
     */
    public function calculateDiffs(&$data)
    {
        if ($data->getUnusedCount() == $data->getCustomCount()) {
            // all strings are unused, nothing to do
            return;
        }

        // calculate the diffs
        $diffs = array();
        $granularity = new Word;
        $renderer = new TextColourRenderer;
        $differ = new Diff($granularity, $renderer);

        $moodle_strings = $data->getMoodleStrings();
        $custom_strings = $data->getCustomStrings();

        $custom_keys = $data->getUsedKeys();

        foreach ($custom_keys as $key) {
            $diffs[$key] = $differ->render(
                $moodle_strings[$key],
                $custom_strings[$key]
            );
        }

        $data->setDiffs($diffs);
    }

    /**
     * Calculate the word replacements statistics
     *
     * @param MdlLangInfo &$data the already loaded lang data
     *
     * @return void
     */
    public function calculateStats(&$data)
    {
        if ($data->getUnusedCount() == $data->getCustomCount()) {
            // all strings are unused, nothing to do
            return;
        }

        // keep track of stats
        $stats = array();

        $granularity = new Word;
        $renderer = new StatsRenderer;
        $differ = new Diff($granularity, $renderer);

        $moodle_strings = $data->getMoodleStrings();
        $custom_strings = $data->getCustomStrings();

        $custom_keys = $data->getUsedKeys();

        foreach ($custom_keys as $key) {
            $diff = $differ->render($moodle_strings[$key], $custom_strings[$key]);

            list($deletes, $inserts) = $renderer->getStats();

            foreach ($deletes as $delete) {
                if (isset($stats[$delete])) {
                    $stats[$delete]['delete'] = $stats[$delete]['delete'] + 1;
                } else {
                    $stats[$delete] = array('delete' => 1, 'insert' => 0);
                }
            }

            foreach ($inserts as $insert) {
                if (isset($stats[$insert])) {
                    $stats[$insert]['insert'] = $stats[$insert]['insert'] + 1;
                } else {
                    $stats[$insert] = array('delete' => 0, 'insert' => 1);
                }
            }

            $renderer->resetStats();
        }

        $data->setStats($stats);
    }

    /**
     * Load the strings from a Moodle and Custom language file
     *
     * @param string $moodle_path the path to the Moodle language file
     * @param string $custom_path the path to the custom langague file
     *
     * @return \Techxplorer\Moodle\MdlLangInfo an instantiated MdlLangInfo object
     *
     * @throws \Techxplorer\Utils\FileNotFoundException if a path cannot be found
     * @throws \InvalidArgumentException if the lang files
     *                                   are for different components
     */
    public function loadLangFile($moodle_path, $custom_path)
    {
        // double check the parameters
        if (!is_file($moodle_path) || !is_readable($moodle_path)) {
            throw new FileNotFoundException($moodle_path);
        }

        if (!is_file($custom_path) || !is_readable($custom_path)) {
            throw new FileNotFoundException($custom_path);
        }

        // ensure the lang files are for the same components
        if (basename($moodle_path) != basename($custom_path)) {
            throw new \InvalidArgumentException(
                'The $moodle_path & $custom_path cannot be for different components'
            );
        }

        $data = new MdlLangInfo($moodle_path, $custom_path);

        // load the strings
        include_once $moodle_path;
        $moodle_strings = $string;
        unset($string);
        include_once $custom_path;
        $custom_strings = $string;
        unset($string);

        // add the strings the data object
        $data->setMoodleStrings($moodle_strings);
        $data->setCustomStrings($custom_strings);
        $data->setPluginType($this->_lookup_table[md5($custom_path)]['type']);

        return $data;
    }
}
