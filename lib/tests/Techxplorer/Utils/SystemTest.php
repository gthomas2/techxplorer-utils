<?php
/**
 * This file is part of Techxplorer's Util script library.
 *
 * Techxplorer's Util script library is free software: you can redistribute it
 * and/or modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * Techxplorer's Util script library is distributed in the hope that it will
 * be useful, but WITHOUT ANY WARRANTY; without even the implied
 * warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Techxplorer's Util script library.
 * If not, see <http://www.gnu.org/licenses/>
 *
 * This is a PHP script which can be used to create a RAM disk on Mac OS X
 *
 * PHP version 5
 *
 * @category TechxplorerUtils-Tests
 * @package  TechxplorerUtils-Tests
 * @author   techxplorer <corey@techxplorer.com>
 * @license  http://opensource.org/licenses/GPL-3.0 GNU Public License v3.0
 * @link     https://github.com/techxplorer/techxplorer-utils
 */

use Techxplorer\Utils\System as System;

/**
 * Test the SystemUtils class
 *
 * @category TechxplorerUtils-Tests
 * @package  TechxplorerUtils-Tests
 * @author   techxplorer <corey@techxplorer.com>
 * @license  http://opensource.org/licenses/GPL-3.0 GNU Public License v3.0
 * @link     https://github.com/techxplorer/techxplorer-utils
 */
class SystemTests extends PHPUnit_Framework_TestCase
{

    /**
     * Test the isMacOSX function
     *
     * @return void
     */
    public function testIsMacOSX() {
        $this->assertTrue(System::isMacOSX());
    }

    /**
     * Test the isOnCLI function
     *
     * @return void
     */
    public function testIsOnCLI() {
        $this->assertTrue(System::isOnCLI());
    }
}

