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

use Techxplorer\Utils\JiraClient as JiraClient;

/**
 * Test the JiraClient class
 *
 * @category TechxplorerUtils-Tests
 * @package  TechxplorerUtils-Tests
 * @author   techxplorer <corey@techxplorer.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU Public License v3.0
 * @link     https://github.com/techxplorer/techxplorer-utils
 */
class JiraClientTest extends PHPUnit_Framework_TestCase
{
    /**
     * Test the loadConfig function
     *
     * @return void
     */
    public function testLoadConfigOne()
    {
        global $CFG;

        $jira = new JiraClient();

        $this->assertTrue($jira->loadConfig($CFG->data_root));
    }

    /**
     * Test the loadConfig function
     *
     * @expectedException InvalidArgumentException
     * @return void
     */
    public function testLoadConfigTwo()
    {
        $jira = new JiraClient();

        $jira->loadConfig('');
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
            '\Techxplorer\Utils\FileNotFoundException',
            sprintf(
                'The file "%s" does not exist',
                $CFG->data_root . '/not-here/jira-list-issues.json'
            ) 
        );
        
        $jira = new JiraClient($CFG->data_root);

        $jira->loadConfig($CFG->data_root . '/not-here');
    }

    /**
     * Test the loadConfig function
     *
     * @return void
     */
    public function testLoadConfigFour()
    {
        global $CFG;

        $this->setExpectedException(
            '\Techxplorer\Utils\ConfigParseException',
            sprintf(
                'The config file "%s" could not be parsed',
                $CFG->data_root . '/jira-four/jira-list-issues.json.dist'
            )
        );

        $jira = new JiraClient($CFG->data_root);

        $jira->loadConfig($CFG->data_root . '/jira-four');
    }

    /**
     * Test the loadConfig function
     *
     * @return void
     */
    public function testLoadConfigFive()
    {
         global $CFG;

         $jira = new JiraClient();

         $this->assertFalse(
             $jira->loadConfig(
                 $CFG->data_root . '/jira-five'
             )
         );
    }
}
