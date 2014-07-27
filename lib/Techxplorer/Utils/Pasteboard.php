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
use \Techxplorer\Utils\Files as Files;

use InvalidArgumentException;
use \Techxplorer\Utils\FileNotFoundException;

/**
 * A class of Pasteboard (clipboard) related utility methods
 *
 * @category TechxplorerUtils
 * @package  TechxplorerUtils
 * @author   techxplorer <corey@techxplorer.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     https://github.com/techxplorer/techxplorer-utils
 */
class Pasteboard
{
    // private class level variables
    private $_pbcopy;
    private $_cat;

    /**
     * Instantiate the class and find the required helper apps
     *
     * @throws FileNotFoundException if the helper apps cannot be found
     */
    public function __construct()
    {
        $this->_pbcopy = Files::findApp('pbcopy');
        $this->_cat    = Files::findApp('cat');
    }

    /**
     * Put the supplied data into the pasteboard
     *
     * @param string $data the data to put into the pasteboard
     *
     * @return boolean true on sucess, false on failure
     *
     * @throws InvalidArgumentException if the data argument is invalid
     */
    public function put($data)
    {
        // validate the argument
        $data = trim($data);

        if ($data == null || $data == '') {
            throw new InvalidArgumentException(
                'The $data parameter cannot be empty'
            );
        }

        // save the data in a temp file
        $handle = tmpfile();
        $meta_data = stream_get_meta_data($handle);
        $filename = $meta_data['uri'];

        fwrite($handle, $data);

        $command = "{$this->_cat} $filename | {$this->_pbcopy}";
        $output = array();
        $return_var = '';

        exec($command, $output, $return_var);

        if ($return_var != 0) {
            return false;
        }

        fclose($handle);

        return true;
    }
}
