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
 * A class of password related utility methods
 *
 * @category TechxplorerUtils
 * @package  TechxplorerUtils
 * @author   techxplorer <corey@techxplorer.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     https://github.com/techxplorer/techxplorer-utils
 */
class Password
{
    /**
     * define the lower case set identifier
     */
    const LOWER_CASE = 'l';

    /**
     * define the lower case set
     */
    const LOWER_CASE_SET = 'abcdefghjkmnpqrstuvwxyz';

    /**
     * define the upper case set identifier
     */
    const UPPER_CASE = 'u';

    /**
     * define the upper case set
     */
    const UPPER_CASE_SET = 'ABCDEFGHJKMNPQRSTUVWXYZ';

    /**
     * define the digit set identifier
     */
    const DIGITS = 'd';

    /**
     * define the diigts set
     */
    const DIGITS_SET = '23456789';

    /**
     * define the symbol set identifier
     */
    const SYMBOLS = 's';

    /**
     * define the symblos set
     */
    const SYMBOLS_SET = '!@#$&*?';

    /**
     * generate a new password
     *
     * @param integer $length the length of the password
     * @param bool    $dashes add dashes to the password
     * @param string  $sets   the list of character sets to use
     *
     * @return string the generated password
     *
     * @link https://gist.github.com/tylerhall/521810 Original Implementation
     */
    public static function generate($length=8, $dashes=false, $sets='luds')
    {
        $use_sets = array();

        // determine which character sets to use
        if (strpos($sets, self::LOWER_CASE) !== false) {
            $sets = str_replace(self::LOWER_CASE, '', $sets);
            $use_sets[] = self::LOWER_CASE_SET;
        }

        if (strpos($sets, self::UPPER_CASE) !== false) {
            $sets = str_replace(self::UPPER_CASE, '', $sets);
            $use_sets[] = self::UPPER_CASE_SET;
        }

        if (strpos($sets, self::DIGITS) !== false) {
            $sets = str_replace(self::DIGITS, '', $sets);
            $use_sets[] = self::DIGITS_SET;
        }

        if (strpos($sets, self::SYMBOLS) !== false) {
            $sets = str_replace(self::SYMBOLS, '', $sets);
            $use_sets[] = self::SYMBOLS_SET;
        }

        // validate parameters
        if (strlen($sets) > 0) {
            throw new InvalidArgumentException(
                "Unrecognised character set identifier'$sets'"
            );
        }

        if (!is_numeric($length)) {
            throw new InvalidArgumentException(
                'The $length parameter must be numeric'
            );
        }

        if ($length < 0) {
            throw new InvalidArgumentException(
                'The $length parameter must be greater than 0'
            );
        }

        if (!is_bool($dashes)) {
            throw new InvalidArgumentException(
                'The $dashes paramters must be a boolean value'
            );
        }

        $all = ''; 
        $password = ''; 

        foreach ($use_sets as $set) {
            $password .= $set[array_rand(str_split($set))];
            $all .= $set;
        }   

        $all = str_split($all);

        for ($i = 0; $i < $length - count($use_sets); $i++) {
            $password .= $all[array_rand($all)];
        }   

        $password = str_shuffle($password);

        if (!$dashes) {
            return $password;
        }

        $dash_len = floor(sqrt($length));
        $dash_str = '';

        while (strlen($password) > $dash_len) {
            $dash_str .= substr($password, 0, $dash_len) . '-';
            $password = substr($password, $dash_len);
        }

        $dash_str .= $password;

        return $dash_str;
    }
}
