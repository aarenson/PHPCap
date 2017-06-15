<?php

namespace IU\PHPCap;

use PHPUnit\Framework\TestCase;

use IU\PHPCap\RedCapProject;
use IU\PHPCap\PhpCapException;

/**
 * PHPUnit integration tests for the RedCap class.
 */
class RedCapTest extends TestCase
{
    private static $config;
    private static $apiUrl;
    private static $superToken;
    private static $redCap;
    
    public static function setUpBeforeClass()
    {
        self::$config = parse_ini_file(__DIR__.'/../config.ini');
        self::$apiUrl     = self::$config['api.url'];
        self::$superToken = self::$config['super.token'];

        self::$redCap = new RedCap(self::$apiUrl, self::$superToken);
    }

    /**
     * Note: there is no way for this test to delete the project that
     * it creates.
     */
    public function testCreateProject()
    {
        if (isset(self::$superToken)) {
            $projectTitle = 'PHPCap Created Project Test';
            $purpose = 1;
            $purposeOther = 'PHPCap project creation test';
            $projectNotes = 'This is a test project using php data format.';
        
            $projectData = [
                'project_title' => $projectTitle,
                'purpose' => 1,
                'purpose_other' => $purposeOther,
                'project_notes' => $projectNotes,
                'is_longitudinal' => 1,
                'surveys_enabled' => 1,
                'record_autonumbering_enabled' => 1
            ];
            $project = self::$redCap->createProject($projectData);
        
            $projectInfo = $project->exportProjectInfo();
        
            $this->assertEquals($projectTitle, $projectInfo['project_title'], 'Project title check.');
            $this->assertEquals($purpose, $projectInfo['purpose'], 'Purpose check.');
            $this->assertEquals($purposeOther, $projectInfo['purpose_other'], 'Purpose other check.');
            $this->assertEquals($projectNotes, $projectInfo['project_notes'], 'Project notes check.');
            #$this->assertEquals(1, $projectInfo['is_longitudinal'], 'Is longitudinal check.');
            $this->assertEquals(1, $projectInfo['surveys_enabled'], 'Surveys enabled check.');
            $this->assertEquals(
                1,
                $projectInfo['record_autonumbering_enabled'],
                'Record autonumbering check.'
            );
        }
    }
    
    
    public function testCreateProjectWithNullProjectData()
    {
        $exceptionCaught = false;
        try {
            $projectData = null;
            $project = self::$redCap->createProject($projectData);
        } catch (PhpCapException $exception) {
            $exceptionCaught = true;
            $this->assertEquals(ErrorHandlerInterface::INVALID_ARGUMENT, $exception->getCode());
        }
        $this->assertTrue($exceptionCaught, 'Exception caught check.');
    }
}
