<?php

require_once('PHPUnit/Autoload.php');

require_once('SampleNotifier.php');
require_once('PhpCap.php');

class PhpCap_TestCase extends PHPUnit_Framework_TestCase {

  // Create a REDCap Factory object representing the REDCap instance
  // to be used for testing. By making this static, only one
  // instance will be created for the entire class.
  static private $factory = null;
  
  // Create a REDCap Project object for testing. By not making this static, 
  // a new object will have to be created for each test, but that object
  // can be referenced using $this->proj. It may be more efficient to
  // reuse the object, but could potentially interfere with some tests, maybe?

  // [ADA, 30-Nov-2016: I don't understand why $proj needs to be public]
  public $proj = null;

  // Configuration -- In the future this might be read in from elsewhere
  const NAME = 'PhpCap Regression Testing';

  const EMAIL = 'aarenson@iu.edu';
  const URL = 'https://redcap.uits.iu.edu/api/';
  const TOKEN = 'C5477C91801C64BB1C0BD65DEDB6C354';
  const PRIMARY = 'record_id';
  const PROJECT_ID = 8604;

  protected function setUp() {

    if (null === $this->proj) {

      if (null === self::$factory) {

	// Create factory
	$subject = '['.self::NAME.'] Error';
	$notifier = new SampleNotifier(self::EMAIL,self::EMAIL,$subject);
	self::$factory = new REDCapFactory(self::NAME,self::URL,$notifier);

      }

      $this->proj = self::$factory->make_project(self::TOKEN,self::PRIMARY);

    }

  }

}

?>