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
    private static $apiToken;
    private static $repeatableSurveyProject;
    private static $participantEmail;
    private static $participantIdentifier;
    
    public static function setUpBeforeClass()
    {
        self::$config = parse_ini_file(__DIR__.'/../config.ini');
        
        self::$apiUrl = self::$config['api.url'];
        
        self::$apiToken = self::$config['repeatable.survey.api.token'];
        
        self::$participantEmail      = self::$config['survey.participant.email'];
        self::$participantIdentifier = self::$config['survey.participant.identifier'];
        
        self::$repeatableSurveyProject = null;
        if (self::$apiToken != null && self::$apiToken !== '') {
            self::$repeatableSurveyProject = new RedCapProject(self::$apiUrl, self::$apiToken);
        }
    }
    
    public function testExportSurveyLink()
    {
        if (self::$repeatableSurveyProject != null) {
            $recordId = 1;
            $form = "weight";
            $surveyLink = self::$repeatableSurveyProject->exportSurveyLink($recordId, $form);
            
            $this->assertNotNull($surveyLink, 'Non-null survey link check.');
            
            $this->assertStringStartsWith('http', $surveyLink, 'Survey link starts with "http".');
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
    
    public function testExportSurveyParticipants()
    {
        if (self::$repeatableSurveyProject != null) {
            $form = 'weight';
            $surveyParticipants = self::$repeatableSurveyProject->exportSurveyParticipants($form);

            $emailFound = false;
            $identifierFound = false;
            foreach ($surveyParticipants as $participant) {
                if ($participant['email'] === self::$participantEmail) {
                    $emailFound = true;
                }
                if ($participant['identifier'] === self::$participantIdentifier) {
                    $identifierFound = true;
                }
                if ($emailFound === true && $identifierFound === true) {
                    break;
                }
            }
            
            $this->assertTrue($emailFound, 'Participant e-mail found.');
            $this->assertTrue($identifierFound, 'Participant identifier found.');
        }
    }
    
    
    public function testExportSurveyQueueLink()
    {
        if (self::$repeatableSurveyProject != null) {
            $recordId = 1;
            $form = "weight";
            $surveyQueueLink = self::$repeatableSurveyProject->exportSurveyQueueLink($recordId, $form);
            
            $this->assertNotNull($surveyQueueLink, 'Non-null survey queue link check.');
            
            $this->assertStringStartsWith('http', $surveyQueueLink, 'Survey queue link starts with "http".');
        }
    }
    
    
    public function testExportSurveyReturnCode()
    {
        if (self::$repeatableSurveyProject != null) {
            $recordId = 1;
            $form = "weight";
            $surveyReturnCode = self::$repeatableSurveyProject->exportSurveyReturnCode($recordId, $form);
            
            $this->assertNotNull($surveyReturnCode, 'Non-null survey return code check.');
            $this->assertTrue(ctype_alnum($surveyReturnCode), 'Alphanumeric survey return code check.');
        }
    }
}
