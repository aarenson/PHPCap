<?php

namespace IU\PHPCap;

use PHPUnit\Framework\TestCase;
use IU\PHPCap\PhpCapException;

class RedCapProjectTest extends TestCase
{
    
    public function testCreateProjectWithNonStringApiUrl()
    {
        #------------------------------
        # Non-string API URL
        #------------------------------
        $exceptionCaught = false;
        try {
            $project = new RedCapProject(123, '12345678901234567890123456789012');
        } catch (PhpCapException $exception) {
            $exceptionCaught = true;
            $this->assertEquals(PhpCapException::INVALID_ARGUMENT, $exception->getCode(), 'Exception code check.');
            $this->assertContains('integer', $exception->getMessage(), 'Message content check.');
        }
        $this->assertTrue($exceptionCaught, 'Exception caught.');
    }
    
    
    public function testCreateProjectWithNullApiToken()
    {
        #----------------------------------
        # Null API token
        #----------------------------------
        $exceptionCaught = false;
        try {
            $project = new RedCapProject('https://redcap.abc.edu/api/', null);
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
}
