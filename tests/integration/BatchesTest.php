<?php

namespace IU\PHPCap;

use PHPUnit\Framework\TestCase;

use IU\PHPCap\RedCapProject;
use IU\PHPCap\PhpCapException;

/**
 * PHPUnit integration tests batch processing.
 */
class BatchesTest extends TestCase
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
    
    public function testExportWithBatches()
    {
        $expectedResult = self::$longitudinalDataProject->exportRecords($format = 'csv');

        $recordIdBatches = self::$longitudinalDataProject->getRecordIdBatches(10);
        
        $result = '';
        $isFirst = true;
        foreach ($recordIdBatches as $recordIdBatch) {
            $records = self::$longitudinalDataProject->exportRecordsAp(
                ['format' => 'csv', 'recordIds' => $recordIdBatch]
            );

            if ($isFirst) {
                $result .= $records;
                $isFirst = false;
            } else {
                # delete off the header line for all except
                # the first batch.
                $result .= substr($records, strpos($records, "\n") + 1);
            }
        }
        $this->assertEquals($expectedResult, $result, 'Batch result check.');
    }

    public function testExportWithBatchesWithFilterLogic()
    {
        $expectedResult = self::$longitudinalDataProject->exportRecordsAp(
            ['format' => 'csv', 'filterLogic' => '[age] >= 60']
        );
        
        $recordIdBatches = self::$longitudinalDataProject->getRecordIdBatches(10, '[age] >= 60');
        
        $result = '';
        $isFirst = true;
        foreach ($recordIdBatches as $recordIdBatch) {
            # Need to repeat filterLogic, because otherwise all records for
            # people with age >= 60 will be returned, instead of all
            # records with age (defined and) >= 60 being returned
            $records = self::$longitudinalDataProject->exportRecordsAp(
                ['format' => 'csv', 'recordIds' => $recordIdBatch, 'filterLogic' => '[age] >= 60']
            );
            
            if ($isFirst) {
                $result .= $records;
                $isFirst = false;
            } else {
                # delete off the header line for all except
                # the first batch.
                $result .= substr($records, strpos($records, "\n") + 1);
            }
        }

        $this->assertEquals($expectedResult, $result, 'Batch result check.');
    }
    
    
    
    public function testNullBatches()
    {
        $caughtException = false;
        try {
            $recordIdBatches = self::$longitudinalDataProject->getRecordIdBatches(null);
        } catch (PhpCapException $exception) {
            $caughtException = true;
            $code = $exception->getCode();
            $this->assertEquals(PhpCapException::INVALID_ARGUMENT, $code, 'Exception code check.');
        }
        
        $this->assertTrue($caughtException, 'Exception caught check.');
    }
    
    
    public function testNonIntegerBatches()
    {
        $caughtException = false;
        try {
            $recordIdBatches = self::$longitudinalDataProject->getRecordIdBatches("two");
        } catch (PhpCapException $exception) {
            $caughtException = true;
            $code = $exception->getCode();
            $this->assertEquals(PhpCapException::INVALID_ARGUMENT, $code, 'Exception code check.');
        }
        
        $this->assertTrue($caughtException, 'Exception caught check.');
    }
    
    public function testZeroBatches()
    {
        $caughtException = false;
        try {
            $recordIdBatches = self::$longitudinalDataProject->getRecordIdBatches(0);
        } catch (PhpCapException $exception) {
            $caughtException = true;
            $code = $exception->getCode();
            $this->assertEquals(PhpCapException::INVALID_ARGUMENT, $code, 'Exception code check.');
        }
        
        $this->assertTrue($caughtException, 'Exception caught check.');
    }
}
