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

namespace Techxplorer\Utils;
use InvalidArgumentException;

/**
 * A class of file, and file system, related utility methods
 *
 * @category TechxplorerUtils
 * @package  TechxplorerUtils
 * @author   techxplorer <corey@techxplorer.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     https://github.com/techxplorer/techxplorer-utils
 */
class Files
{
    /**
     * format a file size from byte count to human readable format
     *
     * @param int $bytes    the number of bytes
     * @param int $decimals the number of decimal places to output
     *
     * @return string the file size in a human readable format
     *
     * @throws InvalidArgumentException if either argument is not an integer
     *
     * @link http://jeffreysambells.com/2012/10/25/human-readable-filesize-php
     */
    public static function humanReadableSize($bytes, $decimals = 2)
    {
        // check the arguments
        if (!is_int($bytes) || !is_int($decimals)) {
            throw new InvalidArgumentException(
                'The $bytes and $decimals parameters must be integers'
            );
        }
        $size = array('B','kB','MB','GB','TB','PB','EB','ZB','YB');
        $factor = floor((strlen($bytes) - 1) / 3);
        return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) .
            @$size[$factor];
    }

    /**
     * find a command line application
     *
     * @param string $name the name of the command
     *
     * @return string the path to the command line application
     *
     * @throws InvalidArgumentException if the argument is not a valid string
     * @throws FileNotFoundException if the app cannot be found
     */
    public static function findApp($name)
    {
        // check the argument
        if ($name == null || trim($name) == '') {
            throw new InvalidArgumentException('The $name parameter is required');
        }

        // find the application
        $command = escapeshellcmd("which $name");
        $path = trim(shell_exec($command));

        if ($path == null || $path == '') {
            throw new FileNotFoundException($name);
        }

        return $path;
    }

    /**
     * Load a configuration file. Either the default file, or an override file
     *
     * @param string $path the name of the config file
     *
     * @return array an array of configuration values
     *
     * @throws InvalidArgumentException if the argument is not a valid string
     * @throws FileNotFoundException if the app cannot be found
     */
    public static function loadConfig($path)
    {
        // store the parsed config
        $config = null;

        // check the argument
        if ($path == null || trim($path) == '') {
            throw new InvalidArgumentException('The $path parameter is required');
        }

        // build the paths
        $default_path  = realpath($path . '.dist');
        $override_path = realpath($path);

        if ($default_path == false && $override_path == false) {
            throw new FileNotFoundException($path);
        }

        // load the override path first
        if ($override_path != false) {
            $config = json_decode(file_get_contents($override_path), true);

            // check if it was able to be parsed
            if ($config != null) {
                return $config;
            } else {
                throw new ConfigParseException($override_path);
            }
        } else {
            $config = json_decode(file_get_contents($default_path), true);

            // check if it was able to be parsed
            if ($config != null) {
                return $config;
            } else {
                throw new ConfigParseException($default_path);
            }
        }
    }

    /**
     * Convert a file size from human readable format to byte count
     *
     * @param string $value the value to convert
     *
     * @return int the converted size in bytes
     *
     * @throws InvalidArgumentException if the argument is not a valid format
     */
    public static function convertSize($value)
    {
        // check the argument
        if ($value == null || trim($value) == '') {
            throw new InvalidArgumentException('The $value parameter is required');
        }

        // convert the size
        $value = strtoupper($value);

        // list the valid units
        $units = array('KB', 'MB', 'GB');

        // loop through looking for a matching unit
        foreach ($units as $i => $unit) {
            if ($unit == substr($value, -2)) {
                return $value * pow(1024, $i + 1);
            }
        }

        // if we get this far, the string didn't parse
        throw new InvalidArgumentException('The $value could not be parsed');
    }
}

/**
 * An exception to indicate a file was not found
 *
 * @category TechxplorerUtils
 * @package  TechxplorerUtils
 * @author   techxplorer <corey@techxplorer.com>
 * @license  http://opensource.org/licenses/GPL-3.0 GNU Public License v3.0
 * @link     https://github.com/techxplorer/techxplorer-utils
 */
class FileNotFoundException extends \RuntimeException
{
    /**
     * Constructor
     *
     * @param string $path The path to the file that was not found
     */
    public function __construct($path)
    {
        parent::__construct(
            sprintf(
                'The file "%s" does not exist',
                $path
            )
        );
    }
}

/**
 * An exception to indicate a config file could not be parsed
 *
 * @category TechxplorerUtils
 * @package  TechxplorerUtils
 * @author   techxplorer <corey@techxplorer.com>
 * @license  http://opensource.org/licenses/GPL-3.0 GNU Public License v3.0
 * @link     https://github.com/techxplorer/techxplorer-utils
 */
class ConfigParseException extends \RuntimeException
{
    /**
     * Constructor
     *
     * @param string $path The path to the file that failed parsing
     */
    public function __construct($path)
    {
        parent::__construct(
            sprintf(
                'The config file "%s" could not be parsed',
                $path
            )
        );
    }
}
