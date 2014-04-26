<?php
/**
 * This file is part of Techxplorer's Util script library.
 *
 * Techxplorer's Util script library is free software: you can redistribute it
 * and/or modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * Techxplorer's Util script library is distributed in the hope that it will
 * be useful, but WITHOUT ANY WARRANTY; without even the implied
 * warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Techxplorer's Util script library.
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

/**
 * A class of file, and file system, related utility methods
 *
 * @category TechxplorerUtils
 * @package  TechxplorerUtils
 * @author   techxplorer <corey@techxplorer.com>
 * @license  http://opensource.org/licenses/GPL-3.0 GNU Public License v3.0
 * @link     https://github.com/techxplorer/techxplorer-utils
 */
class FileUtils
{
    /**
     * format a file size from byte count to human readable format
     *
     * @param int $bytes    the number of bytes
     * @param int $decimals the number of decimal places to output
     *
     * @return string the file size in a human readable format
     *
     * @throws IllegalArgumentException if either argument is not an integer
     *
     * @link http://jeffreysambells.com/2012/10/25/human-readable-filesize-php
     */
    public static function humanReadableSize($bytes, $decimals = 2)
    {
        // check the arguments
        if (!is_int($bytes) || !is_int($decimals)) {
            throw new IllegalArgumentException(
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
     * @throws IllegalArgumentException if the argument is not a valid string
     * @throws FileNotFoundException if the app cannot be found
     */
    public static function findApp($name)
    {
        // check the argument
        if ($name == null || trim($name) == '') {
            throw new IllegalArgumentException('The $name parameter is required');
        }

        // find the application
        $command = escapeshellcmd("which $name");
        $path = trim(shell_exec($command));

        if ($path == null || $path == '') {
            throw new FileNotFoundException($path);
        }

        return $path;
    }

    /**
     * Load a configuration file. Either the default file, or an override file
     *
     * @param string $name the name of the config file, excluding extension
     *
     * @return array an array of configuration values
     *
     * @throws IllegalArgumentException if the argument is not a valid string
     * @throws FileNotFoundException if the app cannot be found
     */
    public static function loadConfig($name)
    {
        // store the parsed config
        $config = null;

        // check the argument
        if ($name == null || trim($name) == '') {
            throw new IllegalArgumentException('The $name parameter is required');
        }

        // build the paths
        $default_path  = realpath(_DIR__ . '/../data/' . $name . '.json.dist');
        $override_path = realpath(_DIR__ . '/../data/' . $name . '.json');

        if ($default_path == false && $override_path == false) {
            throw new FileNotFoundException(
                $name . '.json.dist and ' . $name . '.json'
            );
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
class FileNotFoundException extends RuntimeException
{
    /**
     * Constructor
     *
     * @param string $path The path to the file that was not found
     */
    public function __construct($path)
    {
        parent::__construct(sprintf('The file "%s" does not exist', $path));
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
class ConfigParseException extends RuntimeException
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
