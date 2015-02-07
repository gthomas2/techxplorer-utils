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

namespace Techxplorer\Moodle;

use \Techxplorer\Utils\Files;
use \Techxplorer\Utils\FileNotFoundException;

/**
 * A collection of Moodle related functions
 *
 * @package Techxplorer
 * @subpackage Moodle
 */
class Moodle
{
    /**
     * Construct a new Moodle utility class
     */
    public function __construct() {
        if (!defined('MOODLE_INTERNAL')) {
            define('MOODLE_INTERNAL', true);
        }
    }

    /**
     * Read a Moodle language file
     *
     * @param string $path the path the language file
     *
     * @return array an array of strings found in the language file
     *
     * @throws InvalidArgumentException if the arguments are not valid
     * @throws \Techxplorer\Utils\FileNotFoundException if the file cannot be found
     */
    public function loadLangStrings($path) {
        $path = trim($path);

        if (empty($path)) {
            throw new \InvalidArgumentException('The $path argument is required');
        }

        $path = Files::isPathValid($path);

        $string = array();

        require_once($path);

        return $string;
    }
}
