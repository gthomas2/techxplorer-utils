<?php
/*
 * This file is part of Techxplorer's Utility Scripts
 *
 * Techxplorer's Utility Scripts is free software: you can redistribute it
 * and/or modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * Techxplorer's Utility Scripts is distributed in the hope that it will
 * be useful, but WITHOUT ANY WARRANTY; without even the implied
 * warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Techxplorer's Moodle User List Creator.
 * If not, see <http://www.gnu.org/licenses/>
 */


/**
 * A convenience class to provide the bridge between my code
 * and the JIRA API classes
 *
 * @since 1.0
 * @author techxplorer <corey@techxplorer.com>
 *
 * @copyright 2013 Corey Wallis (techxplorer)
 * @license http://opensource.org/licenses/GPL-3.0
 */
class JiraClient {

	/**
	 * define the default configuration file
	 */
	const DEFAULT_CFG_FILE = '/../data/jira-list-issues.json.dist';

	/**
	 * define the configuration override file
	 */
	const OVERRIDE_CFG_FILE = '/../data/jira-list-issues.json';

	/**
	 * store the connection details
	 */
    private $jira_details = false;

    /**
     * the JIRA api object
     */
    private $jira_client = false;

    /**
     * load the connection and authentication details
     *
     * @return mixed an array of connection related details, or false on failure
     *
     * @since 1.0
     * @author techxplorer <corey@techxplorer.com>
     */
	public function load_auth_info() {

		$details = false;

		// start with the override file
		if(is_readable(__DIR__ . self::OVERRIDE_CFG_FILE)) {
			$details = json_decode(file_get_contents(__DIR__ . self::OVERRIDE_CFG_FILE), true);
		}

		// try the default role file
		if($details == false) {
			// try the default file
			if(is_readable(__DIR__ . self::DEFAULT_CFG_FILE)) {
				$details = json_decode(file_get_contents(__DIR__ . self::DEFAULT_CFG_FILE), true);
			}
		}

        if($details == null || $details == false) {
            return false;
        } else {
    		$this->jira_details = $details;
    		return true;
        }
	}

	/**
	 * get a connection to the jira server
	 *
	 * @return mixed array list of issues, or false on failure
	 *
     * @since 1.0
     * @author techxplorer <corey@techxplorer.com>
     */
    public function get_issues($project, $version) {

        // check the parameters
        if(trim($project) === '') {
            throw new InvalidArgumentException('The project parameter is required');
        }

        if(trim($version === '')) {
            throw new InvalidArgumentException('The version parameter is required');
        }

        // make sure we have details
        if(!$this->jira_details) {
            return false;
        }

        // search jira
        $api = new Jira_Api(
           $this->jira_details['url'],
            new Jira_Api_Authentication_Basic(
                $this->jira_details['user'],
                $this->jira_details['password']
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
