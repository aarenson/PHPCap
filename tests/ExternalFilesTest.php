<?php

namespace IU\PHPCap;

use PHPUnit\Framework\TestCase;

use IU\PHPCap\RedCapProject;

/**
 * PHPUnit tests for files for the RedCapProject class.
 */
class ExternalFilesTest extends TestCase
{
    
    public static function setUpBeforeClass()
    {
        ;
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
}
