<?php

require_once(__DIR__.'/../src/RedCapApiConnection.php');

use PHPUnit\Framework\TestCase;
use IU\PHPCap\RedCapApiConnection;

class ApiConnectionTests extends TestCase {
    private static $config;
    private static $apiConnection;
    
    public static function setUpBeforeClass()
    {
        self::$config = include('config.php');
        self::$apiConnection = new RedCapApiConnection(self::$config['url']);
    }
    
    public function testProjectInfo()
    {
        $data = array(
                'token' => self::$config['token'],
                'content' => 'project',
                'format' => 'json',
                'returnFormat' => 'json'
        );
        
        $result = self::$apiConnection->call($data);
        $result = json_decode($result, true);
        
        //print_r($result);
        
        $this->assertEquals($result['project_language'], 'English', 'Project info "project_language" test.');
        $this->assertEquals($result['purpose_other'], 'Testing', 'Project info "purpose_other" test.');

    }

}
