<?php

namespace IU\PHPCap;

use PHPUnit\Framework\TestCase;

use IU\PHPCap\RedCapProject;

/**
 * PHPUnit tests for events for the RedCapProject class.
 */
class FilesTest extends TestCase
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
    
    public function testFiles()
    {
        #------------------------------------
        # Test importing a file
        #------------------------------------
        $result = self::$longitudinalDataProject->importFile(
            $file = __DIR__.'\data\import-file.txt',
            $recordId = '1001',
            $field = 'patient_document',
            $event = 'enrollment_arm_1'
        );
        
        $this->assertEquals('', $result, 'Blank import result.');
        
        #--------------------------------------------------
        # Test exporting the file that was just imported
        #--------------------------------------------------
        $result = self::$longitudinalDataProject->exportFile(
            $recordId = '1001',
            $field = 'patient_document',
            $event = 'enrollment_arm_1'
        );
        
        $this->assertEquals('test import', $result, 'Export file contents check.');
        
        #---------------------------------------------
        # Test deleting the file that was imported
        #---------------------------------------------
        $result = self::$longitudinalDataProject->deleteFile(
            $recordId = '1001',
            $field = 'patient_document',
            $event = 'enrollment_arm_1'
        );
        
        $this->assertEquals('', $result, 'Blank import result.');

        #---------------------------------------------------------
        # Test trying to export the file that was just deleted
        #---------------------------------------------------------
        $exceptionCaught = false;
        try {
            $result = self::$longitudinalDataProject->exportFile(
                $recordId = '1001',
                $field = 'patient_document',
                $event = 'enrollment_arm_1'
            );
        } catch (PhpCapException $exception) {
            $exceptionCaught = true;
            $this->assertEquals(
                PhpCapException::REDCAP_API_ERROR,
                $exception->getCode(),
                'Export non-existant file exception code check.'
            );
        }
        
        $this->assertTrue($exceptionCaught, 'Export non-existant file exception caught.');
    }
}
