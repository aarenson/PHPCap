<?php

namespace IU\PHPCap;

use PHPUnit\Framework\TestCase;

use IU\PHPCap\RedCapProject;

/**
 * PHPUnit tests for events for the RedCapProject class.
 */
class RedCapProjectEventsTest extends TestCase
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
    
    public function testExportEvents()
    {
        $result = self::$longitudinalDataProject->exportEvents();
        $this->assertEquals(14, count($result), 'Number of results matched.');
        
        $result = self::$longitudinalDataProject->exportEvents($format = 'php', $arms = [1]);
        $this->assertEquals(8, count($result), 'Number of results matchedfor arm 1.');
        
        $result = self::$longitudinalDataProject->exportEvents($format = 'php', $arms = [2]);
        $this->assertEquals(6, count($result), 'Number of results matchedfor arm 2.');
    }

    /**
     * Tests exporting instruments in CSV (Comma-Separated Values) format.
     */
    public function testExportInstrumentsAsCsv()
    {
        $result = self::$longitudinalDataProject->exportEvents($format = 'csv');
        
        ### print $result."\n";
        
        $parser = \KzykHys\CsvParser\CsvParser::fromString($result);
        $csv = $parser->parse();
        
        $firstDataRow = $csv[1];
        
        /**
        $instrumentName  = $firstDataRow[0];
        $instrumentLabel = $firstDataRow[1];

        $this->assertEquals('demographics', $instrumentName, 'Instrument name match.');
        $this->assertEquals('Basic Demography Form', $instrumentLabel, 'Instrument label match.');
        **/
    }
}
