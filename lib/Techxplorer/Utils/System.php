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

/**
 * A class of system and OS  related utility methods
 *
 * @category TechxplorerUtils
 * @package  TechxplorerUtils
 * @author   techxplorer <corey@techxplorer.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     https://github.com/techxplorer/techxplorer-utils
 */
class System
{
    /**
     * Determine if the server operating system is Mac OS X
     *
     * @return boolean true if Mac OS X, false if it isn't
     */
    public static function isMacOSX()
    {
        // get the constant with consistent case
        $os = strtoupper(PHP_OS);

        // check to see if it matches what we expect for Mac OS X
        if ($os == 'DARWIN') {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Determine if the script is running on the CLI
     *
     * @return boolean true if on CLI, false if it isn't
     */
    public static function isOnCLI()
    {
        // get the server api
        $sapi = strtoupper(php_sapi_name());

        if (substr($sapi, 0, 3) == 'CLI') {
            return true;
        } else {
            return false;
        }
    }
}
