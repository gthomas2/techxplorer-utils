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

use Techxplorer\Utils\Files as Files;

/**
 * Test the File class
 *
 * @category TechxplorerUtils-Tests
 * @package  TechxplorerUtils-Tests
 * @author   techxplorer <corey@techxplorer.com>
 * @license  http://opensource.org/licenses/GPL-3.0 GNU Public License v3.0
 * @link     https://github.com/techxplorer/techxplorer-utils
 */
class FilesTests extends PHPUnit_Framework_TestCase
{
    /**
     * Test the humanReadableSize function
     *
     * @return void
     */
    public function testHumanReadableSizeOne()
    {
        $size = 1024;
        $this->assertEquals('1.00kB', Files::humanReadableSize($size));
        $this->assertEquals('1kB', Files::humanReadableSize($size, 0));

        $size = 1024 * 1024;
        $this->assertEquals('1MB', Files::humanReadableSize($size, 0));

        $size = 1024 * 1024 * 1024;
        $this->assertEquals('1GB', Files::humanReadableSize($size, 0));
        $this->assertEquals('1.00GB', Files::humanReadableSize($size));

        $size = $size + ($size / 2);
        $this->assertEquals('1.50GB', Files::humanReadableSize($size));
    }

    /**
     * Test the humanReadableSize function exceptions
     *
     * @expectedException InvalidArgumentException
     *
     * @return void
     */
    public function testHumanReadableSizeTwo()
    {
        Files::humanReadableSize('abc');
    }

    /**
     * Test the humanReadableSize function exceptions
     *
     * @expectedException InvalidArgumentException
     *
     * @return void
     */
    public function testHumanReadableSizeThree()
    {
        Files::humanReadableSize(1024, 'fgh');
    }

    /**
     * Test the findApp function
     *
     * @return void
     */
    public function testFindAppOne()
    {
        $command = 'date';

        $this->assertEquals('/bin/date', Files::findApp($command));
    }

    /**
     * Test the findApp function
     *
     * @return void
     */
    public function testFindAppTwo()
    {
        $this->setExpectedException(
            '\Techxplorer\Utils\FileNotFoundException',
            sprintf(
                'The file "%s" does not exist',
                'foobar' 
            )
        );

        Files::findApp('foobar');
    }

    /**
     * Test the findApp function
     *
     * @expectedException InvalidArgumentException
     *
     * @return void
     */
    public function testFindAppThree()
    {
        Files::findApp(' ');
    }

    /**
     * Test the loadConfig function
     *
     * @return void
     */
    public function testLoadConfigOne()
    {
        global $CFG;

        $config = array(
            'host'     => 'localhost',
            'user'     => 'techxplorer',
            'database' => 'postgres',
            'password' => '',
        );

        $this->assertEquals(
            $config, 
            Files::loadConfig($CFG->data_root . '/db-assist.json.dist')
        );

        $config = array(
            'size' => '4096',
            'name' => 'ram_disk',
        );

        $this->assertEquals(
            $config, 
            Files::loadConfig($CFG->data_root . '/make-ram-disk.json')
        );

        $config = array(
            'size' => '1024',
            'name' => 'RAM Disk',
        );

        $this->assertEquals(
            $config, 
            Files::loadConfig($CFG->data_root . '/make-ram-disk.json.dist')
        );

        $config = array(
            'url'      => 'https://your-jira-server.lan',
            'user'     => 'your-user',
            'password' => 'your-password',
        );

        $this->assertEquals(
            $config, 
            Files::loadConfig($CFG->data_root . '/jira-list-issues.json')
        );

    }

    /**
     * Test the loadConfig function
     *
     * @return void
     */
    public function testLoadConfigTwo()
    {
        global $CFG;

        $this->setExpectedException(
            '\Techxplorer\Utils\FileNotFoundException',
            sprintf(
                'The file "%s" does not exist',
                $CFG->data_root . '/not-here.json'
            )
        );

        Files::loadConfig($CFG->data_root . '/not-here.json');
    }

    /**
     * Test the loadConfig function
     *
     * @return void
     */
    public function testLoadConfigThree()
    {
        global $CFG;

        $this->setExpectedException(
            '\Techxplorer\Utils\ConfigParseException', 
            sprintf(
                'The config file "%s" could not be parsed',
                $CFG->data_root . '/invalid.json'
            )
        );

        Files::loadConfig($CFG->data_root . '/invalid.json');
    }

    /**
     * Test the loadConfig function
     *
     * @expectedException InvalidArgumentException
     *
     * @return void
     */
    public function testLoadConfigFour()
    {
        global $CFG;

        Files::loadConfig('');
    }
}
