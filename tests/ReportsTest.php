<?php

namespace IU\PHPCap;

use PHPUnit\Framework\TestCase;

use IU\PHPCap\RedCapProject;

/**
 * PHPUnit tests for reports for the RedCapProject class.
 */
class ReportsTest extends TestCase
{
    private static $config;
    private static $basicDemographyProject;
    private static $longitudinalDataProject;
    
    public static function setUpBeforeClass()
    {
        self::$config = parse_ini_file('config.ini');
        self::$basicDemographyProject = new RedCapProject(
            self::$config['api.url'],
            self::$config['basic.demography.api.token']
        );
        self::$longitudinalDataProject = new RedCapProject(
            self::$config['api.url'],
            self::$config['longitudinal.data.api.token']
        );
    }
    
    public function testExportReportsWithNullReportId()
    {
        $exceptionCaught = false;
        try {
            $result = self::$longitudinalDataProject->exportReports($reportId = null);
        } catch (PhpCapException $exception) {
            $code = $exception->getCode();
            $this->assertEquals(PhpCapException::INVALID_ARGUMENT, $code, 'Invalid argument check.');
            $exceptionCaught = true;
        }
        $this->assertTrue($exceptionCaught, 'Exception caught.');
    }
    

    public function testExportReportsWithReportIdWithInvalidType()
    {
        $exceptionCaught = false;
        try {
            $result = self::$longitudinalDataProject->exportReports($reportId = true);
        } catch (PhpCapException $exception) {
            $code = $exception->getCode();
            $this->assertEquals(PhpCapException::INVALID_ARGUMENT, $code, 'Invalid argument check.');
            $exceptionCaught = true;
        }
        $this->assertTrue($exceptionCaught, 'Exception caught.');
    }
    

    public function testExportReportsWithInvalidStringReportId()
    {
        $exceptionCaught = false;
        try {
            $result = self::$longitudinalDataProject->exportReports($reportId = 'abc');
        } catch (PhpCapException $exception) {
            $code = $exception->getCode();
            $this->assertEquals(PhpCapException::INVALID_ARGUMENT, $code, 'Invalid argument check.');
            $exceptionCaught = true;
        }
        $this->assertTrue($exceptionCaught, 'Exception caught.');
    }
    

    public function testExportReportsWithInvalidIntegerReportId()
    {
        $exceptionCaught = false;
        try {
            $result = self::$longitudinalDataProject->exportReports($reportId = -100);
        } catch (PhpCapException $exception) {
            $code = $exception->getCode();
            $this->assertEquals(PhpCapException::INVALID_ARGUMENT, $code, 'Invalid argument check.');
            $exceptionCaught = true;
        }
        $this->assertTrue($exceptionCaught, 'Exception caught.');
    }
}
