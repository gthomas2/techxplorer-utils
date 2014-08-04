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
 * @category TechxplorerUtils-Test
 * @package  TechxplorerUtils-Test
 * @author   techxplorer <corey@techxplorer.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     https://github.com/techxplorer/techxplorer-utils
 */

use Techxplorer\Utils\Logger as Logger;

/**
 * Test the Logger class
 *
 * @category TechxplorerUtils-Tests
 * @package  TechxplorerUtils-Tests
 * @author   techxplorer <corey@techxplorer.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     https://github.com/techxplorer/techxplorer-utils
 */
class LoggerTest extends PHPUnit_Framework_TestCase
{

    private $_path = false;

    /**
     * Test the constructor
     *
     * @return void
     */
    public function testConstructor()
    {
        $log = new Logger($this->_path);

        $this->assertFileExists($this->_path);

        return $log;
    }

    /**
     * Test the constructor
     *
     * @expectedException InvalidArgumentException
     *
     * @return void
     */
    public function testConstructorTwo()
    {
        global $CFG;
        $log = new Logger($CFG->data_root . '/not-writable.txt');
    }

    /**
     * Test the constructor
     *
     * @expectedException InvalidArgumentException
     *
     * @return void
     */
    public function testConstructorThree()
    {
        global $CFG;
        $log = new Logger($CFG->data_root . '/not-writable/log.txt');
    }

    /**
     * Test the get path function
     *
     * @return void
     */
    public function testGetPath()
    {
        $log = new Logger($this->_path);
        $this->assertEquals($this->_path, $log->getPath());
        $this->assertFileExists($log->getPath());
    }

    /**
     * Test the isNewLog function
     *
     * @return void
     */
    public function testIsNewLog()
    {
        unlink($this->_path);

        $log = new Logger($this->_path);
        $this->assertTrue($log->isNewLog());
    }

    /**
     * Test the writeHeading function
     *
     * @return void
     */
    public function testWriteHeading()
    {
        $log = new Logger($this->_path);

        $heading  = 'This is a heading';
        $expected = "= This is a heading =\n";

        $log->writeHeading($heading, 1);
        $this->assertStringEqualsFile($this->_path, $expected);

        $log->writeHeading($heading, 2);
        $expected .= "== This is a heading ==\n";
        $this->assertStringEqualsFile($this->_path, $expected);

        $log->writeHeading($heading, 3);
        $expected .= "=== This is a heading ===\n";
        $this->assertStringEqualsFile($this->_path, $expected);

        $log->writeHeading($heading, 4);
        $expected .= "==== This is a heading ====\n";
        $this->assertStringEqualsFile($this->_path, $expected);
    }

    /**
     *  Test the writeHeading function
     *
     *  @expectedException InvalidArgumentException
     *
     *  @return void
     */
    public function testWriteHeadingTwo()
    {
        $log = new Logger($this->_path);

        $log->writeHeading('', 1);
    }

    /**
     *  Test the writeHeading function
     *
     *  @expectedException InvalidArgumentException
     *
     *  @return void
     */
    public function testWriteHeadingThree()
    {
        $log = new Logger($this->_path);

        $log->writeHeading('    ', 1);
    }

    /**
     *  Test the writeHeading function
     *
     *  @expectedException InvalidArgumentException
     *
     *  @return void
     */
    public function testWriteHeadingFour()
    {
        $log = new Logger($this->_path);

        $log->writeHeading('heading', 'x');
    }

    /**
     * Test the writeHeading function
     *
     * @expectedException InvalidArgumentException
     *
     * @return void
     */
    public function testWriteHeadingFive()
    {
        $log = new Logger($this->_path);

        $log->writeHeading('heading', 1.1);
    }


    /**
     * Test the writeLine function
     *
     * @return void
     */
    public function testWriteLine()
    {
        $log = new Logger($this->_path);

        $line = 'This is a line 01';
        $log->writeLine($line);
        $expected = "$line\n";
        $this->assertStringEqualsFile($this->_path, $expected);

        $line .= 'This is a line 02';
        $log->writeLine($line);
        $expected .= "$line\n";
        $this->assertStringEqualsFile($this->_path, $expected);
    }

    /**
     * Test the writeLine function
     *
     * @expectedException InvalidArgumentException
     *
     * @return void
     */
    public function testWriteLineTwo()
    {
        $log = new Logger($this->_path);
        $log->writeLine('');
    }

    /**
     * Test the writeLine function
     *
     * @expectedException InvalidArgumentException
     *
     * @return void
     */
    public function testWriteLineThree()
    {
        $log = new Logger($this->_path);
        $log->writeLine('   ');
    }

    /**
     * Test the writeList function
     *
     * @return void
     */
    public function testWriteList()
    {
        $log = new Logger($this->_path);

        $list = array(
            '1st element',
            '2nd element',
            '3rd element',
            '4th element',
        );

        $expected = '';

        foreach ($list as $l) {
            $expected .= '- ' . $l . "\n";
        }

        $log->writeList($list);

        $this->assertStringEqualsFile($this->_path, $expected);
    }

    /**
     * Setup for a test by determining new path and deleting old one if required
     *
     * @return void
     */
    public function setUp()
    {
        if ($this->_path == false) {
            $this->_path = tempnam(sys_get_temp_dir(), 'Techxplorer');
        } else if (is_file($this->_path)) {
            unlink($this->_path);
            $this->_path = tempnam(sys_get_temp_dir(), 'Techxplorer');
        }
    }

    /**
     * Tidy up after a test by deleting the old log file
     *
     * @return void
     */
    public function tearDown()
    {
        if ($this->_path != false) {
            if (is_file($this->_path)) {
                unlink($this->_path);
                $this->_path = false;
            }
        }
    }
}
