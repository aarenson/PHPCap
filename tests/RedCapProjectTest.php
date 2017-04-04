<?php

require_once(__DIR__.'/../src/RedCapApiConnection.php');
require_once(__DIR__.'/../src/RedCapProject.php');

use PHPUnit\Framework\TestCase;
use IU\PHPCap\RedCapApiConnection;
use IU\PHPCap\RedCapProject;

class ProjectTests extends TestCase {
    private static $config;
    private static $project;
    
    public static function setUpBeforeClass()
    {
        self::$config = include('config.php');
        self::$project = new RedCapProject(self::$config['url'], self::$config['token']);
    }
    
    public function testProjectInfo()
    {
        $callInfo = true;
        $result = self::$project->exportProjectInfo($callInfo);
        
        $this->assertEquals($result['project_language'], 'English', 'Project info "project_language" test.');
        $this->assertEquals($result['purpose_other'], 'Testing', 'Project info "purpose_other" test.');
    }
    
    public function testMetadata()
    {
        $result = self::$project->exportMetadata();
         
        $this->assertArrayHasKey('field_name', $result[0], 'Metadata has field_name field test.');
        $this->assertEquals($result[0]['field_name'], 'study_id', 'Metadata has study_id field test.');
    
        $callInfo = self::$project->getCallInfo();
     
        $this->assertEquals($callInfo['url'], self::$config['url'], 'Metadata url test.');
        $this->assertArrayHasKey('content_type', $callInfo, 'Metadata has content type test.');
        $this->assertArrayHasKey('http_code', $callInfo, 'Metadata has HTTP code test.');
    }

}
