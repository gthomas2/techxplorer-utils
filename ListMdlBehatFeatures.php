#!/usr/bin/env php
<?php
/*
 * This file is part of Techxplorer's List Moodle Behat Features.
 *
 * Techxplorer's List Moodle Behat Features is free software: you can redistribute
 * it and/or modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * Techxplorer's List Moodle Behat Features is distributed in the hope
 * that it will be useful, but WITHOUT ANY WARRANTY; without even the
 * implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Techxplorer's Moodle User List Creator.
 * If not, see <http://www.gnu.org/licenses/>
 */

// adjust error reporting to aid in development
error_reporting(E_ALL);

// include the required libraries
require(__DIR__ . '/vendor/autoload.php');
use Symfony\Component\Yaml\Yaml;
use Behat\Gherkin\Keywords\ArrayKeywords;
use Behat\Gherkin\Lexer;
use Behat\Gherkin\Parser;
use Behat\Gherkin\Node\OutlineNode;
use Behat\Gherkin\Node\ScenarioNode;

/**
 * Techxplorer's List Moodle Behat Features a php script which can be used
 * to list some details about Moodle Behat features.
 *
 * @since 1.0
 * @author techxplorer <corey@techxplorer.com>
 * @license http://opensource.org/licenses/GPL-3.0 GNU Public License v3.0
 * @package Techxplorer-Utils
 */

/**
 * main driving class of Techxplorer's List Moodle Behat Features
 *
 * @since 1.0
 * @author techxplorer <corey@techxplorer.com>
 *
 * @copyright 2013 Corey Wallis (techxplorer)
 * @license http://opensource.org/licenses/GPL-3.0
 */
class ListMdlBehatFeatures {

    /**
     * defines a name for the script
     */
    const SCRIPT_NAME = "Techxplorer's List Moodle Behat Features";

    /**
     * defines the version of the script
     */
    const SCRIPT_VERSION = 'v1.0.1';

    /**
     * defines the uri for more information
     */
    const MORE_INFO_URI = 'https://github.com/techxplorer/techxplorer-utils';

    /**
     * defines the license uri
     */
    const LICENSE_URI = 'http://opensource.org/licenses/GPL-3.0';

    /**
     * defin

    /**
     * main driving function
     *
     * @since 1.0
     * @author techxplorer <corey@techxplorer.com>
     */
    public function do_task() {

        // output some helper text
        \cli\out(self::SCRIPT_NAME . ' - ' . self::SCRIPT_VERSION . "\n");
        \cli\out('License: ' . self::LICENSE_URI . "\n\n");

        // improve handling of arguments
        $arguments = new \cli\Arguments($_SERVER['argv']);

        $arguments->addOption(array('input', 'i'),
            array(
                'default' => '',
                'description' => 'Set the path to the Behat YAML file'
            )
        );

        $arguments->addOption(array('exclude', 'e'),
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
        if($arguments['help']) {
            \cli\out($arguments->getHelpScreen() . "\n\n");
            \cli\out("\n\n");
            die(0);
        }

        if(!$arguments['input']) {
            \cli\err("%rERROR: %wMissing required argument --input\n");
            \cli\err($arguments->getHelpScreen());
            \cli\err("\n");
            die(-1);
        } else {
            $input_path = $arguments['input'];
            $input_path = realpath($input_path);

            if(!is_file($input_path)) {
                \cli\err("%rERROR: %wUnable to locate input file\n");
                die(-1);
            }
        }

        if(!$arguments['exclude'] && !$arguments['all']) {
            \cli\err("%yWARNING: %wExcluding scenarios with the 'javascript' tag\n");
            $exclude_tag = 'javascript';
        } else if($arguments['exclude'] && !$arguments['all']) {
            $exclude_tag = trim($arguments['exclude']);
            if($exclude_tag != '') {
                \cli\err("%yWARNING: %wExcluding scenarios with the '{$exclude_tag}' tag\n");
            } else {
                \cli\err("%rERROR: %wInvalid --exclude argument value\n");
                die(-1);
            }
        } else if(!$arguments['exclude'] && $arguments['all']) {
            \cli\err("%yWARNING: %wIncluding all scenarios\n");
            $exclude_tag = null;
        } else {
            \cli\err("%rERROR: %wYou can't use the --exclude and --all arguments at the same time\n");
            die(-1);
        }

        $verbose = false;

        if($arguments['verbose']) {
            $verbose = true;
        }

        // parse the input file
        try {
            $behat_yaml = Yaml::parse($input_path);
        } catch (ParseException $e) {
            \cli\err("%rERROR: %wUnable to parse input file\n");
            die(-1);
        }

        // get the list of feature dirs
        if(empty($behat_yaml['default'])) {
            \cli\err("%rERROR: %wUnable to locate 'default' data element\n");
            die(-1);
        }

        if(empty($behat_yaml['default']['extensions'])) {
            \cli\err("%rERROR: %wUnable to locate 'default->extensions' data element\n");
            die(-1);
        }

        if(empty($behat_yaml['default']['extensions']['Moodle\BehatExtension\Extension'])) {
            \cli\err("%rERROR: %wUnable to locate 'default->extensions->Moodle\BehatExtension\Extension' data element\n");
            die(-1);
        }

        if(empty($behat_yaml['default']['extensions']['Moodle\BehatExtension\Extension']['features'])) {
            \cli\err("%rERROR: %wUnable to locate 'default->extensions->Moodle\BehatExtension\Extension->features' data element\n");
            die(-1);
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

        $feature_dirs = $behat_yaml['default']['extensions']['Moodle\BehatExtension\Extension']['features'];

        $data = array();
        $scenario_count = 0;

        // process the list of feature dirs
        foreach($feature_dirs as $feature_dir) {
            // check to see the directory is valid
            if(!is_dir($feature_dir)) {
                \cli\err("%yWARNING: Unable to find directory:\n$feature_dir\n");
                continue;
            }

            // get a list of feature files
            $feature_dir_list = new DirectoryIterator($feature_dir);

            // process each feature file
            foreach($feature_dir_list as $feature_file) {
        		if($feature_file->isDot()) {
            		continue;
        		}

        		$feature_file->getExtension();

        		if($feature_file->getExtension() != 'feature') {
            		continue;
        		}

                try {
         		   $feature = $parser->parse(file_get_contents($feature_file->getPathname()));
                } catch (Behat\Gherkin\Exception\ParserException $e) {
                    \cli\err("%yWARNING: %wUnable to parse file:\n" . $feature_file->getPathname() . "\n");
                }

                // filter out features that are just JavaScript scenarios
                $elems = array();
                $scenarios = $feature->getScenarios();

                foreach($scenarios as $scenario) {
                    if($exclude_tag != null && $scenario->hasTag($exclude_tag)) {
                        continue;
                    } else {
                        // deal with scenario outlines differently
                        if($scenario instanceof OutlineNode) {
                            for($i = 1; $i <= count($scenario->getExamples()); $i++) {
                                $elems[] = $scenario->getTitle() . " ($i)";
                            }
                        } else if($scenario instanceof ScenarioNode) {
                            $elems[] = $scenario->getTitle();
                        } else {
                            \cli\err("%yWARNING: %wUnrecognised scenario class '" . get_class($scenario) . "'\n");
                            continue;
                        }
                    }
                }

                if(count($elems) != 0) {
                    if($verbose) {
                        $tmp = array();
                        $tmp['File:'] = array($feature_file->getPathname());
                        $tmp['Scenarios:'] = $elems;
                        $elems = $tmp;
                    }

                    $data['Feature: ' . $feature->getTitle()] = array('Scenarios:' => $elems);
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

// make sure script is only run on the cli
if(substr(php_sapi_name(), 0, 3) == 'cli') {
// yes
$app = new ListMdlBehatFeatures();
$app->do_task();
} else {
// no
die("This script can only be run on the cli\n");
}

?>