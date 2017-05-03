<?php

namespace IU\PHPCap;

use PHPUnit\Framework\TestCase;

function curl_errno($curlHandle)
{
    if (RedCapApiConnectionTest::$curlErrorNumber != 0) {
        $errno = RedCapApiConnectionTest::$curlErrorNumber;
    } else {
        $errno = \curl_errno($curlHandle);
    }
    return $errno;
}

function curl_error($curlHandle)
{
    if (RedCapApiConnectionTest::$curlErrorNumber !== '') {
        $error = RedCapApiConnectionTest::$curlErrorMessage;
    } else {
        $errno = \curl_error($curlHandle);
    }
    return $error;
}

function is_readable($file)
{
    $isReadable = RedCapApiConnectionTest::$isFileReadable ? \is_readable($file) : false;
    return $isReadable;
}

function curl_getinfo($curlHandle, $curlOption = null)
{
    $result = 0;
    if (isset(RedCapApiConnectionTest::$httpCode)) {
        $result = RedCapApiConnectionTest::$httpCode;
    } else {
        if ($curlOption == null) {
            $result = \curl_getinfo($curlHandle);
        } else {
            $result = \curl_getinfo($curlHandle, $curlOption);
        }
    }
    return $result;
}



class RedCapApiConnectionTest extends TestCase
{
    private static $config;
    private static $apiConnection;
    private static $caCertificateFile;
    
    public static $curlErrorNumber;
    public static $curlErrorMessage;
    public static $isFileReadable;
    public static $httpCode;
    
    public static function setUpBeforeClass()
    {
        self::$config = parse_ini_file('config.ini');
        self::$apiConnection = new RedCapApiConnection(self::$config['api.url']);
        
        self::$curlErrorNumber  = 0;
        self::$curlErrorMessage = '';
        self::$isFileReadable   = true;
        self::$httpCode         = null;
    }
    
    public function testConnectionCreation()
    {
        $apiConnection = new RedCapApiConnection(self::$config['api.url']);
        $this->assertNotNull($apiConnection, 'Connection is not null.');
        
        # Test CA certificate file not found
        $caughtException = false;
        try {
            $apiConnection = new RedCapApiConnection(self::$config['api.url'], true, uniqid().".txt");
        } catch (PhpCapException $exception) {
            $caughtException = true;
            $this->assertEquals(
                $exception->getCode(),
                PhpCapException::CA_CERTIFICATE_FILE_NOT_FOUND,
                'CA cert file not found.'
            );
        }
        $this->assertTrue($caughtException, 'Caught CA cert file not found exception.');
        
        # Test CA certificate file not readable
        RedCapApiConnectionTest::$isFileReadable = false;
        $caughtException = false;
        try {
            $apiConnection = new RedCapApiConnection(self::$config['api.url'], true, __FILE__);
        } catch (PhpCapException $exception) {
            $caughtException = true;
            $this->assertEquals(
                $exception->getCode(),
                PhpCapException::CA_CERTIFICATE_FILE_UNREADABLE,
                'CA cert file is unreadable.'
            );
        }
        $this->assertTrue($caughtException, 'Caught CA cert file unreadable exception.');
        RedCapApiConnectionTest::$isFileReadable = true;
        
        if (isset(self::$config['ca.certificate.file'])) {
            $apiConnection = new RedCapApiConnection(
                self::$config['api.url'],
                true,
                self::$config['ca.certificate.file']
            );
            $this->assertNotNull($apiConnection);
        }
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
        } catch (PHPCapException $exception) {
            $exceptionCaught = true;
            $this->assertEquals($exception->getCode(), PhpCapException::CURL_ERROR);
            $this->assertEquals($exception->getCurlErrorNumber(), self::$curlErrorNumber);
            $this->assertEquals($exception->getMessage(), self::$curlErrorMessage);
        }
        $this->assertTrue($exceptionCaught);
        self::$curlErrorNumber = 0;
        self::$curlErrorMessage = '';
    }
    
    public function testCallWithInvalidData()
    {
        $exceptionCaught = false;
        try {
            self::$apiConnection->call(123);
        } catch (PhpCapException $exception) {
            $exceptionCaught = true;
            $this->assertEquals($exception->getCode(), PhpCapException::INVALID_ARGUMENT);
        }
        $this->assertTrue($exceptionCaught);
    }
    
    public function testCallWithCurlErrors()
    {
        # Set up valid call data
        $data = array(
                'token' => self::$config['basic.demography.api.token'],
                'content' => 'project',
                'format' => 'json',
                'returnFormat' => 'json'
        );
        $callData = http_build_query($data, '', '&');
        
        #---------------------------------------
        # Test curl_exec error in call
        #---------------------------------------
        self::$curlErrorNumber = 3;
        self::$curlErrorMessage = 'The URL was not properly formatted.';
        $exceptionCaught = false;
        try {
            $result = self::$apiConnection->call($callData);
        } catch (PhpCapException $exception) {
            $exceptionCaught = true;
            $this->assertEquals($exception->getCode(), PhpCapException::CURL_ERROR);
            $this->assertEquals($exception->getCurlErrorNumber(), self::$curlErrorNumber);
            $this->assertEquals($exception->getMessage(), self::$curlErrorMessage);
        }
        $this->assertTrue($exceptionCaught);
        self::$curlErrorNumber = 0;
        self::$curlErrorMessage = '';
        
        #--------------------------------------
        # Test http code 301 in call
        #--------------------------------------
        self::$httpCode = 301;
        $exceptionCaught = false;
        try {
            $result = self::$apiConnection->call($callData);
        } catch (PhpCapException $exception) {
            $exceptionCaught = true;
            $this->assertEquals($exception->getCode(), PhpCapException::INVALID_URL);
            $this->assertEquals($exception->getHttpStatusCode(), self::$httpCode);
        }
        $this->assertTrue($exceptionCaught);
        self::$httpCode = null;
        
        #--------------------------------------
        # Test http code 404 in call
        #--------------------------------------
        self::$httpCode = 404;
        $exceptionCaught = false;
        try {
            $result = self::$apiConnection->call($callData);
        } catch (PhpCapException $exception) {
            $exceptionCaught = true;
            $this->assertEquals($exception->getCode(), PhpCapException::INVALID_URL);
            $this->assertEquals($exception->getHttpStatusCode(), self::$httpCode);
        }
        $this->assertTrue($exceptionCaught);
        self::$httpCode = null;
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
