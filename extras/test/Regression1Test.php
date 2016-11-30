<?php

require_once('PHPUnit/Autoload.php');

require_once('SampleNotifier.php');
require_once('PhpCap.php');

class Regression1Test extends PHPUnit_Framework_TestCase {

   public function testRegression1 () {

     $test_name = 'Regression Test 1';

     $from_email = 'aarenson@iu.edu';
     $subject_token = '['.$test_name.'] Error';
     $redcap_url = 'https://redcap.uits.iu.edu/api/';
     $api_token = 'C5477C91801C64BB1C0BD65DEDB6C354';
     $primary_key = 'record_id';

     // Create factory
     $app = $test_name;
     $notifier = new SampleNotifier($from_email,$from_email,$subject_token);
     $apifactory = new REDCapFactory($app,$redcap_url,$notifier);
     $project = $apifactory->make_project($api_token,$primary_key);
      
     // Get results
     $fields_filter = array(
			    'dropdown_numeric' => '2',
			    'dropdown_character' => 'b',
			    'dropdown_mixed' => 'b_2'
			    );
			    
     $results = 
       $project->get_records_by_fields("Error getting records by fields",
				       $fields_filter);
				       

     // Were the expected number of records retrieved?
     $records_found = count($results);
     $this->assertEquals(4, $records_found);

   }

}

?>