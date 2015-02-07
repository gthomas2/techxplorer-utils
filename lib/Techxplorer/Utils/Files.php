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
 * @author techxplorer <corey@techxplorer.com>
 * @copyright techxplorer 2015
 * @license GPL-3.0+
 * @see https://github.com/techxplorer/techxplorer-utils
 * @version 2.0
 */

namespace Techxplorer\Utils;

use \Techxplorer\Utils\FileNotFoundException;

/**
 * A collection of file system related functions
 *
 * @package Techxplorer
 * @subpackage Utils
 */
class Files
{
    /**
     * Path is to a file
     */
    const TYPE_FILE = 0;

    /**
     * Path is to a directory
     */
    const TYPE_DIRECTORY = 1;

    /**
     * Validate the given file path
     *
     * A file path is valid if the file can be found and can be read
     *
     * @param string $path the file system path to validate
     * @param int    $type the type of path to validate (default: TYPE_FILE)
     *
     * @return string the full validated path to the file
     *
     * @throws InvalidArgumentException if the supplied arguments are invalid
     * @throws FileNotFoundException    if the path cannot be validated
     */
    public static function isPathValid($path, $type = Files::TYPE_FILE) {
        $path = trim($path);

        if (empty($path)) {
            throw new \InvalidArgumentException('The $path argument is required');
        }

        $realpath = realpath($path);

        if (empty($path)) {
            throw new FileNotFoundException($path);
        }

        if ($type == FILES::TYPE_FILE) {
            if (is_readable($realpath) && is_file($realpath)) {
                return $realpath;
            }
        }

        if ($type == FILES::TYPE_DIRECTORY) {
            if (is_readable($realpath) && is_dir($realpath)) {
                return $realpath;
            }
        }

        throw new FileNotFoundException($realpath);
    }
}
