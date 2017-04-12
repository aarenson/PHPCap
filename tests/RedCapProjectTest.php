<?php

require_once(__DIR__.'/../src/RedCapApiConnection.php');
require_once(__DIR__.'/../src/RedCapProject.php');

use PHPUnit\Framework\TestCase;
use IU\PHPCap\RedCapApiConnection;
use IU\PHPCap\RedCapProject;

class RedCapProjectTest extends TestCase {
    private static $config;
    private static $basicDemographyProject;
    
    public static function setUpBeforeClass()
    {
        self::$config = parse_ini_file('config.ini');
        self::$basicDemographyProject = new RedCapProject(self::$config['api.url'], self::$config['basic.demography.api.token']);
    }
    
    public function testExportProjectInfo()
    {
        $callInfo = true;
        $result = self::$basicDemographyProject->exportProjectInfo($callInfo);
        
        $this->assertEquals($result['project_language'], 'English', 'Project info "project_language" test.');
        $this->assertEquals($result['purpose_other'], 'PHPCap testing', 'Project info "purpose_other" test.');
    }
    
    public function testExportMetadata()
    {
        $result = self::$basicDemographyProject->exportMetadata();
         
        $this->assertArrayHasKey('field_name', $result[0], 'Metadata has field_name field test.');
        $this->assertEquals($result[0]['field_name'], 'record_id', 'Metadata has study_id field test.');
    
        $callInfo = self::$basicDemographyProject->getCallInfo();
     
        $this->assertEquals($callInfo['url'], self::$config['api.url'], 'Metadata url test.');
        $this->assertArrayHasKey('content_type', $callInfo, 'Metadata has content type test.');
        $this->assertArrayHasKey('http_code', $callInfo, 'Metadata has HTTP code test.');
    }
    
    public function testExportRecords()
    {
        $result = self::$basicDemographyProject->exportRecords();
        
        $this->assertEquals(count($result), 100, 'Number of records test.');
        
        $recordIds = array_column($result, 'record_id');
        $this->assertEquals(min($recordIds), 1001, 'Min record_id test.');
        $this->assertEquals(max($recordIds), 1100, 'Max record_id test.');
        
        $lastNames = array_flip(array_column($result, 'last_name'));
        $this->assertArrayHasKey('Braun',  $lastNames, 'Has last name Braun test.');
        $this->assertArrayHasKey('Carter', $lastNames, 'Has last name Carter test.');
        $this->assertArrayHasKey('Hayes',  $lastNames, 'Has last name Hayes test.');
    }
    
    public function testExportRedcapVersion()
    {
        $result = self::$basicDemographyProject->exportRedcapVersion();
        $this->assertRegExp('/^[0-9]+\.[0-9]+\.[0-9]+$/', $result, 'REDCap version format test.');
    }

}
