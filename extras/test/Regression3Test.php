<?php

require_once('extras/test/PhpCap_TestCase.php');

class Regression3Test extends PhpCap_TestCase {

   public function testRegression3 () {

     // Get results
     $choices = $this->proj->get_lookup_choices();

     // Were the expected number of categories returned?
     $this->assertEquals(5, count(array_keys($choices)));

     // Were the expected number of labels returned for dropdown_numeric?
     $this->assertEquals(3, count($choices['dropdown_numeric']));
   }

}

?>