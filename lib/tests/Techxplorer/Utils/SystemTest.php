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

use Techxplorer\Utils\System as System;

/**
 * Test the System class
 *
 * @category TechxplorerUtils-Tests
 * @package  TechxplorerUtils-Tests
 * @author   techxplorer <corey@techxplorer.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License 
 * @link     https://github.com/techxplorer/techxplorer-utils
 */
class SystemTest extends PHPUnit_Framework_TestCase
{

    /**
     * Test the isMacOSX function
     *
     * @return void
     */
    public function testIsMacOSX()
    {
        $this->assertTrue(System::isMacOSX());
    }

    /**
     * Test the isOnCLI function
     *
     * @return void
     */
    public function testIsOnCLI()
    {
        $this->assertTrue(System::isOnCLI());
    }
}
