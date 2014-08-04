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

use \Techxplorer\Utils\FileNotFoundException;

/**
 * A utility class used to write Markdown formatted log files
 *
 * @category TechxplorerUtils
 * @package  TechxplorerUtils
 * @author   techxplorer <corey@techxplorer.com>
 * @license  http://opensource.org/licenses/GPL-3.0 GNU Public License v3.0
 * @link     https://github.com/techxplorer/techxplorer-utils
 */
class Logger
{
    private $_handle = false;
    private $_path;
    private $_is_new = false;

    /**
     * Constructor
     *
     * @param string $path The path to the log file
     *
     * @throws \Techxplorer\Utils\FileNotFoundException
     * @throws \InvalidArgumentException
     */
    public function __construct($path)
    {
        // start a new file if necessary, if not open existing file
        if (is_file($path)) {
            // file already exists
            if (!is_writeable($path)) {
                throw new \InvalidArgumentException(
                    'The file specified by $path must be writable'
                );
            }

            $this->_handle = fopen($path, 'a');

            if ($this->_handle == false) {
                throw new FileNotFoundException($path);
            }

            $this->_path = $path;

        } else if (is_dir(dirname($path))) {
            // create the file in the directory
            if (!is_writable(dirname($path))) {
                throw new \InvalidArgumentException(
                    'The directory specidied by $path must be writable'
                );
            }

            $this->_handle = fopen($path, 'w');

            if ($this->_handle == false) {
                throw new FileNotFoundException($path);
            }

            $this->_path = $path;
            $this->_is_new = true;

        } else {
            throw new \InvalidArgumentException(
                'The $path argument must be a path to the log file'
            );
        }
    }

    /**
     * Return the path to the log file
     *
     * @return the path to the log file
     */
    public function getPath()
    {
        return $this->_path;
    }

    /**
     * Test if this is a new log file
     *
     * @return boolean true if new log file, false if not
     */
    public function isNewLog()
    {
        return $this->_is_new;
    }

    /**
     * Write a heading to the log
     *
     * @param string $heading the text of the heading
     * @param int    $level   the heading level
     *
     * @return boolean true on success, false on failure
     *
     * @throws \InvalidArgumentException
     */
    public function writeHeading($heading, $level)
    {

        if (is_int($level) || ctype_digit($level)) {
            $level = (int) $level;
        } else {
            throw new \InvalidArgumentException(
                'The $level argument must be an integer'
            );
        }

        if ($heading == null || trim($heading) == '') {
            throw new \InvalidArgumentException(
                'The $heading argument must be a valid string'
            );
        }

        $marker = '';

        for ($i = 0; $i < $level; $i++) {
            $marker .= '=';
        }

        $header = "$marker $heading $marker\n";

        return $this->writeLine($header);
    }

    /**
     * Write a paragraph to the log file
     *
     * @param string $paragraph the paragraph to write to the log file
     *
     * @return boolean true on success, false on failure
     *
     * @throws \InvalidArgumetException
     */
    public function writeParagraph($paragraph)
    {
        if ($paragraph == null || trim($paragraph) == '') {
            throw new \InvalidArgumentException(
                'The $paragraph argument must be a valid string'
            );
        }

        return $this->writeLine($paragraph . "\n");
    }

    /**
     * Write a line to the log file
     *
     * @param string $line the line to write to the log file
     *
     * @return boolean true on success, false on failure
     */
    public function writeLine($line)
    {
        $written = fwrite(
            $this->_handle,
            $line . "\n"
        );

        if ($written === false) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Write an unordered list to the log file
     *
     * @param array $elements the list of elements to write
     *
     * @return boolean true on success, false on failure
     *
     * @throws \InvalidArgumentException
     */
    public function writeList($elements)
    {
        if (!is_array($elements)) {
            throw new InvalidArgumentException(
                'The $elements argument must be an array'
            );
        }

        foreach ($elements as $element) {

            if (trim($element) == '') {
                continue;
            }

            $written = $this->writeLine('- ' . $element);

            if (!$written) {
                return false;
            }
        }

        return $this->writeLine('');
    }

    /**
     * Deconstructor
     */
    public function __destruct()
    {
        if ($this->_handle != false) {
            fclose($this->_handle);
        }
    }
}
