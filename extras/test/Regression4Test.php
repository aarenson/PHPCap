<?php

require_once('extras/test/PhpCap_TestCase.php');

class Regression4Test extends PhpCap_TestCase {

   public function testRegression4 () {

     // Get results
     $names = $this->proj->get_fieldnames();

     // Were the expected number of fields returned?
     // NOTES:
     //        * One field for each checkbox choice
     //        * File Upload and Signature fields don't count
     //        * An extra field for 'my_first_instrument_complete'
     $this->assertEquals(30, count(array_keys($names)));

   }

}

?>