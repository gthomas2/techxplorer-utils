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

use \Techxplorer\Utils\Files;
use \Techxplorer\Utils\System;
use \Techxplorer\Utils\FileNotFoundException;

use \Techxplorer\Moodle\Moodle;

/**
 * A base class for the MdlStringSort app
 *
 * @package    Techxplorer
 * @subpackage Apps
 */
class MdlStringSort extends Application
{
    /** @var $application_name the name of the application */
    protected static $application_name = "Techxplorer's Moodle String Sort Script";

    /** @var $application_version the version of the application */
    protected static $application_version = "1.0.0";

    /**
     * Construct a new MdlStringSort object
     */
    public function __construct()
    {
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

        $this->validateOption('input');
        $this->validateOption('output');

        try {
            $this->options['input'] = Files::isPathValid($this->options['input']);
        } catch (FileNotFoundException $e) {
            $this->printError('Unable to find the specified input file.');
            exit(1);
        }

        try {
            Files::isPathValid($this->options['output']);
            $this->printError('The output file already exists.');
            exit(1);
        } catch (FileNotFoundException $e) {
            // do nothing here as the file is supposed to be missing.
        }

        // Load the language strings.
        $moodle = new Moodle();
        $strings = $moodle->loadLangStrings($this->options['input']);

        $stringcount = count($strings);

        if ($stringcount == 0) {
            $this->printError('No language strings were found in the input file.');
            exit(1);
        }

        // Sort the language strings.
        if (!ksort($strings, SORT_NATURAL)) {
            $this->printError('Unable to sort the language strings.');
            exit(1);
        }

        // Output the sorted language strings.
        $fh = fopen($this->options['output'], 'wb');

        if (!$fh) {
            $this->printError('Unable to open the output file.');
            $exit(1);
        }

        $key = current(array_keys($strings));

        $char = substr($key, 0, 1);

        foreach ($strings as $key => $value) {
            $value = addslashes($value);

            $compare = substr($key, 0, 1);

            if ($char != $compare) {
                $char = $compare;
                fwrite($fh, "\n");
            }

            fwrite($fh, "\$string['$key'] = '$value';\n");
        }

        fclose($fh);

        // Test the syntax of the generated file.
        $php = System::findApp('php');

        $command = "$php -l {$this->options['output']}";
        $output = array();
        $return = '';

        exec($command, $output, $return);

        if ($return != 0) {
            $this->printError("The generated file failed a lint check.");
            exit(1);
        }

        $this->printSuccess("$stringcount strings sorted and written to the output file.");
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
            array('input', 'i'),
            array(
                'default' => '',
                'description' => 'The path to the Moodle plugin lang file'
            )
        );

        $this->options->addOption(
            array('output', 'o'),
            array(
                'default' => '',
                'description' => 'The path to the output file'
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
