<?php

namespace IU\PHPCap;

use PHPUnit\Framework\TestCase;

use IU\PHPCap\RedCapProject;

/**
 * PHPUnit tests for field names for the RedCapProject class.
 */
class SurveyTest extends TestCase
{
    private static $config;
    private static $apiUrl;
    private static $repeatableSurveyProject;
    private static $participantEmail;
    private static $participantIdentifier;
    
    public static function setUpBeforeClass()
    {
        self::$config = parse_ini_file(__DIR__.'/../config.ini');
        
        self::$apiUrl = self::$config['api.url'];
        
        $apiToken = self::$config['repeatable.survey.api.token'];
        
        self::$participantEmail      = self::$config['survey.participant.email'];
        self::$participantIdentifier = self::$config['survey.participant.identifier'];
        
        self::$repeatableSurveyProject = null;
        if ($apiToken != null && $apiToken !== '') {
            self::$repeatableSurveyProject = new RedCapProject(self::$apiUrl, $apiToken);
        }
    }
    
    public function testExportSurveyLink()
    {
        if (self::$repeatableSurveyProject != null) {
            $recordId = 1;
            $form = "weight";
            $surveyLink = self::$repeatableSurveyProject->exportSurveyLink($recordId, $form);
            
            $this->assertNotNull($surveyLink, 'Non-null survey link check');
        }
    }
    
    public function testExportSurveyLinkWithNullForm()
    {
        if (self::$repeatableSurveyProject != null) {
            $recordId = 1;
            $caughtException = false;
            try {
                $surveyLink = self::$repeatableSurveyProject->exportSurveyLink($recordId, null);
            } catch (PhpCapException $exception) {
                $caughtException = true;
                $code = $exception->getCode();
                $this->assertEquals(PhpCapException::INVALID_ARGUMENT, $code, 'Exception code check.');
            }
            $this->assertTrue($caughtException, 'Exception caught check.');
        }
    }
}
