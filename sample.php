<?php

// sample.php -- A command-line executed program that uses REDCapAPI.php
//               via the SampleConnection class.
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
