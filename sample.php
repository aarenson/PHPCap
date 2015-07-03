<?php

// sample.php -- A command-line executed program that uses PhpCap.php
//               via the SampleConnection class.
//
// To use this program:
//
// -- Configure SampleConnection.php with appropriate URL, Token, and email
//    parameters.
// -- Execute sample.php on the comman line with: 'php sample.php'
//
//========================================================================= 

//--------------------------------------------------------------------------
// Required libraries
//--------------------------------------------------------------------------
require_once('SampleConnection.php');

//--------------------------------------------------------------------------
// Hard-coded Configurations
//--------------------------------------------------------------------------
// n/a

//-------------------------------------------------------------------------
// Constants
//-------------------------------------------------------------------------
// n/a

//
class Sample extends SampleConnection {

  function __construct($app) {

    // Parent constructor
    parent::__construct($app);

    $record = $this->get_lowest_subject();

    print_r($record);

    return(1);
  }

}
// END of class Sample
//-------------------------------------------------------------------------

new Sample(__FILE__);

?>
