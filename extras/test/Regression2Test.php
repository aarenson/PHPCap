<?php

require_once('PHPUnit/Autoload.php');

require_once('SampleNotifier.php');
require_once('PhpCap.php');

class Regression2Test extends PHPUnit_Framework_TestCase {

   public function testRegression1 () {

     $test_name = 'Regression Test 2';

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
     $error = "Error in prep_records_by_ids_batch";
     $batch_size = 2;
     $num_records_to_retrieve = 10;
     $record_ids = range(1,$num_records_to_retrieve);
     $project->prep_records_by_ids_batch($error,$batch_size,$record_ids);

     $record_events_retrieved = 0;

     while($batch = $project->get_records_by_ids_batch()) {
       foreach($batch as $id => $record_events) {
	 $record_events_retrieved += count($record_events);
       }
     }
				       

     // Were the expected number of records retrieved?
     // Since this isn't a longitudinal project, we expect only one
     // event/record.
     $this->assertEquals($num_records_to_retrieve, $record_events_retrieved);
   }

}

?>