<?php

// sample_advauth.php -- A web-executed program that is called via
//                       a REDCap advanced authorization project bookmark
//                       and uses PhpCap.php via the SampleConnection 
//                       class.
//
// To use this program:
//
// -- Copy sample_advauth.php, SampleConnection.php, SampleNotifier.php,
//    PhpCap.php, and RestCallRequest.php to a directory that can be
//    served by a web server.
// -- Choose a REDCap project for which you have an API token with
//    export privileges.
// -- Configure SampleConnection.php with appropriate URL, Token, and email
//    parameters.
// -- Configure the REDCap project to have a project bookmark that uses
//    advanced authentication to call this program.
// -- Navigate to a REDCap record and click on the project bookmark.
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
class SampleAdvAuth extends SampleConnection {

  protected $record_id;
  protected $authkey;

  protected $username;

  protected $main_html;
  protected $bottom_html;

  function __construct($app) {

    // Parent constructor
    parent::__construct($app);

    //-------------------------------------------------------------------------
    // Read in passed values
    //-------------------------------------------------------------------------
    // Record_id is passed in the GET variable 'record'.
    // REDCap passes 'authkey' as a POST variable. This program then uses
    // authkey to request of REDCap which authorized user invoked this
    // program, if any.

    $record = isset($_GET['record']) ? 
      htmlspecialchars($_GET['record']) : '';

    $authkey = isset($_POST['authkey']) ? 
      htmlspecialchars($_POST['authkey']) : '';

    //------------------------------------------------------------------------
    // Initialize variables
    //------------------------------------------------------------------------
    $this->record_id = $record;
    $this->authkey = $authkey;
    $this->username = '';

    $this->main_html = '';
    $this->bottom_html = '';


    //-------------------------------------------------------------------------
    // Authenticate
    //-------------------------------------------------------------------------

    // Use REDCap's Advanced Bookmark. REDCap sends a POST variable,'authkey'.
    // This program sends that authkey back to the REDCap API,
    // and if it is a valid key for a user that is still logged
    // in to REDCap, REDCap sends back the username. 

    // This program doesn't care _which_ user is authenticated,
    // as any user that could authenticate this way is authorized.
    $body_array = $this->project->check_advanced_link_auth($this->authkey);

    if (is_array($body_array) && isset($body_array['username'])) {
      $this->username = $body_array['username'];
    }

    //------------------------------------------------------------------------
    // Pageheader
    //------------------------------------------------------------------------
    $this->pageheader();

    //-------------------------------------------------------------------------
    // Validate user is authorized
    //-------------------------------------------------------------------------

    if (empty($this->username)) {

      $this->bottom_html.= 
	"Unable to validate that you have an active REDCap session and are authorized for this project.\n";
    }

    // User is authorized
    else {
      $this->main_html = $this->create_maincontent();
      
    }

    //-------------------------------------------------------------------------
    // Show main content
    //-------------------------------------------------------------------------
    $this->show_maincontent();
    
    //------------------------------------------------------------------------
    // Pagefooter
    //------------------------------------------------------------------------
    $this->pagefooter();

  }

  //===========================================================================
  // Helper Functions
  //===========================================================================

  //-------------------------------------------------------------------------
  // pageheader
  //
  protected function pageheader() {

    ?>
    <html>
      <head>
        <link rel="stylesheet" type="text/css" href="sample.css" 
              media="screen" />
      </head>

      <body bgcolor=white>

        <div id="header">
          Sample using Advanced Authorization project bookmark with REDCapAPI
        </div>

        <div id="maincontent">

      <?php
    return true;
  }
  // END pageheader
  //-------------------------------------------------------------------------


  //-------------------------------------------------------------------------
  // pagefooter
  //
  protected function pagefooter() {

    ?>

    </div> <!-- maincontent -->

    <div id="bottombar">&nbsp</div>

    </body></html>
    <?php

    return true;
  }
  // END pagefooter
  //-------------------------------------------------------------------------


  //-------------------------------------------------------------------------
  // show_maincontent
  //
  protected function show_maincontent() {

    ?>
    <table id="maintable">
      <tr><td><?php echo $this->main_html; ?></td></tr>
      <tr><td><?php echo $this->bottom_html; ?></td></tr>
    </table>
    <?php

    return true;
  }
  // END show_maincontent
  //-------------------------------------------------------------------------


  //-------------------------------------------------------------------------
  // create_main_content
  //
  protected function create_maincontent() {

    $html = '';

    $record = $this->get_lowest_subject();

    $html .= "<h1>Authorized user: ".$this->username."</h1>\n";

    $html .= "<pre>\n";
    $html .= print_r($record,true);
    $html .= "</pre>\n";

    return $html;
  }
  // END create_main_content
  //-------------------------------------------------------------------------


}
// END of class SampleAdvAuth
//-------------------------------------------------------------------------

new SampleAdvAuth(__FILE__);

?>
