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

use \Techxplorer\Utils\System;
use \Techxplorer\Utils\FileNotFoundException;

/**
 * A utility class for working with the pasteboard on Mac OS X
 *
 * @package Techxplorer
 * @subpackage Utils
 */
class Pasteboard
{
    /** @var $pbcopy the path to the pbcopy command */
    protected $pbcopy;

    /** @car $cat the path to the cat command */
    protected $cat;

    /** @var $pbpaste the path to the pbpaste command */
    protected $pbpaste;

    /**
     * Constructor for this class
     *
     * @throws FileNotFoundException if any of the required apps cannot be found
     */
    public function __construct()
    {
        if (!System::isMacOSX()) {
            throw new \RuntimeException('This clas can only be used on Mac OS X');
        }

        $this->pbcopy = System::findApp('pbcopy');
        if ($this->pbcopy == false) {
            throw new FileNotFoundException('pbcopy');
        }

        $this->cat = System::findApp('cat');
        if ($this->cat == false) {
            throw new FileNotFoundException('cat');
        }

        $this->pbpaste = System::findApp('pbpaste');
        if ($this->pbpaste == false) {
            throw new FileNotFoundException('pbpaste');
        }
    }

    /**
     * Copy the supplied data into the pasteboard
     *
     * @param string $data the data to copy
     *
     * @return bool true on success, false on failure
     *
     * @throws InvalidArgumentException if any of the arguments fail validation
     */
    public function copy($data)
    {
        $data = trim($data);

        if (empty($data)) {
            throw new \InvalidArgumentException('The $data parameter is required');
        }

        // Save the data in a temp file.
        $handle = tmpfile();
        $filename = stream_get_meta_data($handle)['uri'];

        fwrite($handle, $data);

        // Copy the data to the pasteboard.
        $command = "{$this->cat} $filename | {$this->pbcopy}";
        $output = array();
        $return = '';

        exec($command, $output, $return);

        fclose($handle);

        if ($return != 0) {
            return false;
        }

        return true;
    }

    /**
     * Paste the supplied data from the pasteboard
     *
     * @return mixed bool|string the data from the pasteboard or false on failure
     */
    public function paste()
    {
        // Copy the data from the pasteboard.
        $output = array();
        $return = '';

        exec($this->pbpaste, $output, $return);

        if ($return != 0) {
            return false;
        }

        $data = trim(implode("\n", $output));

        return $data;
    }
}
