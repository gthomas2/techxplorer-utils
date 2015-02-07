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

use \Techxplorer\Utils\Files;
use \PHPUnit_Framework_TestCase;

/**
 * Test the Files class
 */
class TestFiles extends \PHPUnit_Framework_TestCase
{
    /**
     * Test the isPathValid function
     *
     * @return void
     */
    public function testIsPathValid() {

        $path = Files::isPathValid(__FILE__);
        $this->assertTrue(is_string($path), 'Got a ' . gettype($path) . ' instead of a string');

        $path = Files::isPathValid(__DIR__, FILES::TYPE_DIRECTORY);
        $this->assertTrue(is_string($path), 'Got a ' . gettype($path) . ' instead of a string');
    }

    /**
     * Test the isPathValid function
     *
     * @return void
     *
     * @expectedException \Techxplorer\Utils\FileNotFoundException
     */
    public function testIsPathValidZero() {
        $path = Files::isPathValid(__FILE__ . '-not-here');
    }

    /**
     * Test the isPathValid function
     *
     * @return void
     *
     * @expectedException \Techxplorer\Utils\FileNotFoundException
     */
    public function testIsPathValidOne() {
        $path = Files::isPathValid(__DIR__ . '-not-here');
    }

    /**
     * Test the isPathValid function
     *
     * @return void
     *
     * @expectedException \InvalidArgumentException
     */
    public function testIsPathValidTwo() {
        $path = Files::isPathValid('   ');
    }
}
