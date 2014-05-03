#!/usr/bin/env php
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
 * This is a PHP script which lists Behat features in a Moodle installation
 *
 * PHP Version 5.4 
 *
 * @category TechxplorerUtils
 * @package  TechxplorerUtils
 * @author   techxplorer <corey@techxplorer.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     https://github.com/techxplorer/techxplorer-utils
 */

// adjust error reporting to aid in development
error_reporting(E_ALL);

// include the required libraries
require __DIR__ . '/vendor/autoload.php';
use \Techxplorer\Utils\System as System;
use Symfony\Component\Yaml\Yaml;
use Behat\Gherkin\Keywords\ArrayKeywords;
use Behat\Gherkin\Lexer;
use Behat\Gherkin\Parser;
use Behat\Gherkin\Node\OutlineNode;
use Behat\Gherkin\Node\ScenarioNode;

/**
 * Main driving class of the script
 *
 * @category TechxplorerUtils
 * @package  TechxplorerUtils
 * @author   techxplorer <corey@techxplorer.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     https://github.com/techxplorer/techxplorer-utils
 *
 */
class ListMdlBehatFeatures
{
    /**
     * defines a name for the script
     */
    const SCRIPT_NAME = "Techxplorer's List Moodle Behat Features";

    /**
     * defines the version of the script
     */
    const SCRIPT_VERSION = 'v1.0.2';

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
     * @return void
     */
    public function doTask()
    {
        // output some helper text
        \cli\out(self::SCRIPT_NAME . ' - ' . self::SCRIPT_VERSION . "\n");
        \cli\out('License: ' . self::LICENSE_URI . "\n\n");

        // improve handling of arguments
        $arguments = new \cli\Arguments($_SERVER['argv']);

        $arguments->addOption(
            array('input', 'i'),
            array(
                'default' => '',
                'description' => 'Set the path to the Behat YAML file'
            )
        );

        $arguments->addOption(
            array('exclude', 'e'),
            array(
                'default' => 'javascript',
                'description' => 'Exclude scenarios with this tag'
            )
        );

        $arguments->addFlag(array('all', 'a'), 'Include all scenarios');
        $arguments->addFlag(array('verbose', 'v'), 'Output extra information');
        $arguments->addFlag(array('help', 'h'), 'Show this help screen');

        // parse the arguments
        $arguments->parse();

        // show the help screen if required
        if ($arguments['help']) {
            \cli\out($arguments->getHelpScreen() . "\n\n");
            \cli\out("\n\n");
            die(0);
        }

        if (!$arguments['input']) {
            \cli\err("%rERROR: %wMissing required argument --input\n");
            \cli\err($arguments->getHelpScreen());
            \cli\err("\n");
            die(1);
        } else {
            $input_path = $arguments['input'];
            $input_path = realpath($input_path);

            if (!is_file($input_path)) {
                \cli\err("%rERROR: %wUnable to locate input file\n");
                die(1);
            }
        }

        if (!$arguments['exclude'] && !$arguments['all']) {
            \cli\err(
                "%yWARNING: %wExcluding scenarios with the 'javascript' tag\n"
            );
            $exclude_tag = 'javascript';
        } elseif ($arguments['exclude'] && !$arguments['all']) {
            $exclude_tag = trim($arguments['exclude']);
            if ($exclude_tag != '') {
                \cli\err(
                    "%yWARNING: %wExcluding scenarios with the '" .
                    $exclude_tag .
                    "' tag\n"
                );
            } else {
                \cli\err("%rERROR: %wInvalid --exclude argument value\n");
                die(1);
            }
        } elseif (!$arguments['exclude'] && $arguments['all']) {
            \cli\err("%yWARNING: %wIncluding all scenarios\n");
            $exclude_tag = null;
        } else {
            \cli\err(
                "%rERROR: %wYou can't use the --exclude and " . 
                "--all arguments at the same time\n"
            );
            die(1);
        }

        $verbose = false;

        if ($arguments['verbose']) {
            $verbose = true;
        }

        // parse the input file
        try {
            $behat_yaml = Yaml::parse($input_path);
        } catch (ParseException $e) {
            \cli\err("%rERROR: %wUnable to parse input file\n");
            die(1);
        }

        // get the list of feature dirs
        if (empty($behat_yaml['default'])) {
            \cli\err("%rERROR: %wUnable to locate 'default' data element\n");
            die(1);
        }

        if (empty($behat_yaml['default']['extensions'])) {
            \cli\err(
                "%rERROR: %wUnable to locate 'default->extensions' data element\n"
            );
            die(1);
        }

        // find the right array element
        $feature_dirs = null;

        if (!empty($behat_yaml['default'])) {
            $feature_dirs = $behat_yaml['default'];
        } else {
            \cli\err(
                "%rERROR: %w Unable to locate the 'default' YAML element\n"
            );
            die(1);
        }

        if (!empty($feature_dirs['extensions'])) {
            $feature_dirs = $feature_dirs['extensions'];
        } else {
            \cli\err(
                "%rERROR: %wUnable to locate the 'extensions' YAML element\n"
            );
            die(1);
        }

        $mdl_ext = 'Moodle\BehatExtension\Extension';

        if (!empty($feature_dirs[$mdl_ext])) {
            $feature_dirs = $feature_dirs[$mdl_ext];
        } else {
            \cli\err(
                "%rERROR: %wUnable to locate the '$mdl_ext' YAML element\n"
            );
            die(1);
        }

        if (!empty($feature_dirs['features'])) {
            $feature_dirs = $feature_dirs['features'];
        } else {
            \cli\err(
                "%rERROR: %wUnable to locate the 'features' YAML element\n"
            );
            die(1);
        }

        $keywords = new ArrayKeywords(
            array(
                'en' => array(
                    'feature'          => 'Feature',
                    'background'       => 'Background',
                    'scenario'         => 'Scenario',
                    'scenario_outline' => 'Scenario Outline|Scenario Template',
                    'examples'         => 'Examples|Scenarios',
                    'given'            => 'Given',
                    'when'             => 'When',
                    'then'             => 'Then',
                    'and'              => 'And',
                    'but'              => 'But',
                ),
            )
        );

        $lexer  = new Lexer($keywords);
        $parser = new Parser($lexer);

        $data = array();
        $scenario_count = 0;

        // process the list of feature dirs
        foreach ($feature_dirs as $feature_dir) {
            // check to see the directory is valid
            if (!is_dir($feature_dir)) {
                \cli\err("%yWARNING: Unable to find directory:\n$feature_dir\n");
                continue;
            }

            // get a list of feature files
            $feature_dir_list = new DirectoryIterator($feature_dir);

            // process each feature file
            foreach ($feature_dir_list as $feature_file) {
                if ($feature_file->isDot()) {
                    continue;
                }

                $feature_file->getExtension();

                if ($feature_file->getExtension() != 'feature') {
                    continue;
                }

                try {
                    $feature = $parser->parse(
                        file_get_contents($feature_file->getPathname())
                    );
                } catch (Behat\Gherkin\Exception\ParserException $e) {
                    \cli\err(
                        "%yWARNING: %wUnable to parse file:\n" . 
                        $feature_file->getPathname() . "\n"
                    );
                }

                // filter out features that are just JavaScript scenarios
                $elems = array();
                $scenarios = $feature->getScenarios();

                foreach ($scenarios as $s) {
                    if ($exclude_tag != null && $s->hasTag($exclude_tag)) {
                        continue;
                    } else {
                        // deal with scenario outlines differently
                        if ($s instanceof OutlineNode) {
                            for ($i = 1; $i <= count($s->getExamples()); $i++) {
                                $elems[] = $s->getTitle() . " ($i)";
                            }
                        } elseif ($s instanceof ScenarioNode) {
                            $elems[] = $s->getTitle();
                        } else {
                            \cli\err(
                                "%yWARNING: %wUnrecognised scenario class '" .
                                get_class($s) .
                                "'\n"
                            );
                            continue;
                        }
                    }
                }

                if (count($elems) != 0) {
                    if ($verbose) {
                        $tmp = array();
                        $tmp['File:'] = array($feature_file->getPathname());
                        $tmp['Scenarios:'] = $elems;
                        $elems = $tmp;
                    }

                    $data['Feature: ' . $feature->getTitle()]
                        = array('Scenarios:' => $elems);
                    $scenario_count += count($elems);
                }
            }
        }

        // output the list of features
        \cli\out("List of found features:\n");
        $tree = new \cli\Tree;
        $tree->setData($data);
        $tree->setRenderer(new \cli\tree\Markdown(2));
        $tree->display();
        \cli\out("\n");
        \cli\out("Matching features: " . count($data) . "\n");
        \cli\out("Matching scenarios: {$scenario_count}\n");
    }

}

// Make sure the script is run only on the CLI
if (!System::isOnCLI()) {
     die("This script can only be run on the CLI on Mac OS X");
} else {
    $app = new ListMdlBehatFeatures();
    $app->doTask();
}
