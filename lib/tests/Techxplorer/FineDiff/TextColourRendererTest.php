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

use Techxplorer\FineDiff\TextColourRenderer;

use \cogpowered\FineDiff\Diff;
use \cogpowered\FineDiff\Granularity\Word;

/**
 * Test the TextColourRenderer class
 *
 * @category TechxplorerUtils-Tests
 * @package  TechxplorerUtils-Tests
 * @author   techxplorer <corey@techxplorer.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU Public License v3.0
 * @link     https://github.com/techxplorer/techxplorer-utils
 */
class TextColourRendererTests extends PHPUnit_Framework_TestCase
{
    /**
     * Test the TextColourRenderer class
     *
     * @return void
     */
    public function testRenderer()
    {
        $granularity = new Word;
        $renderer = new TextColourRenderer;
        $differ = new Diff($granularity, $renderer);

        $from_string = 'Replace Me with something else';
        $to_string   = 'with something else';
        $diff_string = '%rReplace Me %wwith something else';

        $this->assertEquals(
            $diff_string,
            $differ->render(
                $from_string,
                $to_string
            )
        );

        $from_string = 'Replace Me with something else';
        $to_string   = 'with something else and add me';
        $diff_string = '%rReplace Me %wwith something %relse%w%gelse and add me%w';

        $this->assertEquals(
            $diff_string,
            $differ->render(
                $from_string,
                $to_string
            )
        );

        $from_string = 'Do not Replace Me with something else';
        $to_string   = 'Do not Replace Me with something else and add me';
        $diff_string
            = 'Do not Replace Me with something %relse%w%gelse and add me%w';

        $this->assertEquals(
            $diff_string,
            $differ->render(
                $from_string,
                $to_string
            )
        );

        $from_string = 'Do not Replace Me with something else';
        $to_string   = 'Do not Replace me with something else and add me';
        $diff_string
            = 'Do not Replace %rMe %w%gme %wwith' .
              ' something %relse%w%gelse and add me%w';

        $this->assertEquals(
            $diff_string,
            $differ->render(
                $from_string,
                $to_string
            )
        );
    }
}
