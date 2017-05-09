<?php

namespace IU\PHPCap;

use PHPUnit\Framework\TestCase;

use IU\PHPCap\RedCapProject;
use IU\PHPCap\PhpCapException;

/**
 * PHPUnit tests for the RedCapProject class.
 */
class RedCapProjectTest extends TestCase
{
    private static $config;
    private static $basicDemographyProject;
    private static $longitudinalDataProject;
    
    public static function setUpBeforeClass()
    {
        self::$config = parse_ini_file(__DIR__.'/../config.ini');
        self::$basicDemographyProject = new RedCapProject(
            self::$config['api.url'],
            self::$config['basic.demography.api.token']
        );
        self::$longitudinalDataProject = new RedCapProject(
            self::$config['api.url'],
            self::$config['longitudinal.data.api.token']
        );
    }
    
    /**
     * Note: need to have an actual test that creates a project, otherwise the constructor
     * won't show up in code coverage
     */
    public function testCreateProject()
    {
        $basicDemographyProject = new RedCapProject(
            self::$config['api.url'],
            self::$config['basic.demography.api.token']
        );
        $this->assertNotNull($basicDemographyProject, "Basic demography project not null.");
        
        $longitudinalDataProject = new RedCapProject(
            self::$config['api.url'],
            self::$config['longitudinal.data.api.token']
        );
        $this->assertNotNull($longitudinalDataProject, "Longitudinal data project not null.");
    }
    
    public function testCreateProjectWithNullApiUrlI()
    {
        #------------------------------
        # Null API URL
        #------------------------------
        $exceptionCaught = false;
        try {
            $project = new RedCapProject(null, self::$config['basic.demography.api.token']);
        } catch (PhpCapException $exception) {
            $exceptionCaught = true;
            $this->assertEquals(PhpCapException::INVALID_ARGUMENT, $exception->getCode());
        }
        $this->assertTrue($exceptionCaught, 'Null API URL exception caught.');
    }
    
    public function testCreateProjectWithNonStringApiUrl()
    {
        #------------------------------
        # Non-string API URL
        #------------------------------
        $exceptionCaught = false;
        try {
            $project = new RedCapProject(123, self::$config['basic.demography.api.token']);
        } catch (PhpCapException $exception) {
            $exceptionCaught = true;
            $this->assertEquals(PhpCapException::INVALID_ARGUMENT, $exception->getCode());
            $this->assertContains('integer', $exception->getMessage());
        }
        $this->assertTrue($exceptionCaught);
    }
    
    public function testCreateProjectWithNullApiToken()
    {
        #----------------------------------
        # Null API token
        #----------------------------------
        $exceptionCaught = false;
        try {
            $project = new RedCapProject(self::$config['api.url'], null);
        } catch (PhpCapException $exception) {
            $exceptionCaught = true;
            $this->assertEquals(
                PhpCapException::INVALID_ARGUMENT,
                $exception->getCode(),
                'Null API token exception code check.'
            );
        }
        $this->assertTrue($exceptionCaught, 'Null API token exception caught.');
    }
    
    public function testCreateProjectWithApiTokenWithInvalidType()
    {
        #----------------------------------
        # API token with invalid type
        #----------------------------------
        $exceptionCaught = false;
        try {
            $project = new RedCapProject(self::$config['api.url'], 123);
        } catch (PhpCapException $exception) {
            $exceptionCaught = true;
            $this->assertEquals(
                PhpCapException::INVALID_ARGUMENT,
                $exception->getCode(),
                'API token with wrong type.'
            );
        }
        $this->assertTrue($exceptionCaught, 'API token with wrong type.');
    }
    
    public function testCreateProjectwithApiTokenWithInvalidCharacter()
    {
        #----------------------------------
        # API token with invalid character
        #----------------------------------
        $exceptionCaught = false;
        try {
            $project = new RedCapProject(self::$config['api.url'], '1234567890123456789012345678901G');
        } catch (PhpCapException $exception) {
            $exceptionCaught = true;
            $this->assertEquals(
                PhpCapException::INVALID_ARGUMENT,
                $exception->getCode(),
                'API token with invalid character exception code check.'
            );
        }
        $this->assertTrue($exceptionCaught, 'API token with invalid character exception caught.');
    }
    
    public function testCreateProjectWithApiTokenWithIncorrectLength()
    {
        #----------------------------------
        # API token with incorrect length
        #----------------------------------
        $exceptionCaught = false;
        try {
            $project = new RedCapProject(self::$config['api.url'], '1234567890123456789012345678901');
        } catch (PhpCapException $exception) {
            $exceptionCaught = true;
            $this->assertEquals(
                PhpCapException::INVALID_ARGUMENT,
                $exception->getCode(),
                'API token with incorrect length exception code check.'
            );
        }
        $this->assertTrue($exceptionCaught, 'API token with incorrect length exception caught.');
    }
    
    public function testCreateProjectWithSslVerifyWithInvalidType()
    {
        #----------------------------------
        # SSL verify with invalid type
        #----------------------------------
        $exceptionCaught = false;
        try {
            $project = new RedCapProject(self::$config['api.url'], '12345678901234567890123456789012', 123);
        } catch (PhpCapException $exception) {
            $exceptionCaught = true;
            $this->assertEquals(
                PhpCapException::INVALID_ARGUMENT,
                $exception->getCode(),
                'SSL verify with wrong type exception code check.'
            );
        }
        $this->assertTrue($exceptionCaught, 'SSL verify with wrong type exception caught.');
    }
    
    public function testCreateProjectWithCaCertificateFileWithInvalidType()
    {
        #--------------------------------------
        # CA certificate file with invalid type
        #--------------------------------------
        $exceptionCaught = false;
        try {
            $project = new RedCapProject(self::$config['api.url'], '12345678901234567890123456789012', true, 123);
        } catch (PhpCapException $exception) {
            $exceptionCaught = true;
            $this->assertEquals(
                PhpCapException::INVALID_ARGUMENT,
                $exception->getCode(),
                'CA certificate file with wrong type exception code check.'
            );
        }
        $this->assertTrue($exceptionCaught, 'CA certificate file with wrong type exception caught.');
    }

  
    public function testExportProjectInfo()
    {
        $callInfo = true;
        $result = self::$basicDemographyProject->exportProjectInfo();
        
        $this->assertEquals($result['project_language'], 'English', 'Project info "project_language" test.');
    }

    
    public function testExportRedcapVersion()
    {
        $result = self::$basicDemographyProject->exportRedcapVersion();
        $this->assertRegExp('/^[0-9]+\.[0-9]+\.[0-9]+$/', $result, 'REDCap version format test.');
    }
}
