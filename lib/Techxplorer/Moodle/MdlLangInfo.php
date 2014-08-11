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

/**
 * A class to manage data about a Moodle Lang file comparison
 *
 * @category TechxplorerUtils
 * @package  TechxplorerUtils
 * @author   techxplorer <corey@techxplorer.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     https://github.com/techxplorer/techxplorer-utils
 */
class MdlLangInfo
{
    private $_moodle_path;
    private $_custom_path;
    private $_moodle_strings = array();
    private $_custom_strings = array();

    private $_unused_keys = null;

    private $_diffs = array();

    private $_stats = array();

    private $_type = -1;

    /**
     * Constant representing a core Moodle file
     */
    const TYPE_CORE = 0;

    /**
     * Constant representing a lang file for a block
     */
    const TYPE_BLOCK = 1;

    /**
     * Constant representing a lang file for a mod
     */
    const TYPE_MOD = 2;

    /**
     * Constant representing a lang file for a enrol plugin
     */
    const TYPE_ENROL = 3;

    /**
     * Constant representing a lang file for a repository plugin
     */
    const TYPE_REPOSITORY = 4;

    /**
     * Constant representing a lang file for a report plugin
     */
    const TYPE_REPORT = 5;

    /**
     * Constant representing a lang file for an auth plugin
     */
    const TYPE_AUTH = 6;

    /**
     * Constant representing a lang file for an unknown type
     */
    const TYPE_UNKNOWN = -1;

    /**
     * Construct a new MdlLangInfo object
     *
     * @param string $moodle_path the path to the moodle lang file
     * @param string $custom_path the path to the custom lang file
     */
    public function __construct($moodle_path, $custom_path)
    {
        $this->_moodle_path = $moodle_path;
        $this->_custom_path = $custom_path;
    }

    /**
     * Return the path to the moodle file
     *
     * @return the full path to the moodle file
     */
    public function getMoodlePath()
    {
        return $this->_moodle_path;
    }

    /**
     * Return the path to the custom file
     *
     * @return the full path to the custom file
     */
    public function getCustomPath()
    {
        return $this->_custom_path;
    }

    /**
     * Set the list of Moodle lang strings
     *
     * @param array $strings an array of language strings
     *
     * @return void
     */
    public function setMoodleStrings($strings)
    {
        ksort($strings);
        $this->_moodle_strings = $strings;
    }

    /**
     * Set the list of custom lang strings
     *
     * @param array $strings an array of language strings
     *
     * @return void
     */
    public function setCustomStrings($strings)
    {
        ksort($strings);
        $this->_custom_strings = $strings;
    }

    /**
     * Return the Moodle language strings
     *
     * @return array the moodle language strings
     */
    public function getMoodleStrings()
    {
        return $this->_moodle_strings;
    }

    /**
     * Return the custom language strings
     *
     * @return array the custom langauge strings
     */
    public function getCustomStrings()
    {
        return $this->_custom_strings;
    }

    /**
     * Return the number of strings in the Moodle lang file
     *
     * @return int the number of strings in the Moodle lang file
     */
    public function getMoodleCount()
    {
        return count($this->_moodle_strings);
    }

    /**
     * Return the number of strings in the custom lang file
     *
     * @return int the number of strings in the custom lang file
     */
    public function getCustomCount()
    {
        return count($this->_custom_strings);
    }

    /**
     * Get the list of unused customisations
     *
     * @param bool $format format the list of unused customisations
     *
     * @return mixed array of keys, or formatted list of keys
     */
    public function getUnusedKeys($format = false)
    {
        if ($this->_unused_keys == null) {
            // determine if there are any unusued keys
            $moodle_keys = array_keys($this->_moodle_strings);
            $custom_keys = array_keys($this->_custom_strings);

            $this->_unused_keys = array_values(
                array_diff(
                    $custom_keys,
                    $moodle_keys
                )
            );
        }

        if ($format) {
            // return formatted string
            $string = '';
            foreach ($this->_unused_keys as $key) {
                $string .= ", $key";
            }

            return substr($string, 2);
        } else {
            return $this->_unused_keys;
        }
    }

    /**
     * Get the list of used customisations
     *
     * @param bool $format format the list of used customisations
     *
     * @return mixed array of keys, or fomatted list of keys
     */
    public function getUsedKeys($format = false)
    {
        $custom_keys = array_keys($this->getCustomStrings());
        $custom_keys = array_values(
            array_diff(
                $custom_keys,
                $this->getUnusedKeys()
            )
        );

        if ($format) {
            // return formatted string
            $string = '';
            foreach ($custom_keys as $key) {
                $string .= ", $key";
            }

            return substr($string, 2);
        } else {
            return $custom_keys;
        }
    }

    /**
     * Get the number of unusued cusomisations
     *
     * @return the number of unusued customisations
     */
    public function getUnusedCount()
    {
        return count($this->_unused_keys);
    }

    /**
     * Set the list of diffs between Moodle and customisation
     *
     * @param array $diffs the list of differences
     *
     * @return void
     */
    public function setDiffs($diffs)
    {
        $this->_diffs = $diffs;
    }

    /**
     * Get the list of diffs
     *
     * @return array the list of diffs
     */
    public function getDiffs()
    {
        return $this->_diffs;
    }

    /**
     * Get he number of diffs found in the strings
     *
     * @return int the number of diffs
     */
    public function getDiffCount()
    {
        return count($this->_diffs);
    }

    /**
     * Set the diff stats for this lang file
     *
     * @param array $stats the list of stats
     *
     * @return void
     */
    public function setStats($stats)
    {
        $this->_stats = $stats;
    }

    /**
     * Get the stats records
     *
     * @return array an array of stats records
     */
    public function getStats()
    {
        return $this->_stats;
    }

    /**
     * Get the number of stats records
     *
     * @return int the number of stats records
     */
    public function getStatsCount()
    {
        return count($this->_stats);
    }

    /**
     * Set the plugin type for this lang file
     *
     * @param int $type the plugin type
     *
     * @return void
     *
     * @throws \InvalidArgumentException if type is not one of the named constants
     */
    public function setPluginType($type)
    {
        if (!is_numeric($type)) {
            throw new \InvalidArgumentException(
                'Invalid plugin type detected'
            );
        }

        if ($type >= -1 && $type <= 6) {
            $this->_type = $type;
        } else {
            throw new \InvalidArgumentException(
                'Invalid plugin type detected'
            );
        }
    }

    /**
     * Get the plugin type that this lang file belongs to
     *
     * @return int the plugin type
     */
    public function getPluginType()
    {
        return $this->_type;
    }
}
