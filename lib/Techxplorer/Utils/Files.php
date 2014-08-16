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

    /**
     * Recursively search the file system looking for files
     *
     * start at the specified $parent_dir and if required filter by $extension
     *
     * @param string $parent_dir the parent directory to search
     * @param string $extension  an optional file name extension
     *                           used to filter results
     *
     * @return array a string array of matching files, or null on failure
     *
     * @throws InvalidArgumentException if the $parent_dir parameter is null
     * @throws FileNotFoundException    if the $parent_dir cannot be accessed
     */
    public static function findFiles($parent_dir, $extension=null)
    {
        // check on the parameters
        if ($parent_dir == null || trim($parent_dir) == false) {
            throw new \InvalidArgumentException(
                'the $parent_dir argument cannot be null or an empty string'
            );
        }

        // ensure path ends in slash
        if (substr($parent_dir, -1) != '/') {
            $parent_dir .= '/';
        }

        // check to make sure that the directory exists
        if (file_exists($parent_dir) == false) {
            throw new FileNotFoundException($parent_dir);
        }

        // store the length of the extension for later
        if ($extension != null) {
            $ext_length = strlen($extension) * -1;
        }

        // store lists of files and directories
        $files = array();
        $directories = array();
        array_push($directories, $parent_dir);

        // loop through the list of directories
        while (count($directories) > 0) {

            // open a handle to the next directory
            $directory = array_shift($directories);
            $dir_handle = opendir($directory);

            // check to make sure we got a directory handle
            if ($dir_handle == false) {
                return null;
            }

            // list all of the files
            while (false !== ($file = readdir($dir_handle))) {

                // build the entire path
                $file = $directory . $file;

                // skip the . and .. files
                if ($file == "$directory." || $file == "$directory..") {
                    continue;
                }

                // check to see if this is a file or directory
                if (is_dir($file) == true) {
                    // this is a directory, add it to the list of directories
                    array_push($directories, $file . "/");

                } else {
                    // check to see if this file matches the extension, if required
                    if ($extension != null) {
                        if (substr($file, $ext_length) == $extension) {
                            // store the file
                            array_push($files, $file);
                        }
                    } else {
                        // store the file
                        array_push($files, $file);
                    }
                }
            }
        }

        // return the list of files
        return $files;
    }

    /**
     * Filter a list of paths to only those that match list of extensions
     *
     * @param array $paths      the list of paths to process
     * @param array $extensions the list of allowed extensions
     *
     * @return array the list of filtered paths
     *
     * @throws \InvalidArgumentException if the arguments do not pass validation
     */
    public static function filterPathsByExt($paths, $extensions)
    {
        // check the arguments
        if (!is_array($paths)) {
            throw new \InvalidArgumentException(
                'The $paths argument must be an array'
            );
        }

        if (!is_array($extensions)) {
            throw new \InvalidArgumentException(
                'The $extensions argument must be an array'
            );
        }

        if (count($paths) == 0) {
            throw new \InvalidArgumentException(
                'The $paths argument must have at least one element'
            );
        }

        if (count($extensions) == 0) {
            throw new \InvalidArgumentException(
                'The $extensions argument must have at least one element'
            );
        }

        // filter the list of paths
        $filtered = array();
        $criteria = array();

        foreach ($extensions as $extension) {
            $criteria[$extension] = strlen($extension) * -1;
        }

        foreach ($paths as $path) {
            foreach ($criteria as $extension => $length) {
                if (substr($path, $length) == $extension) {
                    $filtered[] = $path;
                    continue 2; // skip to the end of the outer loop
                }
            }
        }

        return $filtered;
    }
}
