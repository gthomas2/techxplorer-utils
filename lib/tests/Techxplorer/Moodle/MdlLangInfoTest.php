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

use Techxplorer\Moodle\MdlLangInfo;

/**
 * Test the MdlLangInfo class
 *
 * @category TechxplorerUtils-Tests
 * @package  TechxplorerUtils-Tests
 * @author   techxplorer <corey@techxplorer.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU Public License v3.0
 * @link     https://github.com/techxplorer/techxplorer-utils
 */
class MdlLangInfoTests extends PHPUnit_Framework_TestCase
{
    /**
     * Test the MdlLangInfo class
     *
     * @return MdlLangInfo an instantiated object
     */
    public function testMdlLangInfo()
    {
        // Define the test data
        $moodle_path = '/moodle/lang/en';
        $custom_path = '/custom/lang/en';

        // Run the tests
        $object = new MdlLangInfo($moodle_path, $custom_path);
        $this->assertEquals($moodle_path, $object->getMoodlePath());
        $this->assertEquals($custom_path, $object->getCustomPath());

        return $object;
    }

    /**
     * Test the basic string handling functions
     *
     * @param MdlLangInfo $object an instantiated MdLangInfo object
     *
     * @depends testMdlLangInfo
     *
     * @return an instantiated and modified MdlLangInfo object
     */
    public function testStringMethods(MdlLangInfo $object)
    {
        $moodle_strings = array(
            'a' => 'First string',
            'b' => 'Second string',
            'd' => 'Fourth string',
            'c' => 'Third string'
        );

        $custom_strings = array(
            'e' => 'First string',
            'f' => 'Second string',
            'h' => 'Fourth string',
            'g' => 'Third string',
            'a' => 'Zero string',
        );

        $object->setMoodleStrings($moodle_strings);
        $object->setCustomStrings($custom_strings);

        ksort($moodle_strings);
        ksort($custom_strings);

        $this->assertEquals($moodle_strings, $object->getMoodleStrings());
        $this->assertEquals($custom_strings, $object->getCustomStrings());
        $this->assertEquals(count($moodle_strings), $object->getMoodleCount());
        $this->assertEquals(count($custom_strings), $object->getCustomCount());

        return $object;
    }

    /**
     * Test the unused keys function
     *
     * @param MdlLangInfo $object an instantiated MdLangInfo object
     *
     * @depends testStringMethods
     *
     * @return void
     */
    public function testUnusedKeyMethods(MdlLangInfo $object)
    {
        $unused_keys_array = array(
            'e',
            'f',
            'g',
            'h'
        );

        $unused_keys_string = 'e, f, g, h';

        $this->assertEquals($unused_keys_array, $object->getUnusedKeys());
        $this->assertEquals($unused_keys_array, $object->getUnusedKeys(false));
        $this->assertEquals($unused_keys_string, $object->getUnusedKeys(true));
        $this->assertEquals(4, $object->getUnusedCount());

        $new_object = new MdlLangInfo('moodle', 'custom');

        $this->assertEquals(array(), $new_object->getUnusedKeys());
        $this->assertEquals('', $new_object->getUnusedKeys(true));
        $this->assertEquals(0, $new_object->getUnusedCount());
    }

    /**
     * Test the used keys function
     *
     * @param MdlLangInfo $object an instantiated MdLangInfo object
     *
     * @depends testStringMethods
     *
     * @return void
     */
    public function testUsedKeyMethod(MdlLangInfo $object)
    {

        $used_keys_array = array(
            'a',
        );

        $used_keys_string = 'a';

        $this->assertEquals($used_keys_array, $object->getUsedKeys());
        $this->assertEquals($used_keys_array, $object->getUsedKeys(false));
        $this->assertEquals($used_keys_string, $object->getUsedKeys(true));

        $new_object = new MdlLangInfo('moodle', 'custom');

        $this->assertEquals(array(), $new_object->getUsedKeys());
        $this->assertEquals('', $new_object->getUsedKeys(true));
    }

    /**
     * Test the diff related methods
     *
     * @param MdlLangInfo $object an instantiated MdLangInfo object
     *
     * @depends testMdlLangInfo
     *
     * @return void
     */
    public function testDiffMethods(MdlLangInfo $object)
    {
        $moodle_strings = array(
            'a' => 'First string',
            'b' => 'Second string',
            'd' => 'Fourth string',
            'c' => 'Third string'
        );

        $object->setDiffs($moodle_strings);

        $this->assertEquals($moodle_strings, $object->getDiffs());
        $this->assertEquals(4, $object->getDiffCount());

        $new_object = new MdlLangInfo('moodle', 'custom');
        $this->assertEquals(array(), $new_object->getDiffs());
        $this->assertEquals(0, $new_object->getDiffCount());
    }

    /**
     * Test the stat related methods
     *
     * @param MdlLangInfo $object an instantiated MdLangInfo object
     *
     * @depends testMdlLangInfo
     *
     * @return void
     */
    public function testStatMethods(MdlLangInfo $object)
    {
        $moodle_strings = array(
            'a' => 'First string',
            'b' => 'Second string',
            'd' => 'Fourth string',
            'c' => 'Third string'
        );

        $object->setStats($moodle_strings);

        $this->assertEquals($moodle_strings, $object->getStats());
        $this->assertEquals(4, $object->getStatsCount());

        $new_object = new MdlLangInfo('moodle', 'custom');
        $this->assertEquals(array(), $new_object->getStats());
        $this->assertEquals(0, $new_object->getStatsCount());
    }
}
