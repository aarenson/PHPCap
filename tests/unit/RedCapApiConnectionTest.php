<?php

namespace IU\PHPCap;

use PHPUnit\Framework\TestCase;
use IU\PHPCap\PhpCapException;

class RedCapApiConnectionTest extends TestCase
{
    private $apiUrl;
    
    public function setUp()
    {
        $this->apiUrl = 'https://redcap.someplace.edu/api/';
    }
    
    public function testCaCertificateFileNotFound()
    {
        $caughtException = false;
        try {
            $apiConnection = new RedCapApiConnection($this->apiUrl, true, uniqid().".txt");
        } catch (PhpCapException $exception) {
            $caughtException = true;
            $this->assertEquals(
                $exception->getCode(),
                ErrorHandlerInterface::CA_CERTIFICATE_FILE_NOT_FOUND,
                'CA cert file not found.'
            );
        }
        $this->assertTrue($caughtException, 'Caught CA cert file not found exception.');
    }
    
    public function testCaCertificateFileUnreadable()
    {
        SystemFunctions::setIsReadableToFail();
        $caughtException = false;
        try {
            $apiConnection = new RedCapApiConnection($this->apiUrl, true, __FILE__);
        } catch (PhpCapException $exception) {
            $caughtException = true;
            $this->assertEquals(
                $exception->getCode(),
                ErrorHandlerInterface::CA_CERTIFICATE_FILE_UNREADABLE,
                'CA cert file is unreadable.'
            );
        }
        $this->assertTrue($caughtException, 'Caught CA cert file unreadable exception.');
        SystemFunctions::resetIsReadable();
    }
    
    
    public function testCurlErrorWithNoMessage()
    {
        $stringError = 'Peer certificate cannot be authenticated with given CA certificates 6';
        
        SystemFunctions::setCurlErrorInfo($number  = 60, $message = '', $stringError);
        
        $caughtException = false;
        try {
            $apiConnection = new RedCapApiConnection($this->apiUrl);
            $apiConnection->call('data');
        } catch (PhpCapException $exception) {
            $caughtException = true;
            $this->assertEquals(
                $exception->getCode(),
                ErrorHandlerInterface::CONNECTION_ERROR,
                'Exception code check.'
            );
            $this->assertEquals($stringError, $exception->getMessage(), 'Message check.');
        }
        $this->assertTrue($caughtException, 'Caught exception.');
        SystemFunctions::setCurlErrorInfo(0, '', '');
    }
    
    public function testCurlErrorWithNoMessageOrMessageString()
    {
        SystemFunctions::setCurlErrorInfo($number = 60, $message = '', $stringError = null);
        
        $caughtException = false;
        try {
            $apiConnection = new RedCapApiConnection($this->apiUrl);
            $apiConnection->call('data');
        } catch (PhpCapException $exception) {
            $caughtException = true;
            $code = $exception->getCode();
            $this->assertEquals(
                ErrorHandlerInterface::CONNECTION_ERROR,
                $code,
                'Exception code check.'
            );
            # The error code should be contained in the error message
            $this->assertContains(strval($code), $exception->getMessage(), 'Message check.');
        }
        $this->assertTrue($caughtException, 'Caught exception.');
        SystemFunctions::setCurlErrorInfo(0, '', '');
    }
}
