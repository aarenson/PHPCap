<?php

// $Id$
//
// Connection provides methods for retrieving and storing data for
// this project.
//
// Connection knows about project-specific data such as:
//       Stored in REDCAP:
//                Calculated T-Scores
//       Stored in CAS:
//                IU Authentication
// 
//==========================================================================

//-------------------------------------------------------------------------
// Classes
//-------------------------------------------------------------------------
require_once('Notifier.php');
require_once('REDCapAPI.php');
require_once('UITSCAS.php');

//-------------------------------------------------------------------------
// Environment
//-------------------------------------------------------------------------

// n/a

//-----------------------------------------------------------------------
// Constants
//-----------------------------------------------------------------------
// What to use as From of emails
define("FROM_EMAIL",  'hubsupport@indianactsi.org');

// What to use for Subject of emails
define("SUBJECT_TOKEN",  '[SPADE Project] Error');

// CAS
define("CAS_URL",  'https://cas.iu.edu/');
// define("CAS_URL",  'https://cas-reg.iu.edu/'); // Tested with no trouble
define("CAS_APPCODE",  'IU');

// Fields used to store T Scores
define("T_FATIGUE",    't_fatigue');
define("T_PAIN",       't_pain');
define("T_SLEEP",      't_sleep');
define("T_ANXIETY",    't_anxiety');
define("T_DEPRESSION", 't_depression');
define("T_COMPOSITE",  't_composite');

// Midline of Scores
define("T_MIDLINE",    '50');

//
class Connection {

  public $cas;             // For CAS authentication
  public $notifier;        // For notifying of errors when there is no GUI

  public $debug;

  protected $app;

  protected $redcap_url;
  protected $date;

  protected $proj_spade;

  // app: The filename being invoked
  function __construct($app) {

    // If debug is set to 'yes', debugging information is written out as HTML.
    $this->debug='no';    

    // Instance
    // redcap: production redcap
    // OUT OF DATE: redcap-testing: redcap-testing vhost on gauley
    $INSTANCE_KEY = 'dev02';

    // Things to check when testing on another platform
    //    Is the Data Entry Trigger set for the new platform?
    //    Is the CTP Configuration allowed server updated for the new server?
    //    Is the instance key set correctly?
    //    Is the token set correctly in the instance key?

    $INSTANCES['dev02'] = 
      array(
	    'REDCAP_URL' => 'http://in-rtls-dev02.uits.iupui.edu/redcap/',
	    'API_TOKEN' => '7499A033DB89F4E868D4C1A93F495324',
	    'INITIAL_EMAIL_ADDRESS' => 'aarenson@iu.edu',
	    'INITIAL_EMAIL_ADDRESS_DEBUGGING' => 'aarenson@iu.edu',
	    );

    $INSTANCES['redcap'] = 
      array(
	    'REDCAP_URL' => 'https://redcap.uits.iu.edu/',
	    'API_TOKEN' => 'BE7BFD7091F9A3A6936D9A82102F3727',
	    'INITIAL_EMAIL_ADDRESS' => 'hubsupport@indianactsi.org',
	    'INITIAL_EMAIL_ADDRESS_DEBUGGING' => 'aarenson@iu.edu',
	    );
    
    $INSTANCES['redcap-testing'] = 
      array(
	    // URL of the instance of REDCap we're using
	    'REDCAP_URL' => 'https://redcap-testing.uits.iu.edu/',
	    
	    // Token to use for 'College Toolbox Project User Vault' project
	    'API_TOKEN' => '',
	    
	    // Who to notify if initial attempt to get configuration fails
	    'INITIAL_EMAIL_ADDRESS' => 'aarenson@iu.edu',
	    
	    // ADA_DEBUG -- At some point I need to think more about how to
	    //              separately set whether or not debugging info is
	    //              shown on the screen versus whether or not a 
	    //              debugging email address is used.
	    'INITIAL_EMAIL_ADDRESS_DEBUGGING' => 'aarenson@iu.edu',
	    );

    $instance = $INSTANCES[$INSTANCE_KEY];
    $this->redcap_url = $instance['REDCAP_URL'];

    $this->date = date('g:i:s a d-M-Y T');

    // Intermediate variables used for initialization
    // app is first used to represent the filename of the executable
    $cas_return = $instance['REDCAP_URL'].'apis/'.basename($app);
    $cas_params = '?cassvc='.CAS_APPCODE.'&casurl='.$cas_return;

    $this->cas = new UITSCAS(CAS_URL,$cas_params);

    // app is later used to represent just the name of the executable,
    // for logging
    $this->app = basename(isset($app) ? $app : __FILE__, '.php');

    // Create a Notifier for sending emails if there is a problem
    $this->notifier = new Notifier(FROM_EMAIL,
				   $instance['INITIAL_EMAIL_ADDRESS'],
				   SUBJECT_TOKEN);

    // Create a REDCapFactory representing our REDCap instance
    $apifactory = new REDCapFactory($this->app,
				    $instance['REDCAP_URL'].'api/',
				    $this->notifier);

    $this->proj_spade = $apifactory->make_project($instance['API_TOKEN'],
						  'record_id');

    return true;
  }
  // END __construct
  //-------------------------------------------------------------------------


}

?>

