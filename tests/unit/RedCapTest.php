<?php

namespace IU\PHPCap;

use PHPUnit\Framework\TestCase;
use IU\PHPCap\PhpCapException;

class RedCapTest extends TestCase
{
 
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
}
