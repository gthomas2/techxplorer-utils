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
 * Test the Files class
 *
 * @category TechxplorerUtils-Tests
 * @package  TechxplorerUtils-Tests
 * @author   techxplorer <corey@techxplorer.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU Public License v3.0
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

    /**
     * Test the convertSize function
     *
     * @return void
     */
    public function testConvertSizeOne()
    {
        // basic sizes in the denominations supported
        $this->assertEquals(1024, Files::convertSize('1KB'));
        $this->assertEquals(1048576, Files::convertSize('1MB'));
        $this->assertEquals(1073741824, Files::convertSize('1GB'));

        // more than one unit
        $this->assertEquals(1048576 * 10, Files::convertSize('10MB'));

        // a fractional unit
        $this->assertEquals(1048576 * 5.5, Files::convertSize('5.5MB'));

        // lower case
        $this->assertEquals(1024, Files::convertSize('1kb'));
    }

    /**
     * Test the convertSize function
     *
     * @expectedException InvalidArgumentException
     *
     * @return void
     */
    public function testConvertSizeTwo()
    {
        Files::convertSize('');
    }

    /** 
     * Test the convertSize function
     *
     * @expectedException InvalidArgumentException
     *
     * @return void
     */
    public function testConvertSizeThree()
    {
        Files::convertSize('foo');
    }

    /** 
     * Test the convertSize function
     *
     * @expectedException InvalidArgumentException
     *
     * @return void
     */
    public function testConvertSizeFour()
    {
        Files::convertSize('1TB');
    } 

    /**
     * Specify the default number of expected files
     */
    const DEFAULT_FILE_COUNT = 9;

    /**
     * Specify the default number of expected files when
     * a filter is applied
     */
    const DEFAULT_FILTER_FILE_COUNT = 2;

    /**
     * Test the findFiles function when the parent directory isn't available
     *
     * @expectedException \Techxplorer\Utils\FileNotFoundException
     *
     * @return void
     */
    public function testFindFiles()
    {
        // test the case where the path cannot be read
        Files::findFiles('../directory-not-here/');
    }

    /**
     * Test the findFiles function without a filter
     *
     * @return void
     */
    public function testfindFilesTwo()
    {
        global $CFG;

        // test that the file list matches what is expected with no filter
        $this->assertCount(
            self::DEFAULT_FILE_COUNT,
            Files::findFiles($CFG->data_root)
        );
        $this->assertCount(
            self::DEFAULT_FILE_COUNT,
            Files::findFiles($CFG->data_root . '/')
        );
    }

    /**
     * Test the findFiles function with a filter
     *
     * @return void
     */
    public function testFindFilesThree()
    {
        global $CFG;

        // test that the file list matches what is expected with a filter
        $this->assertCount(
            self::DEFAULT_FILTER_FILE_COUNT,
            Files::findFiles($CFG->data_root, '.json')
        );
        $this->assertCount(
            self::DEFAULT_FILTER_FILE_COUNT,
            Files::findFiles($CFG->data_root . '/', '.json')
        );
    }

    /**
     * Test the parameter checking code of the findFiles function
     *
     * @expectedException InvalidArgumentException
     *
     * @return void
     */
    public function testFindFilesFour()
    {
        Files::findFiles(null);
    }

    /**
     * Test the parameter checking code of the findFiles function
     *
     * @expectedException InvalidArgumentException
     *
     * @return void
     */
    public function testFindFilesFive()
    {
        Files::findFiles(null, '.md');
    }

    /**
     * Test the parameter checking code of the findFiles function
     *
     * @expectedException InvalidArgumentException
     *
     * @return void
     */
    public function testFindFilesSix()
    {
         Files::findFiles('', '.md');
    }

    /**
     * Test the filterPathsByExt function
     *
     * @return void
     */
    public function testFilterPathsByExt()
    {
        $paths = array(
            '/path/to/a/php/file.php',
            '/path/to/a/css/file.css',
            'path/to/a/javascript/file.js',
            '/path/to/a/bin/file.bin',
        );

        $expected = array(
            '/path/to/a/php/file.php',
            '/path/to/a/css/file.css',
            'path/to/a/javascript/file.js',
        );

        $exts = array('php', 'css', 'js');

        $this->assertEquals($expected, Files::filterPathsByExt($paths, $exts));

        $expected = array(
            '/path/to/a/php/file.php',
            '/path/to/a/css/file.css',
        );

        $exts = array('php', 'css');

        $this->assertEquals($expected, Files::filterPathsByExt($paths, $exts));

        $expected = array(
            '/path/to/a/php/file.php',
        );

        $exts = array('php');

        $this->assertEquals($expected, Files::filterPathsByExt($paths, $exts));

        $paths[] = '/another/path/to/a/php-file.php';

        $expected = array(
            '/path/to/a/php/file.php',
            '/another/path/to/a/php-file.php'
        );

        $exts = array('php');

        $this->assertEquals($expected, Files::filterPathsByExt($paths, $exts));
    }

    /**
     * Test the filterPathsByExt function
     *
     * @expectedException \InvalidArgumentException
     *
     * @return void
     */
    public function testFilterPathsByExtTwo()
    {
        Files::filterPathsByExt('123', '456');
    }

    /**
     * Test the filterPathsByExt function
     *
     * @expectedException \InvalidArgumentException
     *
     * @return void
     */
    public function testFilterPathsByExtThree()
    {
        Files::filterPathsByExt(array(), '456');
    }

    /**
     * Test the filterPathsByExt function
     *
     * @expectedException \InvalidArgumentException
     *
     * @return void
     */
    public function testFilterPathsByExtFour()
    {
        Files::filterPathsByExt(array(), array());
    }

    /**
     * Test the filterPathsByExt function
     *
     * @expectedException \InvalidArgumentException
     *
     * @return void
     */
    public function testFilterPathsByExtFive()
    {
        $paths = array(
            '/path/to/a/php/file.php',
            '/path/to/a/css/file.css',
            'path/to/a/javascript/file.js',
            '/path/to/a/bin/file.bin',
        );

        Files::filterPathsByExt($paths, array());
    }

    /**
     * Test the filterPathsByExt function
     *
     * @expectedException \InvalidArgumentException
     *
     * @return void
     */
    public function testFilterPathsByExtSix()
    {
        $paths = array(
            '/path/to/a/php/file.php',
            '/path/to/a/css/file.css',
            'path/to/a/javascript/file.js',
            '/path/to/a/bin/file.bin',
        );

        Files::filterPathsByExt(array(), $paths);
    }
}
