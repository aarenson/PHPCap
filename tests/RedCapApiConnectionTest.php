<?php

namespace IU\PHPCap;

use PHPUnit\Framework\TestCase;
use IU\PHPCap\RedCapApiConnection;


function curl_errno($curlHandle) {
    if (RedCapApiConnectionTest::$curlErrorNumber != 0) {
        $errno = RedCapApiConnectionTest::$curlErrorNumber;
    }
    else {
        $errno = \curl_errno($curlHandle);
    }
    return $errno;
}

function curl_error($curlHandle) {
    if (RedCapApiConnectionTest::$curlErrorNumber !== '') {
        $error = RedCapApiConnectionTest::$curlErrorMessage;
    }
    else {
        $errno = \curl_error($curlHandle);
    }
    return $error;
}


class RedCapApiConnectionTest extends TestCase {
    private static $config;
    private static $apiConnection;
    
    public static $curlErrorNumber;
    public static $curlErrorMessage;
    
    public static function setUpBeforeClass()
    {
        self::$config = parse_ini_file('config.ini');
        self::$apiConnection = new RedCapApiConnection(self::$config['api.url']);
        
        self::$curlErrorNumber  = 0;
        self::$curlErrorMessage = '';
    }
    
    public function testConnectionCreation()
    {
        $apiConnection = new RedCapApiConnection(self::$config['api.url']);
        $this->assertNotNull($apiConnection, 'Connection is not null.');
    }
    
    public function testProjectInfo()
    {
        $data = array(
                'token' => self::$config['basic.demography.api.token'],
                'content' => 'project',
                'format' => 'json',
                'returnFormat' => 'json'
        );
        
        $callData = http_build_query($data, '', '&');
        
        $result = self::$apiConnection->call($callData);
        $result = json_decode($result, true);
        
        //print_r($result);
        
        $this->assertEquals($result['project_language'], 'English', 'Project info "project_language" test.');
        $this->assertEquals($result['purpose_other'], 'PHPCap testing', 'Project info "purpose_other" test.');
        
        $callInfo = self::$apiConnection->getCallInfo();
        $this->assertTrue(array_key_exists('url', $callInfo), "callInfo has 'url' key.");
        $this->assertEquals($callInfo['url'], self::$config['api.url'], 'callInfo URL is correct.');
        
        self::$curlErrorNumber = 3;
        self::$curlErrorMessage = 'The URL was not properly formatted.';
        $exceptionCaught = false;
        try {
            $callInfo = self::$apiConnection->getCallInfo();
        }
        catch (PHPCapException $exception) {
            $exceptionCaught = true;
            $this->assertEquals($exception->getCode(), PhpCapException::CURL_ERROR);
            $this->assertEquals($exception->getCurlErrorNumber(), self::$curlErrorNumber);
            $this->assertEquals($exception->getMessage(), self::$curlErrorMessage);
        }
        $this->assertTrue($exceptionCaught);
        self::$curlErrorNumber = 0;
        self::$curlErrorMessage = '';
    }
    
    public function testTimeout()
    {
        $setTimeout = 10;
        self::$apiConnection->setTimeoutInSeconds($setTimeout);
        $getTimeout = self::$apiConnection->getTimeoutInSeconds();
        $this->assertEquals($setTimeout, $getTimeout, "Timeout comparison 1");
        
        $setTimeout = 24;
        self::$apiConnection->setTimeoutInSeconds($setTimeout);
        $getTimeout = self::$apiConnection->getTimeoutInSeconds();
        $this->assertEquals($setTimeout, $getTimeout, "Timeout comparison 2");
        
        $setTimeout = 32;
        self::$apiConnection->setCurlOption(CURLOPT_TIMEOUT, $setTimeout);
        $getTimeout = self::$apiConnection->getTimeoutInSeconds();
        $this->assertEquals($setTimeout, $getTimeout, "Timeout comparison 3");
    }

}
