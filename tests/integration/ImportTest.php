<?php

namespace IU\PHPCap;

use PHPUnit\Framework\TestCase;

use IU\PHPCap\RedCapProject;

/**
 * PHPUnit integrations tests for import methods.
 */
class ImportTest extends TestCase
{
    private static $config;
    private static $emptyProject;
    
    public static function setUpBeforeClass()
    {
        self::$config = parse_ini_file(__DIR__.'/../config.ini');
        self::$emptyProject = new RedCapProject(
            self::$config['api.url'],
            self::$config['empty.project.api.token']
        );
    }

    public function testImports()
    {
        $projectInfo = [
            'project_irb_number' => '',
            'is_longitudinal' => 0
        ];
        
        $count = self::$emptyProject->importProjectInfo($projectInfo, $format = 'php');
        
        $this->assertEquals(2, $count, 'Project info value updates check.');
        
        $result = self::$emptyProject->exportProjectInfo();
                
        $this->assertEquals(
            $projectInfo['project_irb_number'],
            $result['project_irb_number'],
            'IRB number check.'
        );
        
        $this->assertEquals(
            $projectInfo['is_longitudinal'],
            $result['is_longitudinal'],
            'Is longitudinal check.'
        );
    }
}
