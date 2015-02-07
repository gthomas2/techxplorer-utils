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
 * @version 1.0
 */

namespace Techxplorer;

use \Techxplorer\Moodle\Moodle;
use \PHPUnit_Framework_TestCase;

/**
 * Test the Files class
 */
class TestMoodle extends \PHPUnit_Framework_TestCase
{
    /**
     * Test the loadLangStrings function
     */
    public function testLoadLangStrings() {
        global $CFG;

        $moodle = new Moodle();

        $strings = $moodle->loadLangStrings($CFG->data_root . 'moodle.php');

        $this->assertNotEmpty($strings);

        $this->assertCount(1793, $strings);
    }

    /**
     * Test the loadLangStrings function
     *
     * @expectedException \InvalidArgumentException
     */
    public function testLoadLangStringsZero() {
        $moodle = new Moodle();

        $strings = $moodle->loadLangStrings('  ');
    }

    /**
     * Test the loadLangStrings function
     *
     * @expectedException \Techxplorer\Utils\FileNotFoundException
     */
    public function testLoadLangStringsOne() {
        global $CFG;

        $moodle = new Moodle();

        $strings = $moodle->loadLangStrings($CFG->data_root . 'not-here.php');
    }

}
