<?php

require_once('extras/test/PhpCap_TestCase.php');

class Regression2Test extends PhpCap_TestCase {

   public function testRegression2 () {

     // Get results
     $error = "Error in prep_records_by_ids_batch";
     $batch_size = 2;
     $num_records_to_retrieve = 10;
     $record_ids = range(1,$num_records_to_retrieve);
     $this->proj->prep_records_by_ids_batch($error,$batch_size,$record_ids);

     $record_events_retrieved = 0;

     while($batch = $this->proj->get_records_by_ids_batch()) {
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