<?php

namespace IU\PHPCap;

use PHPUnit\Framework\TestCase;

use IU\PHPCap\RedCapProject;

/**
 * PHPUnit tests for using the underlying connection for the RedCapProject class.
 */
class ConnectionsTest extends TestCase
{
    private static $config;
    private static $basicDemographyProject;
    private static $longitudinalDataProject;
    
    public static function setUpBeforeClass()
    {
        self::$config = parse_ini_file(__DIR__.'/../config.ini');
        self::$basicDemographyProject = new RedCapProject(
            self::$config['api.url'],
            self::$config['basic.demography.api.token']
        );
        self::$longitudinalDataProject = new RedCapProject(
            self::$config['api.url'],
            self::$config['longitudinal.data.api.token']
        );
    }
    
    public function testConnection()
    {
        $project = self::$basicDemographyProject;
        $connection = $project->getConnection();
        $this->assertNotNull($connection);
        
        $connection->setTimeoutInSeconds(10);
        $timeout = $connection->getTimeoutInSeconds();
        
        $this->assertEquals(10, $timeout, 'Connection timeout check.');
        
        $project->setTimeoutInSeconds(10);
        $timeout = $project->getTimeoutInSeconds();
        
        $this->assertEquals(10, $timeout, 'Project timeout check.');
    }
}
