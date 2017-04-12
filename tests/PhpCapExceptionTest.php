<?php

require_once(__DIR__.'/../src/PhpCapException.php');

use PHPUnit\Framework\TestCase;
use IU\PHPCap\PhpCapException;

class PhpCapExceptionTest extends TestCase {
    
    public function testInvalidArgument()
    {
        $message = 'Argument has wrong type.';
        $code    = PhpCapException::INVALID_ARGUMENT;
        
        try {
            throw new PHPCapException($message, $code);
        }
        catch (PHPCapException $exception) {
            $this->assertEquals($exception->getMessage(), $message, 'Message matches.');
            $this->assertEquals($exception->getCode(), $code, 'Code matches.');
        }
    }
    
    public function testCurlError()
    {
        $message         = 'Unsupported protocol';
        $code            = PhpCapException::CURL_ERROR;
        $curlErrorNumber = 1;
    
        try {
            throw new PHPCapException($message, $code, $curlErrorNumber);
        }
        catch (PHPCapException $exception) {
            $this->assertEquals($exception->getMessage(), $message, 'Message matches.');
            $this->assertEquals($exception->getCode(), $code, 'Code matches.');
            $this->assertEquals($exception->getCurlErrorNumber(), $curlErrorNumber, 'Curl error number matches.');
        }
    }

}
