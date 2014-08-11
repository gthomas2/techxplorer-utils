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

namespace Techxplorer\FineDiff;

use \cogpowered\FineDiff\Render\Renderer;

/**
 * A custom FineDiff renderer which counts deletions and insertions
 * Based heavily on he standard HTML renderer
 *
 * @category TechxplorerUtils
 * @package  TechxplorerUtils
 * @author   techxplorer <corey@techxplorer.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     https://github.com/techxplorer/techxplorer-utils
 */
class StatsRenderer extends Renderer
{
    private $_deletes = array();
    private $_inserts  = array();

    /**
     * Fake rendering the diff
     *
     * @param string $opcode      operation code to undertake
     * @param string $from        string to work with
     * @param int    $from_offset character offset to work with
     * @param int    $from_len    number of character to work with
     *
     * @return string the modified string
     */
    public function callback($opcode, $from, $from_offset, $from_len)
    {
        if ($opcode === 'c') { // copy
            $string = substr($from, $from_offset, $from_len);
        } else if ($opcode === 'd') { //delete
            $deletion = substr($from, $from_offset, $from_len);

            if (strcspn($deletion, " \n\r") === 0) {
                $deletion = str_replace(
                    array("\n","\r"),
                    array('\n','\r'), $deletion
                );
            }

            $string = $deletion;

            $for_stat = $this->_trimString($deletion);
            if ($for_stat != '') {
                $this->_deletes[] = $for_stat;
            }
        } else {
            $string = substr($from, $from_offset, $from_len);
            $for_stat = $this->_trimString($string);
            if ($for_stat != '') {
                $this->_inserts[] = $for_stat;
            }
        }

        return $string;
    }

    /**
     * Trim a string of all punctuation and change it to lower case
     *
     * @param string $string the string to trim
     *
     * @return string the trimmed string
     */
    private function _trimString($string)
    {
        return strtolower(trim(preg_replace('/[^0-9a-z ]+/i', '', $string)));
    }

    /**
     * Reset the stats collection arrays
     *
     * @return void
     */
    public function resetStats()
    {
        $this->_deletes = array();
        $this->_inserts = array();
    }

    /**
     * Get the stats data arrays
     *
     * @return array an array with two elements 'deletes' and 'inserts'
     */
    public function getStats()
    {
        return array (
            $this->_deletes,
            $this->_inserts,
        );
    }
}
