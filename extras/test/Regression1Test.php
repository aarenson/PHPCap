<?php

require_once('extras/test/PhpCap_TestCase.php');

class Regression1Test extends PhpCap_TestCase {

   public function testRegression1 () {

     // Get results
     $fields_filter = array(
			    'dropdown_numeric' => '2',
			    'dropdown_character' => 'b',
			    'dropdown_mixed' => 'b_2'
			    );
			    
     $results = 
       $this->proj->get_records_by_fields("Error getting records by fields",
				       $fields_filter);
				       

     // Were the expected number of records retrieved?
     $records_found = count($results);
     $this->assertEquals(4, $records_found);

   }

}

?>