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
 * @version 2.0
 */

namespace Techxplorer\Apps;

use GitWrapper\GitWrapper;
use GitWrapper\GitBranches;

/**
 * A base class for the JiraGitBridge  app
 *
 * @package    Techxplorer
 * @subpackage Apps
 */
class JiraGitBridge extends Application
{
    /** @var $application_name the name of the application */
    protected static $application_name = "Techxplorer's Jira to Git Bridge Script";

    /** @var $application_version the version of the application */
    protected static $application_version = "2.0.0";

    /** @var $configpath the path to where the config files are stored */
    protected $configpath;

    /**
     * Construct a new JiraGitBridge  object
     *
     * @param string $configpath the path to where the config files are stored
     */
    public function __construct($configpath)
    {
        $this->configpath = $configpath;
        parent::__construct();
    }

    /**
     * Many entry point for the application
     *
     * @return void
     */
    public function doTask()
    {
        // Output the header information.
        $this->printHeader();

        // Parse and validate the command line options.
        $this->parseOptions();
        $this->printHelpScreen();
        $this->validateOption('repository', getcwd());
        $this->validateOption('project');
        $this->validateOption('version');

        // Load the JIRA access settings.
        // Use YAML because that's what al the cool kids are using these days
        try {
            $this->loadConfigFile($this->configpath . '/jira-config.yaml');
        } catch (\Techxplorer\Utils\FileNotFoundException $e) {
            $this->printError("Unable to locate configuration file at:\n" . $this->configpath . '/jira-config.yaml');
            exit(1);
        }  catch (\Noodlehaus\Exception\ParseException $e) {
            $this->printError("Unable to load configuration file:\n{$e->getMessage()}\n");
            exit(1);
        }

        // Validate the settings.
        $settings = array('url', 'user', 'password');

        if (is_array($this->validateConfig($settings))) {
            $this->printErrror(
                "The following required settings were not found in the config file.\n" .
                implode(",", $this->validateConfig($settings))
            );
            exit(1);
        }

        $this->options['project'] = trim($this->options['project']);
        $this->options['version'] = trim($this->options['version']);

        // Output some status text.
        $output  = "Searching JIRA for:\n";
        $output .= "  Project: {$this->options['project']}\n";
        $output .= "  Version: {$this->options['version']}";
        $this->printInfo($output);

        // Search JIRA for the issues in this version.
        $client = new \chobie\Jira\Api(
            $this->config['url'],
            new \chobie\Jira\Api\Authentication\Basic(
                $this->config['user'],
                $this->config['password']
            )
        );

        $this->printInfo('Gathering list of issues from JIRA...');

        $walker = new \chobie\Jira\Issues\Walker($client);
        $walker->push("project = {$this->options['project']} AND fixVersion = \"{$this->options['version']}\"");

        $issues = array();
        $keys   = array();

        foreach ($walker as $jira_issue) {
            $issue = array();

            $issue['key']     = $jira_issue->getKey();
            $issue['summary'] = $jira_issue->getSummary();
            $issue['status']  = $jira_issue->getStatus()['name'];

            $keys[] = $issue['key'];

            $issues[] = $issue;
        }

        if (empty($issues)) {
            $this->printError('No issues matching the criteria could be found in JIRA');
            exit(1);
        }

        // Build a pattern to search the git log for.
        $pattern = implode('\|', $keys);

        // Get a GitWrapper object and associated with the repository path.
        $wrapper = new GitWrapper();
        $git = $wrapper->workingCopy($this->options['repository']);
        $branches = new GitBranches($git);
        $branch = trim($branches->head());

        $this->printInfo("Searching for commits in the '{$branch}' branch...");

        try {
            $git_log = $git->log(
                array(
                    'grep' => $pattern,
                    'oneline' => true
                )
            );
        } catch (\GitWrapper\GitException $e) {
            $this->printError('Unable to build list of commits.');
            exit(1);
        }

        // Process the list of log entries.
        $git_output = $git_log->getOutput();

        if (empty($git_output)) {
            $this->printError('No matching commits found.');
        }

        $git_log_entries = explode("\n", $git_output);
        $commits = array();
        $codes   = array();
        $merges  = 0;

        foreach ($git_log_entries as $log_entry) {
            $log_entry = trim($log_entry);

            if (empty($log_entry)) {
                continue;
            }

            $tmp = explode(' ', $log_entry);

            $hash = $tmp[0];
            $code = trim($tmp[1], ':');
            $codes[] = $code;

            unset($tmp[0]);
            unset($tmp[1]);

            $commits[] = array($hash, $code, implode(' ', $tmp));
        }

        // Get and process a list of only the merge commits.
        try {
            $git_log = $git->log(
                array(
                    'grep' => $pattern,
                    'oneline' => true,
                    'merges' => true
                )
            );
        } catch (\GitWrapper\GitException $e) {
            $this->printError('Unable to build list of merge commits.');
            exit(1);
        }

        $git_output = $git_log->getOutput();

        if (!empty($git_output)) {
            $git_log_entries = explode("\n", $git_output);

            foreach ($git_log_entries as $log_entry) {
                $log_entry = trim($log_entry);

                if (empty($log_entry)) {
                    continue;
                }

                $tmp = explode(' ', $log_entry);

                $code = trim($tmp[1], ':');

                $idx = 0;

                foreach ($commits as $commit) {
                    if ($commit[1] == $code) {
                        // Update JIRA code entry.
                        $commit[1] = $code . ' (merge)';

                        // Replace array entry with this new one.
                        $commits[$idx] = $commit;
                        $merges++;
                    }

                    $idx++;
                }
            }
        }

        // Output a list of commits with codes matching the JIRA items.
        $table = new \cli\Table();
        $table->setHeaders(array('Git Hash', 'JIRA Code', 'Description'));
        $table->setRows($commits);
        $table->display();

        $this->printInfo('Apply commits in reverse order (bottom to top)');

        $output =  "Commits matching JIRA issues found. Statistics:\n";
        $output .= "  Commits found: " . count($commits) . "\n";
        $output .= "  Merge commits: {$merges}";

        $this->printSuccess($output);

        $codes = array_unique($codes);

        if (count($codes) != count($issues)) {
            $this->printWarning(
                "The count of JIRA items with commits '" . count($codes) . "' " .
                "does not match the total count of items '" . count($issues) . "'"
            );
        }
    }

    /**
     * Parse the list of command line options
     *
     * @return void
     */
    protected function parseOptions()
    {
        $this->options = new \cli\Arguments($_SERVER['argv']);


        $this->options->addOption(
            array('repository', 'r'),
            array(
                'default' => 'The current working directory',
                'description' => 'The path to the git repository'
            )
        );

        $this->options->addOption(
            array('project', 'p'),
            array(
                'default' => '',
                'description' => 'The project in JIRA that is of interest'
            )
        );

        $this->options->addOption(
            array('version', 'v'),
            array(
                'default' => '',
                'description' => 'The version associated with the project in JIRA'
            )
        );

        $this->options->addFlag(
            array('help', 'h'),
            'Show this help screen'
        );

        // Parse the Options.
        $this->options->parse();
    }
}
