#!/usr/bin/env php
<?php
/*
 * This file is part of Techxplorer's Jira Git Bridge script.
 *
 * Techxplorer's Jira Git Bridge script is free software: you can redistribute it
 * and/or modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * Techxplorer's Jira Git Bridge script is distributed in the hope that it will
 * be useful, but WITHOUT ANY WARRANTY; without even the implied
 * warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Techxplorer's Jira Git Bridge script.
 * If not, see <http://www.gnu.org/licenses/>
 */

// adjust error reporting to aid in development
error_reporting(E_ALL);

// include the required libraries
require(__DIR__ . '/vendor/autoload.php');
require(__DIR__ . '/lib/JiraClient.php');


/**
 * a php script which can be used to createa bridge between
 * a Jira project and a Git repository
 *
 * @since 1.0
 * @author techxplorer <corey@techxplorer.com>
 * @license http://opensource.org/licenses/GPL-3.0 GNU Public License v3.0
 * @package Techxplorer-Utils
 */

/**
 * main driving class of Techxplorer's Jira Git Bridge script
 *
 * @since 1.0
 * @author techxplorer <corey@techxplorer.com>
 *
 * @copyright 2014 Corey Wallis (techxplorer)
 * @license http://opensource.org/licenses/GPL-3.0
 */
class JiraGitBridge {

	/**
	 * defines a name for the script
	 */
	const SCRIPT_NAME = "Techxplorer's Jira Git Bridge script";

	/**
	 * defines the version of the script
	 */
	const SCRIPT_VERSION = 'v1.0.0';

	/**
	 * defines the uri for more information
	 */
	const MORE_INFO_URI = 'https://github.com/techxplorer/techxplorer-utils';

	/**
	 * defines the license uri
	 */
	const LICENSE_URI = 'http://opensource.org/licenses/GPL-3.0';

	/**
	 * main driving function
	 *
	 * @since 1.0
	 * @author techxplorer <corey@techxplorer.com?
	 */
    public function do_task() {

        // output some helper text
		\cli\out(self::SCRIPT_NAME . ' - ' . self::SCRIPT_VERSION . "\n");
		\cli\out('License: ' . self::LICENSE_URI . "\n\n");

		// define a list of valid actions
		$valid_actions = array(
			'find-commits' => 'List the commits in a JIRA project and version',
		);

		// improve handling of arguments
		$arguments = new \cli\Arguments($_SERVER['argv']);

		$arguments->addOption(array('action', 'a'),
		    array(
		        'default' => 'list-commits',
		        'description' => 'The action to undertake'
            )
        );

		$arguments->addOption(array('project', 'p'),
			array(
				'default' => '',
				'description' => 'The JIRA project of interest'
			)
		);

		$arguments->addOption(array('version', 'v'),
			array(
				'default' => '',
				'description' => 'The JIRA version of interest'
			)
		);

		$arguments->addOption(array('repository', 'r'),
			array(
				'default' => '',
				'description' => 'The path to the git repository'
			)
		);

		$arguments->addFlag(array('help', 'h'), 'Show this help screen');

		// parse the arguments
		$arguments->parse();

		// show the help screen if required
		if($arguments['help']) {
			\cli\out($arguments->getHelpScreen());
			\cli\out("\n");

			// output the table
			\cli\out("List of available actions:\n");
			$table = new \cli\Table();
			$table->setHeaders(array('Action', 'Description'));
			$table->setRows($actions);
			$table->display();
			die(0);
		}

		// check the arguments
		$action = 'list-commits';

		if(!$arguments['action']) {
			\cli\out("%yWARNING: %wUsing default action 'list-commits'\n\n");
		} else {
    		$action = trim($arguments['action']);

    		if(!array_key_exists($action, $valid_actions)) {
    			\cli\out("%rERROR: %wInvalid action detected.\n Use the -h flag to see a list of valid actions.\n\n");
    		 	die(-1);
    		}
		}

		if(!$arguments['project']) {
			\cli\err("%rERROR: %wMissing required argument --project\n");
			\cli\err($arguments->getHelpScreen());
			\cli\err("\n");
		 	die(-1);
		}

		if(!$arguments['version']) {
			\cli\err("%rERROR: %wMissing required argument --version\n");
			\cli\err($arguments->getHelpScreen());
			\cli\err("\n");
		 	die(-1);
		}

		if(!$arguments['repository']) {
            \cli\err("%rERROR: %wMissing required argument --repository\n");
            \cli\err($arguments->getHelpScreen());
            \cli\err("\n");
            die(-1);
        } else {
            $repo_path = $arguments['repository'];
            $repo_path = realpath($repo_path);

            if(!is_dir($repo_path)) {
                \cli\err("%rERROR: %wUnable to locate Git repository\n");
                die(-1);
            }
        }

        //determine the path to the helper applications
        $git_path = trim(shell_exec('which git'));

        if($git_path == null || $git_path == '') {
            \cli\err("%rERROR: %wUnable to locate git executable\n");
            die(-1);
        }

        $grep_path = trim(shell_exec('which grep'));

        if($grep_path == null || $grep_path == '') {
            \cli\err("%rERROR: %wUnable to location grep executable\n");
            die(-1);
        }

		$jira_project = trim($arguments['project']);
		$jira_version = trim($arguments['version']);

		// output some helper text
		\cli\out("Searching for project: {$jira_project}\n");
		\cli\out("Searching for version: {$jira_version}\n");
		\cli\out("Using this git: {$git_path}\n");
		\cli\out("Using this grep: {$grep_path}\n");

		// get JIRA helper class
		$jira_client = new JiraClient();

		if(!$jira_client->load_auth_info()) {
    		\cli\err("%rERROR: %wUnable to load JIRA authentication details.\n");
    		\cli\err("\n");
		 	die(-1);
		}

        // get a list of issues
		$issues = $jira_client->get_issues($arguments['project'], $arguments['version']);

		if($issues === false || count($issues) == 0) {
    		\cli\out("%yWARNING: %wNo issues found.\n");
    		die(0);
		}

		// build a list of keys
		$keys = array();

		foreach($issues as $issue) {
    		$keys[] = $issue['key'];
		}

		// build the pattern
		$pattern = implode('\|', $keys);

		// change to the repo path
		if(!chdir($repo_path)) {
    		\cli\err("%rERROR: %wUnable to change to repository directory\n");
            die(-1);
		}

		// get a list of all of the commits
		$command = "{$git_path} log --oneline | {$grep_path} '{$pattern}'";
		$result = trim(shell_exec($command));

		if($result == null || $result == ''){
    		\cli\err("%rERROR: %wUnable to execute git command:\n");
    		\cli\err($command . "\n");
            die(-1);
		}

		// process the results
		$results = explode("\n", $result);
        $commits = array();
        $merges = 0;

        foreach($results as $commit) {
            $tmp = explode(' ', $commit);
            $commits[] = array($tmp[0], trim($tmp[1], ':'));
        }

        // get a list of just merges
        $command = "{$git_path} log --oneline --merges | {$grep_path} '{$pattern}'";
		$result = trim(shell_exec($command));

		if($result === null){ // we could legitimately have no output
    		\cli\err("%rERROR: %wUnable to execute git command:\n");
    		\cli\err($command . "\n");
            die(-1);
		}

		// process the results
		$results = explode("\n", $result);

        foreach($results as $result) {
            $tmp = explode(' ', $result);

            $code = trim($tmp[1], ':');

            $idx = 0;

            foreach($commits as $commit) {
                if($commit[1] == $code) {

                    // update JIRA code entry
                    $commit[1] = $code . ' (merge)';

                    // replace array entry with this one
                    $commits[$idx] = $commit;

                    $merges++;
                }

                $idx++;
            }
        }

        // output a list of commits
		$table = new \cli\Table();
		$table->setHeaders(array('Hash', 'JIRA Code'));
		$table->setRows($commits);
		$table->display();

		\cli\out("Commits found: " . count($commits) . "\n");
		\cli\out("Merge commits: {$merges}\n");
		\cli\out("Apply commits in reverse order (bottom to top)\n");
    }
}

// make sure script is only run on the cli
if(substr(php_sapi_name(), 0, 3) == 'cli') {
	// yes
	$app = new JiraGitBridge();
	$app->do_task();
} else {
	// no
	die("This script can only be run on the cli\n");
}

?>