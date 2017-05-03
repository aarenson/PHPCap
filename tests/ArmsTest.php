<?php

namespace IU\PHPCap;

use PHPUnit\Framework\TestCase;

use IU\PHPCap\RedCapProject;
use IU\PHPCap\PhpCapException;

/**
 * PHPUnit tests for the RedCapProject class.
 */
class RedCapProjectTest extends TestCase
{
    private static $config;
    private static $basicDemographyProject;
    private static $longitudinalDataProject;
    
    public static function setUpBeforeClass()
    {
        self::$config = parse_ini_file('config.ini');
        self::$basicDemographyProject = new RedCapProject(
            self::$config['api.url'],
            self::$config['basic.demography.api.token']
        );
        self::$longitudinalDataProject = new RedCapProject(
            self::$config['api.url'],
            self::$config['longitudinal.data.api.token']
        );
    }
    
    public function testExportArms()
    {
        $result = self::$longitudinalDataProject->exportArms();
        
        $this->assertEquals(count($result), 2, 'Number of arms test.');
        
        $this->assertEquals($result[0]['arm_num'], 1);
        $this->assertEquals($result[1]['arm_num'], 2);
        
        $this->assertEquals($result[0]['name'], 'Drug A');
        $this->assertEquals($result[1]['name'], 'Drug B');
    }
}
