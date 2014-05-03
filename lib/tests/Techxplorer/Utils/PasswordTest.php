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

use Techxplorer\Utils\Password as Password;

/**
 * Test the Password class
 *
 * @category TechxplorerUtils-Tests
 * @package  TechxplorerUtils-Tests
 * @author   techxplorer <corey@techxplorer.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License 
 * @link     https://github.com/techxplorer/techxplorer-utils
 */
class PasswordTests extends PHPUnit_Framework_TestCase
{
    /**
     * Test the generate function
     *
     * @return void
     */
    public function testGenerateOne() {

        // prepare some test data
        $lower_case_set = str_split(Password::LOWER_CASE_SET);
        $upper_case_set = str_split(Password::UPPER_CASE_SET);
        $digits_set     = str_split(Password::DIGITS_SET);
        $symbols_set    = str_split(Password::SYMBOLS_SET);

        // test default call
        $password = Password::generate();

        $this->assertTrue(strlen($password) == 8);

        $this->assertTrue(
            $this->hasNeedle(
                $lower_case_set, $password
            )
        );

        $this->assertTrue(
            $this->hasNeedle(
                $upper_case_set, $password
            )
        );

        $this->assertTrue(
            $this->hasNeedle(
                $digits_set, $password
            )
        );

        $this->assertTrue(
            $this->hasNeedle(
                $symbols_set, $password
            )
        );

        // set restricted character set call
        $password = Password::generate(8, false, 'l');

        $this->assertTrue(strlen($password) == 8);

        $this->assertTrue(
            $this->hasNeedle(
                $lower_case_set, $password
            )
        );

        $this->assertTrue(
            !$this->hasNeedle(
                $upper_case_set, $password
            )
        );

        $this->assertTrue(
            !$this->hasNeedle(
                $digits_set, $password
            )
        );

        $this->assertTrue(
            !$this->hasNeedle(
                $symbols_set, $password
            )
        );

        $password = Password::generate(8, false, 'lu');

        $this->assertTrue(strlen($password) == 8);

        $this->assertTrue(
            $this->hasNeedle(
                $lower_case_set, $password
            )
        );

        $this->assertTrue(
            $this->hasNeedle(
                $upper_case_set, $password
            )
        );

        $this->assertTrue(
            !$this->hasNeedle(
                $digits_set, $password
            )
        );

        $this->assertTrue(
            !$this->hasNeedle(
                $symbols_set, $password
            )
        );

        $password = Password::generate(8, false, 'lud');

        $this->assertTrue(strlen($password) == 8);

        $this->assertTrue(
            $this->hasNeedle(
                $lower_case_set, $password
            )
        );

        $this->assertTrue(
            $this->hasNeedle(
                $upper_case_set, $password
            )
        );

        $this->assertTrue(
            $this->hasNeedle(
                $digits_set, $password
            )
        );

        $this->assertTrue(
            !$this->hasNeedle(
                $symbols_set, $password
            )
        );

        $password = Password::generate(8, false, 'luds');

        $this->assertTrue(strlen($password) == 8);

        $this->assertTrue(
            $this->hasNeedle(
                $lower_case_set, $password
            )
        );

        $this->assertTrue(
            $this->hasNeedle(
                $upper_case_set, $password
            )
        );

        $this->assertTrue(
            $this->hasNeedle(
                $digits_set, $password
            )
        );

        $this->assertTrue(
            $this->hasNeedle(
                $symbols_set, $password
            )
        );

        // test different length
        $password = Password::generate(16);

        $this->assertTrue(strlen($password) == 16);

        // test with dashes
        $password = Password::generate(8, true);

        $this->assertTrue(strlen($password) == (8 + floor(sqrt(8)) +1));

        $this->assertTrue(strpos($password, '-') !== false);

        $this->assertTrue(
            $this->hasNeedle(
                $lower_case_set, $password
            )
        );

        $this->assertTrue(
            $this->hasNeedle(
                $upper_case_set, $password
            )
        );

        $this->assertTrue(
            $this->hasNeedle(
                $digits_set, $password
            )
        );

        $this->assertTrue(
            $this->hasNeedle(
                $symbols_set, $password
            )
        );
    }

    /**
     * test the generate function
     *
     * @return void
     *
     * @expectedException InvalidArgumentException
     */
    public function testGenerateTwo() {
        Password::generate('df');
    }

    /**
     * test the generate function
     *
     * @return void
     *
     * @expectedException InvalidArgumentException
     */
    public function testGenerateThree() {
        Password::generate(-1);
    }

    /**
     * test the generate function
     *
     * @return void
     *
     * @expectedException InvalidArgumentException
     */
    public function testGenerateFour() {
        Password::generate(8, '7');
    }

    /**
     * test the generate function
     *
     * @return void
     *
     * @expectedException InvalidArgumentException
     */
    public function testGenerateFive() {
        Password::generate(8, false, 'a');
    }

    /**
     * test the generate function
     *
     * @return void
     *
     * @expectedException InvalidArgumentException
     */
    public function testGenerateSix() {
        Password::generate(8, false, 'ludsa');
    }

    /**
     * check to see if a string contains any element from an array
     *
     * @param array  $needles  a list of needles to search for
     * @param string $haystack the string to search in
     *
     * @return bool true if one of the needles is found, false if not 
     */
    private function hasNeedle($needles, $haystack) {
        foreach ($needles as $needle) {
            if (strpos($haystack, $needle) !== false) {
                return true;
            }
        }
        return false;
    }
}
