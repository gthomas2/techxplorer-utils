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

namespace Techxplorer\Apps;

use \Techxplorer\Utils\FileNotFoundException;

use \Noodlehaus\config;

use \InvalidArgumentException;
use \RuntimeException;

/**
 * A base class providing convenience methods for all scripts
 *
 * @package    Techxplorer
 * @subpackage Apps
 */
abstract class Application
{

    /** @var string $application_name the name of the application. */
    protected static $application_name;

    /** @var string $application_version the version of the application. */
    protected static $application_version;

    /** @var string $application_license the URI for more information about the license. */
    protected $application_license = 'http://www.gnu.org/copyleft/gpl.html';

    /** @var bool $testing indicate that the class is undergoing testing */
    protected $testing = false;

    /** @var object $options the list of parsed command line options */
    protected $options;

    /** @var object $config the list of config variables from the config file */
    protected $config;

    /**
     * Construct a new Application object
     *
     * @throws RuntimeException if the required properties are not implemented by the child class
     */
    protected function __construct()
    {
        if (empty(static::$application_name)) {
            throw new RuntimeException(get_called_class() . ' must define an $application_name.');
        }

        if (empty(static::$application_version)) {
            throw new RuntimeException(get_called_class() . ' must define an $application_version.');
        }
    }

    /**
     * Main entry point to the application
     */
    abstract public function doTask();

    /**
     * Parse the command line options
     *
     * @return void
     */
    abstract protected function parseOptions();

    /**
     * Show the help screen if required
     *
     * @param array  $actions  indicates if a check to print the list of actions is required
     * @param string $argument the option that indicates that actions should be listed
     */
    protected function printHelpScreen($actions = null, $option = null)
    {
        // Print a help screen.
        if ($this->options['help']) {
            \cli\out($this->options->getHelpScreen());
            \cli\out("\n\n");
            die(0);
        }

        if (!empty($option)) {
            if (!empty($this->options[$option])) {
                // Print the list of actions if required.
                if (is_array($actions) && count($actions) > 0) {
                    $this->printActionList($actions);
                    die(0);
                }
            }
        }
    }

    /**
     * Validate an option and if necessary use a default value
     *
     * @param string $option  the name of the required option
     * @param string $default the default option if it isn't found
     *
     * @return bool true if valid, false if it isn't
     *
     * @throws InvalidArgumentException if one of the options is invalid
     */
    protected function validateOption($option, $default = null)
    {
        if (empty(trim($option))) {
            throw new InvalidArgumentException('The $option argument is required.');
        }

        $option = trim($option);

        if (empty($this->options[$option])) {
            if (empty($default)) {
                $this->printError("Missing required option --{$option}");
                exit(1);
            } else {
                $this->printWarning("Missing option --{$option} using the default value:\n  {$default}");
                $this->options[$option] = $default;
            }
        }
    }

    /**
     * Convenience function to print application header
     *
     * @return void
     */
    protected function printHeader()
    {
        $message = static::$application_name . ' - ' . static::$application_version . "\n";
        $message .= 'License: ' . $this->application_license . "\n\n";

        if ($this->testing) {
            return $message;
        } else {
            \cli\out($message);
        }
    }

    /**
     * Convenience function to print info text
     *
     * @param string $message the message to print
     *
     * @throws InvalidArgumentException if the message is empty
     *
     * @return void
     */
    protected function printInfo($message)
    {
        if (empty(trim($message))) {
            throw new InvalidArgumentException('The $message argument is required.');
        }

        $message = 'INFO: ' . trim($message) . "\n";

        if ($this->testing) {
            return $message;
        } else {
            \cli\out($message);
        }
    }

    /**
     * Convenience function to print warning text
     *
     * @param string $message the message to print
     *
     * @throws InvalidArgumentException if the messasge is empty
     *
     * @return void
     */
    protected function printWarning($message)
    {
        if (empty(trim($message))) {
            throw new InvalidArgumentException('The $message argument is required.');
        }

        $message = '%yWARNING:%w ' . trim($message) . "\n";

        if ($this->testing) {
            return $message;
        } else {
            \cli\out($message);
        }
    }

    /**
     * Convenience function to print error text
     *
     * @param string $message the message to print
     *
     * @throws InvalidArgumentException if the message is empty
     *
     * @return void
     */
    protected function printError($message)
    {
        if (empty(trim($message))) {
            throw new InvalidArgumentException('The $message argument is required.');
        }

        $message = '%rERROR:%w ' . trim($message) . "\n";

        if ($this->testing) {
            return $message;
        } else {
            \cli\err($message);
        }
    }

    /**
     * Convenience function to print success text
     *
     * @param string $message the messae to print
     *
     * @throws InvalidArgumentException if the message is empty
     *
     * @return void
     */
    protected function printSuccess($message)
    {
        if (empty(trim($message))) {
            throw new InvalidArgumentException('The $message argument is required.');
        }

        $message = '%gSUCCESS:%w ' . trim($message) . "\n";

        if ($this->testing) {
            return $message;
        } else {
            \cli\out($message);
        }
    }

    /**
     * Convenience class to validate an action
     *
     * @param string $action  the specified action
     * @param array  $actions the list of possible actions
     *
     * @return bool true if the action is valid, false if it is not
     *
     * @throws InvalidArgumentException if the options are invalid
     */
    protected function isValidAction($action, $actions)
    {
        if (empty(trim($action))) {
            throw new InvalidArgumentException('The $action argument is required.');
        }

        if (!is_array($actions) || count($actions) == 0) {
            throw new InvalidArgumentException('The $actions argument must be an array with at least one element.');
        }

        return array_key_exists($action, $actions);
    }

    /**
     * Print a list of valid actions
     *
     * @param array $action the list of possible actions
     *
     * @return void
     *
     * @throws InvalidArgumentException if the options are invalid
     */
    protected function printActionList($actions)
    {
        if (!is_array($actions) || count($actions) == 0) {
            throw new InvalidArgumentException('The $actions argument must be an array with at least one element.');
        }

        $list = array();

        foreach ($actions as $key => $value) {
            $list[] = array($key, $value);
        }

        \cli\out("List of available actions:\n");
        $table = new \cli\Table();
        $table->setHeaders(array('Action', 'Description'));
        $table->setRows($list);
        $table->display();
    }

    /**
     * Load a configuration file
     *
     * @param string $path the path to the configuration file
     *
     * @throws InvalidArgumentException if the path is invalid
     * @throws \Techxplorer\Utils\FileNotFound if the path cannot be found
     * @throws \Noodlehaus\Exception\ParseException if the file cannot be parsed
     */
    protected function loadConfigFile($path)
    {
        if (empty(trim($path))) {
            throw new InvalidArgumentException('The $path argment is required');
        }

        $path = trim($path);

        if (!is_file($path) || !is_readable($path)) {
            throw new FileNotFoundException($path);
        }

        $this->config = new Config($path);

        return true;
    }

    /**
     * Validate a configuration file
     *
     * @param array $settings an array of settings that must be in the config file
     *
     * @return mixed array|bool true if it is valid, an array of missing settings if not
     *
     * @throws InvalidArgumentException if the parameter is invalid
     */
    protected function validateConfig($settings)
    {
        if (!is_array($settings) || count($settings) == 0) {
            throw new InvalidArgumentException('The $settings argument must be an array with at least one element.');
        }

        $missing = array();

        foreach ($settings as $setting) {
            if (empty($this->config[$setting])) {
                $missing[] = $setting;
            }
        }

        if (count($missing) > 0) {
            return $missing;
        } else {
            return true;
        }
    }
}
