<?php

namespace IU\PHPCap;

use PHPUnit\Framework\TestCase;

use IU\PHPCap\SystemFunctions;

/**
 * PHPUnit tests for external files for the RedCapProject class.
 */
class ExternalFilesTest extends TestCase
{
    
    public static function setUpBeforeClass()
    {
    }
    
    public function testFileReadAndWrite()
    {
        $content = RedCapProject::fileToString(__DIR__."/data/file.txt");
        $this->assertEquals($content, "Test data file.", 'file.txt content match.');
        
        $outputFile = __DIR__."/data/output.txt";
        $text1 = "This is a test.";
        RedCapProject::writeStringToFile($text1, $outputFile);
        $content = RedCapProject::fileToString($outputFile);
        $this->assertEquals($content, $text1, 'String write check.');
        
        $text2 = " Another test.";
        RedCapProject::appendStringToFile($text2, $outputFile);
        $content = RedCapProject::fileToString($outputFile);
        $this->assertEquals($content, $text1 . $text2, 'String append check.');
    }
    
    public function testFileToStringWithNonExistantFile()
    {
        $exceptionCaught = false;
        try {
            $content = RedCapProject::fileToString(__DIR__.'/data/'.uniqid().'.txt');
        } catch (PhpCapException $exception) {
            $code = $exception->getCode();
            $this->assertEquals(PhpCapException::INPUT_FILE_NOT_FOUND, $code, 'Exception code check.');
            $exceptionCaught = true;
        }
        $this->assertTrue($exceptionCaught, 'Exception caught.');
    }
    
    public function testFileToStringWithUnreadableFile()
    {
        $exceptionCaught = false;
        SystemFunctions::setIsReadableToFail();
        try {
            $content = RedCapProject::fileToString(__DIR__."/data/file.txt");
        } catch (PhpCapException $exception) {
            $code = $exception->getCode();
            $this->assertEquals(PhpCapException::INPUT_FILE_UNREADABLE, $code, 'Exception code check.');
            $exceptionCaught = true;
        }
        $this->assertTrue($exceptionCaught, 'Exception caught.');
        SystemFunctions::resetIsReadable();
    }
    
    public function testFileToStringWithSystemFileError()
    {
        $exceptionCaught = false;
        SystemFunctions::setFileGetContentsToFail();
        $error = ['message' => 'System file error.'];
        SystemFunctions::setErrorGetLast($error);
        try {
            $content = RedCapProject::fileToString(__DIR__."/data/file.txt");
        } catch (PhpCapException $exception) {
            $code = $exception->getCode();
            $this->assertEquals(PhpCapException::INPUT_FILE_ERROR, $code, 'Exception code check.');
            $exceptionCaught = true;
        }
        $this->assertTrue($exceptionCaught, 'Exception caught.');
        SystemFunctions::resetFileGetContents();
        SystemFunctions::resetErrorGetLast();
    }
    
    
    public function testFileToStringWithUnkownSystemFileError()
    {
        $exceptionCaught = false;
        SystemFunctions::setFileGetContentsToFail();
        try {
            $content = RedCapProject::fileToString(__DIR__."/data/file.txt");
        } catch (PhpCapException $exception) {
            $code = $exception->getCode();
            $this->assertEquals(PhpCapException::INPUT_FILE_ERROR, $code, 'Exception code check.');
            $exceptionCaught = true;
        }
        $this->assertTrue($exceptionCaught, 'Exception caught.');
        SystemFunctions::resetFileGetContents();
    }
    

    public function testWriteStringToFileWithSystemFileError()
    {
        $exceptionCaught = false;
        SystemFunctions::setFilePutContentsToFail();
        $error = ['message' => 'System file error.'];
        SystemFunctions::setErrorGetLast($error);
        try {
            RedCapProject::writeStringToFile("test", __DIR__."/data/output.txt");
        } catch (PhpCapException $exception) {
            $code = $exception->getCode();
            $this->assertEquals(PhpCapException::OUTPUT_FILE_ERROR, $code, 'Exception code check.');
            $exceptionCaught = true;
        }
        $this->assertTrue($exceptionCaught, 'Exception caught.');
        SystemFunctions::resetFilePutContents();
        SystemFunctions::resetErrorGetLast();
    }

    public function testWriteStringToFileWithUnkownSystemFileError()
    {
        $exceptionCaught = false;
        SystemFunctions::setFilePutContentsToFail();
        try {
            RedCapProject::writeStringToFile("test", __DIR__."/data/output.txt");
        } catch (PhpCapException $exception) {
            $code = $exception->getCode();
            $this->assertEquals(PhpCapException::OUTPUT_FILE_ERROR, $code, 'Exception code check.');
            $exceptionCaught = true;
        }
        $this->assertTrue($exceptionCaught, 'Exception caught.');
        SystemFunctions::resetFilePutContents();
    }
}
