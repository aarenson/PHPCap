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
    }
    
    public function testConnectionCreation()
    {
        $connection = new RedCapApiConnection(self::$config['api.url']);
        $this->assertNotNull($connection, 'Connection is not null.');
        
        $this->assertEquals(self::$config['api.url'], $connection->getUrl(), 'URL check.');
        
        $this->assertFalse($connection->getSslVerify(), 'SSL verify default check.');
        
        $connection->setSslVerify(true);
        $sslVerify = $connection->getSslVerify();
        $this->assertTrue($connection->getSslVerify(), 'Set SSL verify check.');
        
        $this->assertNull($connection->getCaCertificateFile(), 'SSL CA certificate file default check.');
        
        $caCertFile = 'USERTrustRSACertificationAuthority.crt';
        $connection->setCaCertificateFile($caCertFile);
        $this->assertEquals($caCertFile, $connection->getCaCertificateFile(), 'CA cert. file check.');
        
        $timeout = $connection->getTimeoutInSeconds();
        $timeout += 120;
        $connection->setTimeoutInSeconds($timeout);
        $this->assertEquals(
            $timeout,
            $connection->getTimeoutInSeconds(),
            'Timeout check.'
        );
        
        $connectionTimeout = $connection->getConnectionTimeoutInSeconds();
        $connectionTimeout += 20;
        $connection->setConnectionTimeoutInSeconds($connectionTimeout);
        $this->assertEquals(
            $connectionTimeout,
            $connection->getConnectionTimeoutInSeconds(),
            'Connection timeout check.'
        );
    }
    
    public function testConnectionCreationWithErrorHandler()
    {
        $errorHandler = new ErrorHandler();
        $connection = new RedCapApiConnection(
            $apiUrl = self::$config['api.url'],
            $sslVerify = false,
            $caCertificateFile = null,
            $errorHandler
        );
        
        $this->assertNotNull($connection, 'Connection not null check.');
        
        $errorHandlerFromGet = $connection->getErrorHandler();
        $this->assertSame($errorHandler, $errorHandlerFromGet, 'Error handler check.');
    }
    
    public function testConnectionSetErrorHandler()
    {
        $errorHandler = new ErrorHandler();
        $connection = new RedCapApiConnection(self::$config['api.url']);
        
        $this->assertNotNull($connection, 'Connection not null check.');
        
        $connection->setErrorHandler($errorHandler);
        
        $errorHandlerFromGet = $connection->getErrorHandler();
        
        $this->assertSame($errorHandler, $errorHandlerFromGet, 'Error handler check.');
    }
    
    public function testConnectionWithCaCertificateFile()
    {
        if (isset(self::$config['ca.certificate.file'])) {
            $apiConnection = new RedCapApiConnection(
                self::$config['api.url'],
                true,
                self::$config['ca.certificate.file']
            );
            $this->assertNotNull($apiConnection, 'Conection not null.');
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
            $this->assertEquals($exception->getCode(), ErrorHandlerInterface::CONNECTION_ERROR);
            $this->assertEquals($exception->getConnectionErrorNumber(), SystemFunctions::$curlErrorNumber);
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
            $this->assertEquals($exception->getCode(), ErrorHandlerInterface::INVALID_ARGUMENT);
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
            $this->assertEquals($exception->getCode(), ErrorHandlerInterface::CONNECTION_ERROR);
            $this->assertEquals($exception->getConnectionErrorNumber(), SystemFunctions::$curlErrorNumber);
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
            $this->assertEquals($exception->getCode(), ErrorHandlerInterface::INVALID_URL);
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
            $this->assertEquals($exception->getCode(), ErrorHandlerInterface::INVALID_URL);
            $this->assertEquals($exception->getHttpStatusCode(), SystemFunctions::$httpCode);
        }
        $this->assertTrue($exceptionCaught);
        SystemFunctions::$httpCode = null;
    }
    
    
    public function testTimeout1()
    {
        $setTimeout = 10;
        self::$apiConnection->setTimeoutInSeconds($setTimeout);
        $getTimeout = self::$apiConnection->getTimeoutInSeconds();
        $this->assertEquals($setTimeout, $getTimeout, "Timeout comparison 1");
    }
    
    public function testTimeout2()
    {
        $setTimeout = 24;
        self::$apiConnection->setTimeoutInSeconds($setTimeout);
        $getTimeout = self::$apiConnection->getTimeoutInSeconds();
        $this->assertEquals($setTimeout, $getTimeout, "Timeout comparison 2");
    }
    
    public function testTimeout3()
    {
        $setTimeout = 32;
        self::$apiConnection->setCurlOption(CURLOPT_TIMEOUT, $setTimeout);
        $getTimeout = self::$apiConnection->getTimeoutInSeconds();
        $this->assertEquals($setTimeout, $getTimeout, "Timeout comparison 3");
    }
}
