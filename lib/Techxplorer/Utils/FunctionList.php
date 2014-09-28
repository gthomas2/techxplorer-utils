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
 * PHP Version 5.5
 *
 * @category TechxplorerUtils
 * @package  TechxplorerUtils
 * @author   techxplorer <corey@techxplorer.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     https://github.com/techxplorer/techxplorer-utils
 */

namespace Techxplorer\Utils;

/**
 * A class of utility methods used to build a list of functions in a PHP file
 *
 * TODO add unit tests
 *
 * @category TechxplorerUtils
 * @package  TechxplorerUtils
 * @author   techxplorer <corey@techxplorer.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     https://github.com/techxplorer/techxplorer-utils
 */
class FunctionList
{
    private $_input_path;
    private $_functions;

    /**
     * Look for functions that are defined in a php file
     */
    const DEFINED_FUNCTIONS = 0;

    /**
     * Look for functions are are used in a php file
     */
    const USED_FUNCTIONS = 1;

    /**
     * Class constructor
     *
     * @param string $input_path path to the input file
     *
     * @throws FileNotFoundException if the input path cannot be found
     * @throws \InvalidArgumentException if the $input_path argument is invalid
     */
    public function __construct($input_path = null)
    {

        // double check the parameter
        if ($input_path == null | trim($input_path) == "") {
            throw new InvalidArgumentException(
                'The $input_path parameter cannot be null or an empty string'
            );
        }

        if (!is_file($input_path) || !is_readable($input_path)) {
            throw new FileNotFoundException($input_path);
        }

        $this->_input_path = $input_path;
        $this->_functions = null;
    }

    /**
     * Build a list of functions
     *
     * @param int $type_list the type of functions to find
     *
     * @return mixed  int the number of functions found or false on failure
     *
     * @throws FileNotFoundException if the input path cannot be read
     */
    public function buildList($type_list = self::DEFINED_FUNCTIONS)
    {
        switch($type_list) {
        case self::DEFINED_FUNCTIONS:
            return $this->_buildDefinedFuncList();
            break;
        case self::USED_FUNCTIONS:
            return $this->_buildUsedFuncList();
            break;
        default:
            throw new IllegalArgumentException(
                'Unrecognised $type_list parameter'
            );
        }
    }

    /**
     * Build a list of functions that are defined in a php file
     *
     * @return mixed  int the number of functions found or false on failure
     *
     * @throws FileNotFoundException if the input path cannot be read
     */
    private function _buildDefinedFuncList()
    {
        // get all of the tokens
        $tokens = token_get_all(file_get_contents($this->_input_path));

        if (count($tokens) == 0) {
            return false;
        }

        $this->_functions = array();

        $skip = 0;
        $next_is_function = false;

        // look for all of the functions
        foreach ($tokens as $token) {
            // skip entries we know we don't need
            if ($skip > 0) {
                $skip--;
                continue;
            }

            // skip anything that isn't an array
            if (!is_array($token)) {
                continue;
            }

            // is this the start of a function definition?
            if ($token[0] == T_FUNCTION) {
                $next_is_function = true;
                $skip = 1;
                continue;
            }

            if ($next_is_function && $token[0] == T_STRING) {
                $this->_functions[] = array(
                    'name' => $token[1],
                    'line' => $token[2]
                );
                $next_is_function = false;
            }
        }

        return count($this->_functions);
    }

    /**
     * Build a list of functions that are used in a php file
     *
     * @return mixed int the number of functions found or false on failure
     *
     * @TODO better handling of functions of objects
     *
     * @throws FileNotFoundException if the input path cannot be read
     */
    private function _buildUsedFuncList()
    {
        // get all of the tokens
        $tokens = token_get_all(file_get_contents($this->_input_path));

        if (count($tokens) == 0) {
            return false;
        }

        $this->_functions = array();

        $possible_function = null;
        $old_token   = null;
        $older_token = null;

        foreach ($tokens as $token) {
            if (is_array($token)) {
                if ($token[0] == T_STRING) {
                    $possible_function = $token;
                }
            } else {
                if ($token == '(' && $possible_function != null) {
                    if (is_array($older_token)
                        && $older_token[0] != T_OBJECT_OPERATOR
                        && $older_token[0] != T_DOUBLE_COLON
                    ) {
                        $this->_functions[] = array(
                            'name' => $possible_function[1],
                            'line' => $possible_function[2]
                        );
                    } elseif (!is_array($older_token)) {
                        $this->_functions[] = array(
                            'name' => $possible_function[1],
                            'line' => $possible_function[2]
                        );
                    }
                }

                $possible_function = null;
            }

            $older_token = $old_token;
            $old_token = $token;
        }

        return count($this->_functions);
    }

    /**
     * Return the list of defined functions
     *
     * @param int $type_list the type of functions to find
     *
     * @return array a list of functions
     *
     * @throws FileNotFoundException if the input path cannot be read
     */
    public function getList($type_list = self::DEFINED_FUNCTIONS)
    {
        if (!is_array($this->_functions)) {
            $this->buildList($type_list);
        }
        return $this->_functions;
    }
}
