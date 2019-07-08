<?php

namespace IU\PHPCap;

use PHPUnit\Framework\TestCase;

/**
 * PHPUnit tests for ErrorHandler class.
 */
class ErrorHandlerTest extends TestCase
{
    public function test()
    {
        $errorHandler = new ErrorHandler();
        $this->assertNotNull($errorHandler, 'Error handler not null.');
        $this->assertTrue($errorHandler instanceof ErrorHandlerInterface);

        $exceptionCaught = false;
        $expectedMessage = 'Error handler test.';
        $expectedCode = ErrorHandlerInterface::REDCAP_API_ERROR;
        try {
            $errorHandler->throwException($expectedMessage, $expectedCode);
        } catch (\Exception $exception) {
            $exceptionCaught = true;
            $message = $exception->getMessage();
            $code = $exception->getCode();
        }

        $this->assertTrue($exceptionCaught);
        $this->assertEquals($expectedMessage, $message);
        $this->assertEquals($expectedCode, $code);
    }
}
