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

use \Techxplorer\Utils\Pasteboard;

use \PHPUnit_Framework_TestCase;

/**
 * Test the abstract Pasteboard class
 */
class TestPasteboard extends \PHPUnit_Framework_TestCase
{
    /**
     * Test the Pasteboard class
     *
     * @return var
     */
    public function testPasteboard()
    {
        $pasteboard = new Pasteboard();

        $data = 'The return we reap from generous actions is not always evident. - Francesco Guicciardini';

        $this->assertTrue($pasteboard->copy($data));
        $this->assertEquals($data, $pasteboard->paste());

        $data = <<<'EOD'
If you feel lost, disappointed, hesitant, or weak, return to yourself,
to who you are, here and now and when you get there, you will discover yourself,
like a lotus flower in full bloom, even in a muddy pond, beautiful and strong.
-  Masaru Emoto
EOD;

        $this->assertTrue($pasteboard->copy($data));
        $this->assertEquals($data, $pasteboard->paste());
    }

    /**
     * Test the copy function
     *
     * @return void
     * @expectedException \InvalidArgumentException
     */
    public function testPasteboardZero()
    {
        $pasteboard = new Pasteboard();
        $pasteboard->copy('   ');
    }
}
