<?php

// SampleConnection ties business logic to data storage using REDCapAPI.php
//
//==========================================================================

//-------------------------------------------------------------------------
// Classes
//-------------------------------------------------------------------------
require_once('SampleNotifier.php');
require_once('REDCapAPI.php');

//-------------------------------------------------------------------------
// Environment
//-------------------------------------------------------------------------

// n/a

//-----------------------------------------------------------------------
// Constants
//
// TO USE THIS CLASS, configure these constants for your REDCap project
//
// -- Choose a REDCap project for which you have or can get an API token
// -- Change FROM_EMAIL to your email address
// -- Change REDCAP_URL to your REDCap instance's URL
// -- Change API_TOKEN to your token
// -- CHANGE PRIMARY_KEY to be the name of the variable/field that holds
//    the unique value for your each record in your REDCap project.
//
//-----------------------------------------------------------------------
// What to use as From of emails
define("FROM_EMAIL",  '<admin@somewhere.edu>');

// What to use for Subject of emails
define("SUBJECT_TOKEN",  '[API Project] Error');

// URL of the instance of REDCap we're using
define("REDCAP_URL", 'http://redcap.somewhere.edu/api/');

// Token to use for REDCap project
define("API_TOKEN", 'ABCDEFGHIJKLMNOPQRSTUVWXYZ123456');

// Name of field used as primary key in the REDCap project
define("PRIMARY_KEY", 'record_id');

//
class SampleConnection {

  protected $project;

  // app: The filename being invoked
  function __construct($app) {

    // app represents just the name of the executable, for logging
    $app = basename(isset($app) ? $app : __FILE__, '.php');

    // Create a Notifier for sending emails if there is a problem w/API
    $notifier = new SampleNotifier(FROM_EMAIL, FROM_EMAIL, SUBJECT_TOKEN);

    // Create a REDCapFactory representing our REDCap instance
    $apifactory = new REDCapFactory($app, REDCAP_URL, $notifier);

    $this->project = $apifactory->make_project(API_TOKEN, PRIMARY_KEY);

    return true;
  }
  // END __construct
  //-------------------------------------------------------------------------

  //-------------------------------------------------------------------------
  //
  // get_lowest_subject
  //
  // Assumes that subjects have a primary key that is numeric.
  //
  // Returns all of the values for the record with the lowest id.
  //
  public function get_lowest_subject() {

    // Get primary keys for all records in this project
    // NOTE: get_ids_all would be better here, but is not yet implemented
    $primary_keys = $this->get_partials_all('Failed to get partials',
					    array($this->project->primary));

    // Determine the lowest subject
    $sorted_primary_keys = sort($primary_keys);
    $cur_id = $sorted_primary_keys[0];

    // Get all values for the lowest subject
    $records = $this->get_records_by_ids('Failed to get records',
					 array($cur_id));

    return $record[0];
  }

}

?>

