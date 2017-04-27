<?php

use PHPUnit\Framework\TestCase;
use IU\PHPCap\PhpCapException;

class PhpCapExceptionTest extends TestCase {
    
    public function testInvalidArgument()
    {
        $message = 'Argument has wrong type.';
        $code    = PhpCapException::INVALID_ARGUMENT;
        
        $exceptionCaught = false;
        
        try {
            throw new PhpCapException($message, $code);
        }
        catch (PhpCapException $exception) {
            $exceptionCaught = true;
            $this->assertEquals($exception->getMessage(), $message, 'Message matches.');
            $this->assertEquals($exception->getCode(), $code, 'Code matches.');
        }
        
        $this->assertTrue($exceptionCaught, 'Exception caught.');
    }
    
    public function testCurlError()
    {
        $message         = 'Unsupported protocol';
        $code            = PhpCapException::CURL_ERROR;
        $curlErrorNumber = 1;

        $exceptionCaught = false;
        
        try {
            throw new PhpCapException($message, $code, $curlErrorNumber);
        }
        catch (PhpCapException $exception) {
            $exceptionCaught = true;
            $this->assertEquals($exception->getMessage(), $message, 'Message matches.');
            $this->assertEquals($exception->getCode(), $code, 'Code matches.');
            $this->assertEquals($exception->getCurlErrorNumber(), $curlErrorNumber, 'Curl error number matches.');
        }
        
        $this->assertTrue($exceptionCaught, 'Exception caught.');
    }
    
    public function testHttpError()
    {
        $message = "Invalid URL.";
        $code           = 
        $httpStatusCode = 404;
        
        $exceptionCaught = false;
        
        try {
            throw new PhpCapException($message, $code, null /* cURL error number */, $httpStatusCode);
        }
        catch (PHPCapException $exception) {
            $exceptionCaught = true;
            $this->assertEquals($exception->getMessage(), $message, 'Message matches.');
            $this->assertEquals($exception->getCode(), $code, 'Code matches.');
            $this->assertNull($exception->getCurlErrorNumber());
            $this->assertEquals($exception->getHttpStatusCode(), $httpStatusCode, 'HTTP status code matches.');
        }

        $this->assertTrue($exceptionCaught, 'Exception caught.');
    }

}
