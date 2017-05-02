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
    
    /**
     * Note: need to have an actual test that creates a project, otherwise the constructor
     * won't show up in code coverage
     */
    public function testProjectCreation()
    {
        self::$config = parse_ini_file('config.ini');
        
        $basicDemographyProject = new RedCapProject(
            self::$config['api.url'],
            self::$config['basic.demography.api.token']
        );
        $this->assertNotNull($basicDemographyProject, "Basic demography project not null.");
        
        $longitudinalDataProject = new RedCapProject(
            self::$config['api.url'],
            self::$config['longitudinal.data.api.token']
        );
        $this->assertNotNull($longitudinalDataProject, "Longitudinal data project not null.");
       
        #------------------------------
        # Null API URL
        #------------------------------
        $exceptionCaught = false;
        try {
            $project = new RedCapProject(null, self::$config['basic.demography.api.token']);
        } catch (PhpCapException $exception) {
            $exceptionCaught = true;
            $this->assertEquals($exception->getCode(), PhpCapException::INVALID_ARGUMENT);
        }
        $this->assertTrue($exceptionCaught);
        
        #------------------------------
        # Non-string API URL
        #------------------------------
        $exceptionCaught = false;
        try {
            $project = new RedCapProject(123, self::$config['basic.demography.api.token']);
        } catch (PhpCapException $exception) {
            $exceptionCaught = true;
            $this->assertEquals($exception->getCode(), PhpCapException::INVALID_ARGUMENT);
            $this->assertContains('integer', $exception->getMessage());
        }
        $this->assertTrue($exceptionCaught);
    }
    
    public function testExportProjectInfo()
    {
        $callInfo = true;
        $result = self::$basicDemographyProject->exportProjectInfo();
        
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
    
    public function testExportArms()
    {
        $result = self::$longitudinalDataProject->exportArms();
        
        $this->assertEquals(count($result), 2, 'Number of arms test.');
        
        $this->assertEquals($result[0]['arm_num'], 1);
        $this->assertEquals($result[1]['arm_num'], 2);
        
        $this->assertEquals($result[0]['name'], 'Drug A');
        $this->assertEquals($result[1]['name'], 'Drug B');
    }
    
    
    public function testExportRecords()
    {
        $result = self::$basicDemographyProject->exportRecords();
        
        $this->assertEquals(count($result), 100, 'Number of records test.');
        
        $recordIds = array_column($result, 'record_id');
        $this->assertEquals(min($recordIds), 1001, 'Min record_id test.');
        $this->assertEquals(max($recordIds), 1100, 'Max record_id test.');
        
        $lastNameMap = array_flip(array_column($result, 'last_name'));
        $this->assertArrayHasKey('Braun', $lastNameMap, 'Has last name test.');
        $this->assertArrayHasKey('Carter', $lastNameMap, 'Has last name test.');
        $this->assertArrayHasKey('Hayes', $lastNameMap, 'Has last name test.');
    }
    
    public function testExportRecordsAp()
    {
        $result = self::$basicDemographyProject->exportRecordsAp([]);
    
        $this->assertEquals(count($result), 100, 'Number of records test.');
    
        $recordIds = array_column($result, 'record_id');
        $this->assertEquals(min($recordIds), 1001, 'Min record_id test.');
        $this->assertEquals(max($recordIds), 1100, 'Max record_id test.');
    
        $lastNameMap = array_flip(array_column($result, 'last_name'));
        $this->assertArrayHasKey('Braun', $lastNameMap, 'Has last name test.');
        $this->assertArrayHasKey('Carter', $lastNameMap, 'Has last name test.');
        $this->assertArrayHasKey('Hayes', $lastNameMap, 'Has last name test.');
    }
    
    public function testExportRecordsWithFilterLogic()
    {
        $result = self::$basicDemographyProject->exportRecords(
            'php',
            'flat',
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            "[last_name] = 'Thiel'"
        );
        $this->assertEquals(2, count($result), 'Got expected number of records.');
        $firstNameMap = array_flip(array_column($result, 'first_name'));
        $this->assertArrayHasKey('Suzanne', $firstNameMap, 'Has first name test.');
        $this->assertArrayHasKey('Kaia', $firstNameMap, 'Has first name test.');
    }

    public function testExportRecordsApWithFilterLogic()
    {
        $result = self::$basicDemographyProject->exportRecordsAp(['filterLogic' => "[last_name] = 'Thiel'"]);
        
        $this->assertEquals(2, count($result));
        $firstNameMap = array_flip(array_column($result, 'first_name'));
        $this->assertArrayHasKey('Suzanne', $firstNameMap, 'Has first name test.');
        $this->assertArrayHasKey('Kaia', $firstNameMap, 'Has first name test.');
    }
    
    public function testExportRecordsApRecordIds()
    {
        $result = self::$basicDemographyProject->exportRecordsAp(['recordIds' => [1001, 1010, 1100]]);
    
        $this->assertEquals(3, count($result));
        $recordIdMap = array_flip(array_column($result, 'record_id'));
        $this->assertArrayHasKey(1001, $recordIdMap, 'Has record ID 1001.');
        $this->assertArrayHasKey(1001, $recordIdMap, 'Has record ID 1010.');
        $this->assertArrayHasKey(1100, $recordIdMap, 'Has record ID 1100.');
    }
    
    public function testExportRecordsAsCsv()
    {
        $recordIds = array ('1001');
    
        $records = self::$basicDemographyProject->exportRecords($format = 'csv', $type = null, $recordIds);
    
        $this->assertEquals(count($records), 1, 'Correct number of records returned test.');

        $parser = \KzykHys\CsvParser\CsvParser::fromString($records);
        $csv = $parser->parse();
        
        $firstDataRow = $csv[1];
        
        $csvRecordId = $firstDataRow[0];
          
        $this->assertEquals($recordIds[0], $csvRecordId, 'Correct record ID returned test.');
    }
    
    public function testExportRecordsApAsCsv()
    {
        $recordIds = array ('1001');
    
        $records = self::$basicDemographyProject->exportRecordsAp(['format' => 'csv', 'recordIds' => $recordIds]);
    
        $this->assertEquals(count($records), 1, 'Correct number of records returned test.');
    
        $parser = \KzykHys\CsvParser\CsvParser::fromString($records);
        $csv = $parser->parse();
    
        $firstDataRow = $csv[1];
    
        $csvRecordId = $firstDataRow[0];
    
        $this->assertEquals($recordIds[0], $csvRecordId, 'Correct record ID returned test.');
    }
    
    public function testExportRecordsAsOdm()
    {
        $recordIds = array ('1001');
        
        $records = self::$basicDemographyProject->exportRecords($format = 'odm', $type = null, $recordIds);
        
        $this->assertEquals(count($records), 1, 'Correct number of records returned test.');
    
        $xml = new \DomDocument();
        $xml->loadXML($records);
        
        $xmlRecordId = null;
        $itemData = $xml->getElementsByTagName("ItemData");
        foreach ($itemData as $item) {
            if ($item->getAttribute('ItemOID') === 'record_id') {
                $xmlRecordId = $item->getAttribute('Value');
                break;
            }
        }
   
        $this->assertEquals($recordIds[0], $xmlRecordId, 'Correct record ID returned test.');
    }
    
    
    public function testExportRecordsAsXml()
    {
        $recordIds = array('1001');
        
        $records = self::$basicDemographyProject->exportRecords($format = 'xml', $type = null, $recordIds);
        
        $this->assertEquals(count($records), 1, 'Correct number of records returned test.');
        
        $xml = simplexml_load_string($records);
        
        $xmlRecordIdNodes = $xml->xpath("//record_id");
        $xmlRecordId = (string) $xmlRecordIdNodes[0];
        
        $this->assertEquals($recordIds[0], $xmlRecordId, 'Correct record ID returned test.');
    }
    
    
    public function testExportRedcapVersion()
    {
        $result = self::$basicDemographyProject->exportRedcapVersion();
        $this->assertRegExp('/^[0-9]+\.[0-9]+\.[0-9]+$/', $result, 'REDCap version format test.');
    }
    
    public function testFileReadnAndWrite()
    {
        $content = RedCapProject::fileToString(__DIR__."/data/file.txt");
        $this->assertEquals($content, "Test data file.", 'file.txt content match.');
        
        $outputFile = __DIR__."/data/output.txt";
        $text1 = "This is a test.";
        RedCapProject::writeStringToFile($text1, $outputFile);
        $content = RedCapProject::fileToString($outputFile);
        $this->assertEquals($content, $text1, 'String write check.');
        
        $text2 = " Another test.";
        RedCapProject::appendStringToFile($text2, $outputFile);
        $content = RedCapProject::fileToString($outputFile);
        $this->assertEquals($content, $text1 . $text2, 'String append check.');
    }
}
