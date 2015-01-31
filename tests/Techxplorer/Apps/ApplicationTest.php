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

namespace Techxplorer;

use \PHPUnit_Framework_TestCase;
use \ReflectionProperty;
use \ReflectionMethod;

/**
 * Test the abstract application class
 */
class TestApplication extends \PHPUnit_Framework_TestCase
{
    /**
     * Test the various convenience functions provided by the application class
     *
     * @return void
     */
    public function testConvenienceFunctions()
    {
        // Build a test stub object.
        $methods = get_class_methods('Techxplorer\Apps\Application');
        $stub = $this->getMockBuilder('Techxplorer\Apps\Application')
            ->disableOriginalConstructor()
            ->setMethods($methods)
            ->getMockForAbstractClass();

        // Use reflection to set the protected testing attributes.
        $attribute = new ReflectionProperty('Techxplorer\Apps\Application', 'testing');
        $attribute->setAccessible(true);
        $attribute->setValue($stub, true);

        $attribute = new ReflectionProperty('Techxplorer\Apps\Application', 'application_name');
        $attribute->setAccessible(true);
        $attribute->setValue($stub, 'Test Application');

        $attribute = new ReflectionProperty('Techxplorer\Apps\Application', 'application_version');
        $attribute->setAccessible(true);
        $attribute->setValue($stub, '2.0');

        // Use reflection to get at the protected methods.
        $method = new ReflectionMethod('Techxplorer\Apps\Application', 'printWarning');
        $method->setAccessible(true);

        $expected = "%yWARNING:%w Danger!\n";

        $this->assertEquals($expected, $method->invoke($stub, 'Danger!'));

        $method = new ReflectionMethod('Techxplorer\Apps\Application', 'printError');
        $method->setAccessible(true);

        $expected = "%rERROR:%w Kaboom!\n";

        $this->assertEquals($expected, $method->invoke($stub, 'Kaboom!'));

        $method = new ReflectionMethod('Techxplorer\Apps\Application', 'printSuccess');
        $method->setAccessible(true);

        $expected = "%gSUCCESS:%w Woo Hoo!\n";

        $this->assertEquals($expected, $method->invoke($stub, 'Woo Hoo!'));

        $method = new ReflectionMethod('Techxplorer\Apps\Application', 'printInfo');
        $method->setAccessible(true);

        $expected = "INFO: It's snowing in california.\n";

        $this->assertEquals($expected, $method->invoke($stub, "It's snowing in california."));

        $method = new ReflectionMethod('Techxplorer\Apps\Application', 'printHeader');
        $method->setAccessible(true);

        $expected = "Test Application - 2.0\nLicense: http://www.gnu.org/copyleft/gpl.html\n\n";

        $this->assertEquals($expected, $method->invoke($stub));

        $method = new ReflectionMethod('Techxplorer\Apps\Application', 'isValidAction');
        $method->setAccessible(true);

        $actions = array(
             'create' => 'Create a database and matching user',
             'empty'  => 'Empty a database',
             'delete' => 'Delete a database and matching user',
             'list'   => 'List all databases and users',
        );

        $this->assertTrue($method->invoke($stub, 'list', $actions));
        $this->assertFalse($method->invoke($stub, 'notfound', $actions));

    }

    /**
     * Test the locadConfigFile function
     */
    public function testLoadConfigFileZero()
    {
        // Build a test stub object.
        $methods = get_class_methods('Techxplorer\Apps\Application');
        $stub = $this->getMockBuilder('Techxplorer\Apps\Application')
            ->disableOriginalConstructor()
            ->setMethods($methods)
            ->getMockForAbstractClass();

        $method = new ReflectionMethod('Techxplorer\Apps\Application', 'loadConfigFile');
        $method->setAccessible(true);

        global $CFG;
        $this->assertTrue($method->invoke($stub, $CFG->data_root . 'db-assist.yaml'));

        return $stub;
    }

    /**
     * Test the loadConfigFile exceptions
     *
     * @expectedException \Techxplorer\Utils\FileNotFoundException
     */
    public function testLoadConfigFileOne()
    {
        // Build a test stub object.
        $methods = get_class_methods('Techxplorer\Apps\Application');
        $stub = $this->getMockBuilder('Techxplorer\Apps\Application')
            ->disableOriginalConstructor()
            ->setMethods($methods)
            ->getMockForAbstractClass();

        $method = new ReflectionMethod('Techxplorer\Apps\Application', 'loadConfigFile');
        $method->setAccessible(true);

        global $CFG;
        $method->invoke($stub, $CFG->data_root . 'missing.yaml');
    }

    /**
     * Test the loadConfigFile exceptions
     *
     * @expectedException \Noodlehaus\Exception\ParseException
     */
    public function testLoadConfigFileTwo()
    {
        // Build a test stub object.
        $methods = get_class_methods('Techxplorer\Apps\Application');
        $stub = $this->getMockBuilder('Techxplorer\Apps\Application')
            ->disableOriginalConstructor()
            ->setMethods($methods)
            ->getMockForAbstractClass();

        $method = new ReflectionMethod('Techxplorer\Apps\Application', 'loadConfigFile');
        $method->setAccessible(true);

        global $CFG;
        $method->invoke($stub, $CFG->data_root . 'bad-config.yaml');
    }

    /**
     * Test the validateConfig
     *
     * @depends testLoadConfigFileZero
     * @return void
     */
    public function testValidateConfig($stub)
    {
        $method = new ReflectionMethod('Techxplorer\Apps\Application', 'validateConfig');
        $method->setAccessible(true);

        $settings = array('host', 'user', 'database', 'password');
        $this->assertTrue($method->invoke($stub, $settings));

        $settings = array('notfound');
        $this->assertEquals($settings, $method->invoke($stub, $settings));
    }
}
