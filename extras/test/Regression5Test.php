<?php

require_once('extras/test/PhpCap_TestCase.php');

class Regression5Test extends PhpCap_TestCase {

   public function testRegression5 () {

     // Get results
     $id = $this->proj->get_project_id("Unable to get project id");

     // Was the expected project id returned
     $this->assertEquals(PhpCap_TestCase::PROJECT_ID, $id);

   }

}

?>