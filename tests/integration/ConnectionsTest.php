<?php

namespace IU\PHPCap;

use PHPUnit\Framework\TestCase;

use IU\PHPCap\RedCapProject;

/**
 * PHPUnit tests for using the underlying connection for the RedCapProject class.
 */
class ConnectionsTest extends TestCase
{
    private static $config;
    private static $basicDemographyProject;
    private static $longitudinalDataProject;
    private static $apiConnection;
    private static $caCertificateFile;
    
    
    public static function setUpBeforeClass()
    {
        self::$config = parse_ini_file(__DIR__.'/../config.ini');
        
        self::$apiConnection = new RedCapApiConnection(self::$config['api.url']);
        
        self::$basicDemographyProject = new RedCapProject(
            self::$config['api.url'],
            self::$config['basic.demography.api.token']
        );
        self::$longitudinalDataProject = new RedCapProject(
            self::$config['api.url'],
            self::$config['longitudinal.data.api.token']
        );
    }
    
    public function testProjectConnection()
    {
        $project = self::$basicDemographyProject;
        $connection = $project->getConnection();
        $this->assertNotNull($connection, 'Connection not null check.');
        
        $connection->setTimeoutInSeconds(10);
        $timeout = $connection->getTimeoutInSeconds();
        
        $this->assertEquals(10, $timeout, 'Connection timeout check.');
        
        $project->setTimeoutInSeconds(10);
        $timeout = $project->getTimeoutInSeconds();
        
        $this->assertEquals(10, $timeout, 'Project timeout check.');
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
        SystemFunctions::setIsReadableToFail();
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
        SystemFunctions::resetIsReadable();
    
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
    
        SystemFunctions::$curlErrorNumber = 3;
        SystemFunctions::$curlErrorMessage = 'The URL was not properly formatted.';
        $exceptionCaught = false;
        try {
            $callInfo = self::$apiConnection->getCallInfo();
        } catch (PHPCapException $exception) {
            $exceptionCaught = true;
            $this->assertEquals($exception->getCode(), PhpCapException::CURL_ERROR);
            $this->assertEquals($exception->getCurlErrorNumber(), SystemFunctions::$curlErrorNumber);
            $this->assertEquals($exception->getMessage(), SystemFunctions::$curlErrorMessage);
        }
        $this->assertTrue($exceptionCaught);
        SystemFunctions::$curlErrorNumber = 0;
        SystemFunctions::$curlErrorMessage = '';
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
        SystemFunctions::$curlErrorNumber = 3;
        SystemFunctions::$curlErrorMessage = 'The URL was not properly formatted.';
        $exceptionCaught = false;
        try {
            $result = self::$apiConnection->call($callData);
        } catch (PhpCapException $exception) {
            $exceptionCaught = true;
            $this->assertEquals($exception->getCode(), PhpCapException::CURL_ERROR);
            $this->assertEquals($exception->getCurlErrorNumber(), SystemFunctions::$curlErrorNumber);
            $this->assertEquals($exception->getMessage(), SystemFunctions::$curlErrorMessage);
        }
        $this->assertTrue($exceptionCaught);
        SystemFunctions::$curlErrorNumber = 0;
        SystemFunctions::$curlErrorMessage = '';
    
        #--------------------------------------
        # Test http code 301 in call
        #--------------------------------------
        SystemFunctions::$httpCode = 301;
        $exceptionCaught = false;
        try {
            $result = self::$apiConnection->call($callData);
        } catch (PhpCapException $exception) {
            $exceptionCaught = true;
            $this->assertEquals($exception->getCode(), PhpCapException::INVALID_URL);
            $this->assertEquals($exception->getHttpStatusCode(), SystemFunctions::$httpCode);
        }
        $this->assertTrue($exceptionCaught);
        SystemFunctions::$httpCode = null;
    
        #--------------------------------------
        # Test http code 404 in call
        #--------------------------------------
        SystemFunctions::$httpCode = 404;
        $exceptionCaught = false;
        try {
            $result = self::$apiConnection->call($callData);
        } catch (PhpCapException $exception) {
            $exceptionCaught = true;
            $this->assertEquals($exception->getCode(), PhpCapException::INVALID_URL);
            $this->assertEquals($exception->getHttpStatusCode(), SystemFunctions::$httpCode);
        }
        $this->assertTrue($exceptionCaught);
        SystemFunctions::$httpCode = null;
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
