<?php

namespace IU\PHPCap;

use PHPUnit\Framework\TestCase;
use IU\PHPCap\PhpCapException;

class RedCapTest extends TestCase
{
    public function testCreateRedCap()
    {
        $apiUrl = 'https://redcap.somplace.edu/api/';
        $apiToken = '12345678901234567890123456789012';
        
        $connection = $this->getMockBuilder(__NAMESPACE__.'\RedCapApiConnectionInterface')
            ->getMock();
        /*
        $redCapProject = $this->getMockBuilder(__NAMESPACE__.'\RedCapProject')
            ->setMethods(['__construct'])
            ->setConstructorArgs([$apiUrl, $apiToken, $sslVerify = null,
            $caCertificateFile = null, $errorHandler = null, $connection = null])
            ->getMock();
        */
        $redCap = new RedCap($apiUrl, null, null, null, null, $connection);
        $this->assertNotNull($redCap, 'RedCap not null.');
    }
    
    
    public function testCreateRedCapWithNullApiUrl()
    {
        $exceptionCaught = false;
        try {
            $redcap = new RedCap(null, '1234567890123456789012345678901212345678901234567890123456789012');
        } catch (PhpCapException $exception) {
            $exceptionCaught = true;
            $this->assertEquals(
                ErrorHandlerInterface::INVALID_ARGUMENT,
                $exception->getCode(),
                'Exception code check.'
            );
        }
        $this->assertTrue($exceptionCaught, 'Exception caught.');
    }
    
    public function testCreateRedCapWithNonStringApiUrl()
    {
        $exceptionCaught = false;
        try {
            $redcap = new RedCap(123, '1234567890123456789012345678901212345678901234567890123456789012');
        } catch (PhpCapException $exception) {
            $exceptionCaught = true;
            $this->assertEquals(
                ErrorHandlerInterface::INVALID_ARGUMENT,
                $exception->getCode(),
                'Exception code check.'
            );
            $this->assertContains('integer', $exception->getMessage(), 'Message content check.');
        }
        $this->assertTrue($exceptionCaught, 'Exception caught.');
    }
    
    public function testCreateRedCapWithSuperTokenWithInvalidLength()
    {
        $exceptionCaught = false;
        try {
            $redcap = new RedCap('https://redcap.uits.iu.edu/api/', '1234567890');
        } catch (PhpCapException $exception) {
            $exceptionCaught = true;
            $this->assertEquals(
                ErrorHandlerInterface::INVALID_ARGUMENT,
                $exception->getCode(),
                'Exception code check.'
            );
        }
        $this->assertTrue($exceptionCaught, 'Exception caught.');
    }

    
    public function testCreateRedCapWithSuperTokenWithInvalidType()
    {
        $exceptionCaught = false;
        try {
            $redcap = new RedCap('https://redcap.uits.iu.edu/api/', 1234);
        } catch (PhpCapException $exception) {
            $exceptionCaught = true;
            $this->assertEquals(
                ErrorHandlerInterface::INVALID_ARGUMENT,
                $exception->getCode(),
                'Exception code check.'
            );
        }
        $this->assertTrue($exceptionCaught, 'Exception caught.');
    }
    
    
    public function testCreateRedCapWithSuperTokenWithInvalidCharacters()
    {
        $exceptionCaught = false;
        try {
            $superToken = 'ABCDEFG890123456789012345678901212345678901234567890123456789012';
            $redcap = new RedCap('https://redcap.uits.iu.edu/api/', $superToken);
        } catch (PhpCapException $exception) {
            $exceptionCaught = true;
            $this->assertEquals(
                ErrorHandlerInterface::INVALID_ARGUMENT,
                $exception->getCode(),
                'Exception code check.'
            );
        }
        $this->assertTrue($exceptionCaught, 'Exception caught.');
    }
    
    
    public function testCreateRedCapWithInvalidSslVerify()
    {
        $exceptionCaught = false;
        try {
            $apiUrl = 'https://redcap.uits.iu.edu/api/';
            $sslVerify = 1;
            $redcap = new RedCap($apiUrl, null, $sslVerify);
        } catch (PhpCapException $exception) {
            $exceptionCaught = true;
            $this->assertEquals(
                ErrorHandlerInterface::INVALID_ARGUMENT,
                $exception->getCode(),
                'Exception code check.'
            );
        }
        $this->assertTrue($exceptionCaught, 'Exception caught.');
    }
    
    
    public function testCreateRedCapWithInvalidCaCertificatFile()
    {
        $exceptionCaught = false;
        try {
            $apiUrl = 'https://redcap.uits.iu.edu/api/';
            $sslVerify = false;
            $caCertificateFile = 123;
            $redcap = new RedCap($apiUrl, null, $sslVerify, $caCertificateFile);
        } catch (PhpCapException $exception) {
            $exceptionCaught = true;
            $this->assertEquals(
                ErrorHandlerInterface::INVALID_ARGUMENT,
                $exception->getCode(),
                'Exception code check.'
            );
        }
        $this->assertTrue($exceptionCaught, 'Exception caught.');
    }
    
    public function testCreateRedCapWithInvalidErrorHandler()
    {
        $exceptionCaught = false;
        try {
            $apiUrl = 'https://redcap.uits.iu.edu/api/';
            $sslVerify = false;
            $caCertificateFile = null;
            $errorHandler = 'Invalid error handler';
            $redcap = new RedCap($apiUrl, null, $sslVerify, $caCertificateFile, $errorHandler);
        } catch (PhpCapException $exception) {
            $exceptionCaught = true;
            $this->assertEquals(
                ErrorHandlerInterface::INVALID_ARGUMENT,
                $exception->getCode(),
                'Exception code check.'
            );
        }
        $this->assertTrue($exceptionCaught, 'Exception caught.');
    }
    
    public function testCreateRedCapWithInvalidConnection()
    {
        $exceptionCaught = false;
        try {
            $apiUrl = 'https://redcap.uits.iu.edu/api/';
            $sslVerify = false;
            $caCertificateFile = null;
            $errorHandler = null;
            $connection = 'Invalid connection';
            $redcap = new RedCap($apiUrl, null, $sslVerify, $caCertificateFile, $errorHandler, $connection);
        } catch (PhpCapException $exception) {
            $exceptionCaught = true;
            $this->assertEquals(
                ErrorHandlerInterface::INVALID_ARGUMENT,
                $exception->getCode(),
                'Exception code check.'
            );
        }
        $this->assertTrue($exceptionCaught, 'Exception caught.');
    }
    
    public function testCreateProject()
    {
        $connection = $this->getMockBuilder(__NAMESPACE__.'\RedCapApiConnectionInterface')->getMock();
        $errorHandler = $this->getMockBuilder(__NAMESPACE__.'\ErrorHandlerInterface')->getMock();
        
        $callback = function (
            $apiUrl,
            $apiToken,
            $sslVerify = false,
            $caCertificateFile = null,
            $errorHandler = null,
            $connection = null
        ) {
                return 'it worked';
        };
        
        $apiToken = '12345678901234567890123456789012';
        $connection->method('callWithArray')->willReturn($apiToken);
        $apiUrl = 'https://redcap.somplace.edu/api/';
        $apiToken = '12345678901234567890123456789012';
        
        
        $redCap = new RedCap($apiUrl, null, null, null, $errorHandler, $connection);
        $redCap->setProjectConstructor($callback);
        
        $projectData = ['project_title' => 'Test project.', 'purpose' => '0'];
        $project = $redCap->createProject($projectData);
        
        $this->assertEquals('it worked', $project, 'Project value.');
    }
    
    public function testCreateProjectWithJsonAndOdm()
    {
        $connection = $this->getMockBuilder(__NAMESPACE__.'\RedCapApiConnectionInterface')
            ->getMock();
        
        $callback = function (
            $apiUrl,
            $apiToken,
            $sslVerify = false,
            $caCertificateFile = null,
            $errorHandler = null,
            $connection = null
        ) {
                return 'it worked';
        };
        
        $apiToken = '12345678901234567890123456789012';
        $connection->method('callWithArray')->willReturn($apiToken);
        $apiUrl = 'https://redcap.somplace.edu/api';
        $apiToken = '12345678901234567890123456789012';
        
        
        $redCap = new RedCap($apiUrl, null, null, null, null, $connection);
        $redCap->setProjectConstructor($callback);
        
        $odm = '';
        $projectData = '[{"project_title": "Test project.", "purpose": "0"}]';
        $project = $redCap->createProject($projectData, $format = 'json', $odm);
        
        $this->assertEquals('it worked', $project, 'Project value.');
    }
    
    public function testCreateProjectWithRedCapApiError()
    {
        $connection = $this->getMockBuilder(__NAMESPACE__.'\RedCapApiConnectionInterface')
        ->getMock();
        
        $callback = function (
            $apiUrl,
            $apiToken,
            $sslVerify = false,
            $caCertificateFile = null,
            $errorHandler = null,
            $connection = null
        ) {
                return 'it worked';
        };
        
        $apiToken = '12345678901234567890123456789012';
        $connection->expects($this->once())->method('callWithArray')->willReturn('{"error": "REDCap API error."}');
        $apiUrl = 'https://redcap.somplace.edu/api';
        $apiToken = '12345678901234567890123456789012';
        
        
        $redCap = new RedCap($apiUrl, null, null, null, null, $connection);
        $redCap->setProjectConstructor($callback);
        
        $projectData = ['project_title' => 'Test project.', 'purpose' => '0'];
        $exceptionCaught = false;
        try {
            $project = $redCap->createProject($projectData);
        } catch (PhpCapException $exception) {
            $exceptionCaught = true;
            $expectedCode = ErrorHandlerInterface::REDCAP_API_ERROR;
            $code = $exception->getCode();
            $this->assertEquals($expectedCode, $code, 'Exception code check.');
        }
        $this->assertTrue($exceptionCaught, 'Exception caught.');
    }
    
    public function testCreateProjectWithInvalidFormatType()
    {
        $connection = $this->getMockBuilder(__NAMESPACE__.'\RedCapApiConnectionInterface')->getMock();
        
        $apiUrl = 'https://redcap.somplace.edu/api';
        
        $redCap = new RedCap($apiUrl, null, null, null, null, $connection);
        
        $projectData = ['project_title' => 'Test project.', 'purpose' => '0'];
        $exceptionCaught = false;
        try {
            $project = $redCap->createProject($projectData, $format = 1 /* invalid */);
        } catch (PhpCapException $exception) {
            $exceptionCaught = true;
            $expectedCode = ErrorHandlerInterface::INVALID_ARGUMENT;
            $code = $exception->getCode();
            $this->assertEquals($expectedCode, $code, 'Exception code check.');
        }
        $this->assertTrue($exceptionCaught, 'Exception caught.');
    }
    
    public function testCreateProjectWithInvalidFormatValue()
    {
        $connection = $this->getMockBuilder(__NAMESPACE__.'\RedCapApiConnectionInterface')->getMock();
        
        $apiUrl = 'https://redcap.somplace.edu/api';
        
        $redCap = new RedCap($apiUrl, null, null, null, null, $connection);
        
        $projectData = ['project_title' => 'Test project.', 'purpose' => '0'];
        $exceptionCaught = false;
        try {
            $project = $redCap->createProject($projectData, $format = 'invalid');
        } catch (PhpCapException $exception) {
            $exceptionCaught = true;
            $expectedCode = ErrorHandlerInterface::INVALID_ARGUMENT;
            $code = $exception->getCode();
            $this->assertEquals($expectedCode, $code, 'Exception code check.');
        }
        $this->assertTrue($exceptionCaught, 'Exception caught.');
    }
    
    public function testCreateProjectWithNullData()
    {
        $connection = $this->getMockBuilder(__NAMESPACE__.'\RedCapApiConnectionInterface')->getMock();
        
        $apiUrl = 'https://redcap.somplace.edu/api';
        
        $redCap = new RedCap($apiUrl, null, null, null, null, $connection);
        
        $projectData = null;
        $exceptionCaught = false;
        try {
            $project = $redCap->createProject($projectData);
        } catch (PhpCapException $exception) {
            $exceptionCaught = true;
            $expectedCode = ErrorHandlerInterface::INVALID_ARGUMENT;
            $code = $exception->getCode();
            $this->assertEquals($expectedCode, $code, 'Exception code check.');
        }
        $this->assertTrue($exceptionCaught, 'Exception caught.');
    }
    
    public function testGetProject()
    {
        $connection = $this->getMockBuilder(__NAMESPACE__.'\RedCapApiConnectionInterface')->getMock();
        $errorHandler = $this->getMockBuilder(__NAMESPACE__.'\ErrorHandlerInterface')->getMock();
        
        $callback = function (
            $apiUrl,
            $apiToken,
            $sslVerify = false,
            $caCertificateFile = null,
            $errorHandler = null,
            $connection = null
        ) {
                return 'it worked';
        };
        
        //$connection->method('callWithArray')->willReturn( $apiToken );
        $apiUrl = 'https://redcap.somplace.edu/api';
        
        
        $redCap = new RedCap($apiUrl, null, null, null, $errorHandler, $connection);
        $redCap->setProjectConstructor($callback);
        
        $apiToken = '12345678901234567890123456789012';
        $project = $redCap->getProject($apiToken);
        
        $this->assertEquals('it worked', $project, 'Project value.');
    }
    
    public function testGetProjectWithNullApiToken()
    {
        $connection = $this->getMockBuilder(__NAMESPACE__.'\RedCapApiConnectionInterface')->getMock();
        $apiUrl = 'https://redcap.somplace.edu/api';
        $redCap = new RedCap($apiUrl, null, null, null, null, $connection);
        
        $exceptionCaught = false;
        try {
            $project = $redCap->getProject($apiToken = null);
        } catch (PhpCapException $exception) {
            $exceptionCaught = true;
            $code = $exception->getCode();
            $expectedCode = ErrorHandlerInterface::INVALID_ARGUMENT;
            $this->assertEquals($expectedCode, $code, 'Exception code check.');
        }
        $this->assertTrue($exceptionCaught, 'Exception caught.');
    }
    
    public function testGetProjectWithApiTokenWithInvalidType()
    {
        $connection = $this->getMockBuilder(__NAMESPACE__.'\RedCapApiConnectionInterface')->getMock();
        $apiUrl = 'https://redcap.somplace.edu/api';
        $redCap = new RedCap($apiUrl, null, null, null, null, $connection);
        
        $exceptionCaught = false;
        try {
            $project = $redCap->getProject($apiToken = 123);
        } catch (PhpCapException $exception) {
            $exceptionCaught = true;
            $code = $exception->getCode();
            $expectedCode = ErrorHandlerInterface::INVALID_ARGUMENT;
            $this->assertEquals($expectedCode, $code, 'Exception code check.');
        }
        $this->assertTrue($exceptionCaught, 'Exception caught.');
    }
    
    public function testGetProjectWithApiTokenWithInvalidFormat()
    {
        $connection = $this->getMockBuilder(__NAMESPACE__.'\RedCapApiConnectionInterface')->getMock();
        $apiUrl = 'https://redcap.somplace.edu/api';
        $redCap = new RedCap($apiUrl, null, null, null, null, $connection);
        
        $exceptionCaught = false;
        try {
            $project = $redCap->getProject($apiToken = '1234567890123456789012345678901G');
        } catch (PhpCapException $exception) {
            $exceptionCaught = true;
            $code = $exception->getCode();
            $expectedCode = ErrorHandlerInterface::INVALID_ARGUMENT;
            $this->assertEquals($expectedCode, $code, 'Exception code check.');
        }
        $this->assertTrue($exceptionCaught, 'Exception caught.');
    }
    
    
    public function testGetProjectWithApiTokenWithInvalidLength()
    {
        $connection = $this->getMockBuilder(__NAMESPACE__.'\RedCapApiConnectionInterface')->getMock();
        $apiUrl = 'https://redcap.somplace.edu/api';
        $redCap = new RedCap($apiUrl, null, null, null, null, $connection);
        
        $exceptionCaught = false;
        try {
            $project = $redCap->getProject($apiToken = '123456789012345678901234567890123');
        } catch (PhpCapException $exception) {
            $exceptionCaught = true;
            $code = $exception->getCode();
            $expectedCode = ErrorHandlerInterface::INVALID_ARGUMENT;
            $this->assertEquals($expectedCode, $code, 'Exception code check.');
        }
        $this->assertTrue($exceptionCaught, 'Exception caught.');
    }
}
