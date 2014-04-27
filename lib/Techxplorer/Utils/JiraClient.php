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
 * @category TechxplorerUtils
 * @package  TechxplorerUtils
 * @author   techxplorer <corey@techxplorer.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     https://github.com/techxplorer/techxplorer-utils
 */

namespace Techxplorer\Utils;
use Techxplorer\Utils\Files as Files;
use InvalidArgumentException;
use BadMethodCallException;
use Jira_Api;
use Jira_Issues_Walker;
use Jira_Api_Authentication_Basic;

/**
 * A convenience class to provide a bridge between my code
 * and the JIRA API classes from chobie/jira-api-restclient
 *
 * @category TechxplorerUtils
 * @package  TechxplorerUtils
 * @author   techxplorer <corey@techxplorer.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     https://github.com/techxplorer/techxplorer-utils
 */
class JiraClient
{
    /**
     * define the default configuration file
     */
    const CONFIG_FILE = 'jira-list-issues.json';

    /**
     * store the connection details
     */
    private $_jira_details = false;

    /**
     * load the connection and authentication details
     *
     * @param string $path to the data directory
     *
     * @return boolean true on successful load of configuration details
     *
     * @throws \Techxplorer\Utils\FileNotFoundException
     * @throws \Techxplorer\Utils\ConfigParseException 
     */
    public function loadConfig($path)
    {
        // check on the parameters
        if ($path == null || trim($path) == '') {
            throw new InvalidArgumentException('The $path parameter is required');
        }
        
        $this->_jira_details = Files::loadConfig($path . '/' . self::CONFIG_FILE);

        // check to ensure all necessary config is available
        $expected_keys = array('url', 'user', 'password');

        $missing = false;

        foreach ($expected_keys as $key) {
            if (!array_key_exists($key, $this->_jira_details)) {
                $missing = true;
            }
        }

        if ($missing) {
            return false;
        }

        return true;
    }

    /**
     * get a connection to the jira server
     *
     * @param string $project the JIRA project name
     * @param string $version the JIRA project version name
     *
     * @return mixed array list of issues, or false on failure
     *
     * @throws InvalidArgumentException
     * @throws BadMethodCallException if not called after loadConfig
     *
     */
    public function getIssues($project, $version)
    {
        // check the parameters
        if (trim($project) === '') {
            throw new InvalidArgumentException(
                'The project parameter is required'
            );
        }

        if (trim($version === '')) {
            throw new InvalidArgumentException(
                'The version parameter is required'
            );
        }

        // make sure we have details
        if (!$this->_jira_details) {
            throw new BadMethodCallException(
                'getIssues must be called after loadConfig'
            );
        }

        // search jira
        $api = new Jira_Api(
            $this->_jira_details['url'],
            new Jira_Api_Authentication_Basic(
                $this->_jira_details['user'],
                $this->_jira_details['password']
            )
        );

        // walker object responsible for the search
        $walker = new Jira_Issues_Walker($api);

        // do the search
        $walker->push("project = {$project} AND fixVersion = \"{$version}\"");

        $issues = array();

        foreach ($walker as $step) {
            $issue = array();

            $issue['key'] = $step->getKey();
            $issue['summary'] = $step->getSummary();
            $issue['status'] = $step->getStatus()['name'];

            $issues[] = $issue;
        }

        return $issues;
    }
}
