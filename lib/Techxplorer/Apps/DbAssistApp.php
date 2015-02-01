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

use \Techxplorer\Database\PostgreSQL;

use Hackzilla\PasswordGenerator\Generator\ComputerPasswordGenerator;

/**
 * A base class for the DbAssist app
 *
 * @package    Techxplorer
 * @subpackage Apps
 */
class DbAssistApp extends Application
{
    /** @var $application_name the name of the application */
    protected static $application_name = "Techxplorer's Database Assist Script";

    /** @var $application_version the version of the application */
    protected static $application_version = "2.0.0";

    /** @var $configpath the path to where the config files are stored */
    protected $configpath;

    /**
     * Constructor for the class
     *
     * @param string $configpath the path to where the config files are stored
     *
     * @return void
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

        // Define a list of valid actions.
        $valid_actions = array(
            'create' => 'Create a database and matching user',
            'empty'  => 'Empty a database',
            'delete' => 'Delete a database and matching user',
            'list'   => 'List all databases and users',
        );

        // Parse and validate the command line options.
        $this->parseOptions();
        $this->printHelpScreen($valid_actions, 'list');
        $this->validateOption('action');

        if (!$this->isValidAction($this->options['action'], $valid_actions)) {
            $this->printError("Invalid action '{$this->options['action']}' detected.");
        }

        // Load the configuration settings.
        // Use YAML because that's what all the cool kids are using these days.
        try {
            $this->loadConfigFile($this->configpath . '/db-assist.yaml');
        } catch (\Techxplorer\Utils\FileNotFoundException $e) {
            $this->printError("Unable to locate configuration file at:\n" . $this->configpath . '/db-assist.yaml');
            exit(1);
        }  catch (\Noodlehaus\Exception\ParseException $e) {
            $this->printError("Unable to load configuration file:\n{$e->getMessage()}\n");
            exit(1);
        }

        // Validate the settings.
        $settings = array('host', 'user', 'database', 'password');

        if (is_array($this->validateConfig($settings))) {
            $this->printErrror(
                "The following required settings were not found in the config file.\n" .
                implode(",", $this->validateConfig($settings))
            );
            exit(1);
        }

        // Connect to the database
        $postgres = new PostgreSQL(
            $this->config['host'],
            $this->config['user'],
            $this->config['password'],
            $this->config['database']
        );

        if (!$postgres->connect()) {
            $this->printError('Unable to connect to the database.');
            exit(1);
        }

        // Undertake the appropriate action.
        switch($this->options['action']) {
            case 'list':
                $databases = $postgres->getDatabaseList();
                $user_list = $postgres->getUserList($databases);

                $table = new \cli\Table();
                $table->setHeaders(array('Databases', 'User List'));
                $table->setRows($user_list);
                $table->display();
                break;
            case 'create':
                $this->validateOption('database');
                $this->validateOption('user', $this->options['database']);

                $generator = new ComputerPasswordGenerator();

                $generator
                    ->setOptionValue(ComputerPasswordGenerator::OPTION_UPPER_CASE, true)
                    ->setOptionValue(ComputerPasswordGenerator::OPTION_LOWER_CASE, true)
                    ->setOptionValue(ComputerPasswordGenerator::OPTION_NUMBERS, true)
                    ->setOptionValue(ComputerPasswordGenerator::OPTION_SYMBOLS, false);

                $password = $generator->generatePassword();

                $this->validateOption('password', $password);

                $this->printInfo('Creating user and database with the following options:');

                $table = new \cli\Table();
                $table->setHeaders(array('Option', 'Value'));
                $table->setRows(
                    array(
                        array('Username', $this->options['user']),
                        array('Password', $this->options['password']),
                        array('Database', $this->options['database'])
                    )
                );
                $table->display();

                if ($postgres->userExists($this->options['user'])) {
                    $this->printError("The {$this->options['user']} user already exists in the system.");
                    exit(1);
                }

                if ($postgres->databaseExists($this->options['database'])) {
                    $this->printError("The {$this->options['database']} database already exists in the system.");
                    exit(1);
                }

                if (!$postgres->createUser($this->options['user'], $this->options['password'])) {
                    $this->printError("Unable to create the {$this->options['user']} user in the system.");
                    exit(1);
                }

                if (!$postgres->createDatabase($this->options['database'], $this->options['user'])) {
                    $this->printError("Unable to create the {$this->options['database']} database in the system.");
                    exit(1);
                }

                $this->printSuccess('User and corresponding database created.');
                break;
            case 'delete':
                $this->validateOption('database');
                $this->validateOption('user', $this->options['database']);

                $this->printInfo('Deleting user and database with the following options:');

                $table = new \cli\Table();
                $table->setHeaders(array('Option', 'Value'));
                $table->setRows(
                    array(
                        array('Username', $this->options['user']),
                        array('Database', $this->options['database'])
                    )
                );
                $table->display();

                if (!$postgres->userExists($this->options['user'])) {
                    $this->printError("The {$this->options['user']} user does not exist in the system.");
                    exit(1);
                }

                if (!$postgres->databaseExists($this->options['database'])) {
                    $this->printError("The {$this->options['database']} database does not exist in the system.");
                    exit(1);
                }

                if (!$postgres->dropDatabase($this->options['database'])) {
                    $this->printError("Unable to delete the {$this->options['database']} database from the system.");
                    exit(1);
                }

                if (!$postgres->dropUser($this->options['user'])) {
                    $this->printError("Unable to delete the {$this->options['user']} user from the system.");
                    exit(1);
                }
                $this->printSuccess('User and corresponding database deleted.');
                break;
            case 'empty':
                $this->validateOption('database');
                $this->validateOption('user', $this->options['database']);

                $this->printInfo('Emptying database with the following options:');

                $table = new \cli\Table();
                $table->setHeaders(array('Option', 'Value'));
                $table->setRows(
                    array(
                        array('Username', $this->options['user']),
                        array('Database', $this->options['database'])
                    )
                );
                $table->display();

                if (!$postgres->userExists($this->options['user'])) {
                    $this->printError("The {$this->options['user']} user does not exist in the system.");
                    exit(1);
                }

                if (!$postgres->databaseExists($this->options['database'])) {
                    $this->printError("The {$this->options['database']} database does not exist in the system.");
                    exit(1);
                }

                if (!$postgres->dropDatabase($this->options['database'])) {
                    $this->printError("Unable to delete the {$this->options['database']} database from the system.");
                    exit(1);
                }

                if (!$postgres->createDatabase($this->options['database'], $this->options['user'])) {
                    $this->printError("Unable to create the {$this->options['database']} database in the system.");
                    exit(1);
                }

                $this->printSuccess('Database deleted and re-created.');
                break;
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
            array('user', 'u'),
            array(
                'default' => '',
                'description' => 'The PostgreSQL user to use'
            )
        );

        $this->options->addOption(
            array('database', 'd'),
            array(
                'default' => '',
                'description' => 'The name of the database to use'
            )
        );

        $this->options->addOption(
            array('password', 'p'),
            array(
                'default' => '',
                'description' => 'The password to use'
            )
        );

        $this->options->addOption(
            array('action', 'a'),
            array(
                'default' => '',
                'description' => 'The name of the action to undertake'
            )
        );

        $this->options->addFlag(array('help', 'h'), 'Show this help screen');
        $this->options->addFlag(array('list', 'l'), 'List available actions');

        // Parse the Options.
        $this->options->parse();
    }
}
