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
}
