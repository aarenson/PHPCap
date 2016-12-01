<?php

// See the NOTICE file distributed with this work for information
// regarding copyright ownership.  Trustees of Indiana University
// licenses this file to you under the Apache License, Version 2.0 (the
// "License"); you may not use this file except in compliance with the
// License.  You may obtain a copy of the License at
//
//      http://www.apache.org/licenses/LICENSE-2.0
//
// Unless required by applicable law or agreed to in writing, software
// distributed under the License is distributed on an "AS IS" BASIS,
// WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or
// implied.  See the License for the specific language governing
// permissions and limitations under the License.
//
//=========================================================================

//--------------------------------------------------------------------------
// Required libraries
//--------------------------------------------------------------------------
// n/a

//-------------------------------------------------------------------------
//
// REDCapProject is used for importing/exporting via REDCap API
//
//-------------------------------------------------------------------------
//
class REDCapProject {

  public $app = '';

  protected $api_url = '';
  protected $token = '';     // API token for a REDCap project
  protected $notifier = '';  // Object for reporting errors

  protected $metadata = array();
  protected $fieldnames = array();
  protected $exportfieldnames = array();

  // Used for storing info about batches between calls to
  // get_records_by_ids_batch
  protected $records_by_ids_batch_error = '';
  protected $records_by_ids_batch_size = 1;
  protected $records_by_ids_batch_record_ids = array();

  public $primary = '';   // The field that stores the record_id

  public function __construct($app,$api_url,$token,$notifier,$primary) {
    $this->app = $app;
    $this->api_url = $api_url;
    $this->token = $token;
    $this->notifier = $notifier;
    $this->primary = $primary;
  }

  //-------------------------------------------------------------------------
  // Design of export-related functions
  //
  // A base method is provided (get_records) that allows one to
  // supply arrays of records, fields, events, and/or forms of interest.
  // Further methods are supplied that build on get_records for
  // recurring needs.
  //
  // For these further methods, it was assumed that one never wants
  // to specify events or forms of interest, but that various combinations
  // of wanting to specify either records or fields might be wanted.
  //
  // A matrix of three ways one might want to specify which records to
  // retrieve crossed with three ways one might want to specify which fields
  // should be returned was considered:
  //
  //   * Which records: All, by id, by the value of other fields
  //   * Which fields:  All, id only, other fields
  // 
  // This lead to having nine possible methods:
  //
  // A) get_records_all:  All records with all fields
  // B) get_ids_all:      The ID for every record
  // C) get_partials_all: A subset of fields for every record
  //
  // D) get_records_by_ids:  All fields for the specified records
  // E) get_ids_by_ids:      The ids for the specified records
  //                         NOTE: Nonsensical, as one already has the ids
  // F) get_partials_by_ids: A subset of fields for the specified records
  //
  // G) get_records_by_fields:  All fields for records that match values
  //                                 of some particular fields
  // H) get_ids_by_fields:      The ids for records that match values
  //                                 of some particular fields
  // I) get_partials_by_fields: A subset of fields that match values
  //                                 of some particular fields
  //
  // The latter three (G,H,I) can be implemented by first calling C and 
  // then calling the appropriate one of D,E,F, with the exception that
  // E is nonsensical, so doesn't need to actually be implemented.
  //
  // In practice, we have so far found that only A, C, D, and G need to
  // be implemented.
  //
  // NOTE: For performance reasons, it is ESSENTIAL to avoid exporting
  //       or importing too many fields at a time. Doing so can quickly
  //       lead to serious memory problems. 
  //
  //       get_records_all should be used VERY, VERY carefully, if at all.
  // 
  //-------------------------------------------------------------------------


  //-----------------------------------------------------------------------
  // get_all_records
  //
  // $error is used if export fails
  // 
  public function get_records_all($error) {
    return $this->get_records($error,array(),array(),array(),array());
  }
  // END get_all_records
  //-----------------------------------------------------------------------


  //-----------------------------------------------------------------------
  // get_partials_all
  //
  // $error is used if export fails
  // 
  // $fields is array of fields to get. If empty, get all
  //
  public function get_partials_all($error,$fields) {

    $records = array();
    $events = array();
    $forms = array();

    $results = $this->get_records($error,$records,$events,$forms,$fields);

    return($results);
  }
  // END get_partials_all
  //-------------------------------------------------------------------------


  //-----------------------------------------------------------------------
  // get_records_by_ids
  //
  // $error is used if export fails
  // 
  // $records is array of record_ids to get. If empty, get all
  //
  public function get_records_by_ids($error,$records) {

    $events = array();
    $forms = array();
    $fields = array();

    $results = 
      $this->get_records($error,$records,$events,$forms,$fields);

    return($results);
  }
  // END get_records_by_ids
  //-------------------------------------------------------------------------


  //-----------------------------------------------------------------------
  // prep_records_by_ids_batch
  //
  // $error is used if export fails
  // 
  public function prep_records_by_ids_batch($error,$size,$record_ids) {

    $this->records_by_ids_batch_error = $error;
    $this->records_by_ids_batch_size = $size;
    $this->records_by_ids_batch_record_ids = $record_ids;
    
    return(true);
  }
  // END prep_records_by_ids_batch
  //-------------------------------------------------------------------------


  //-----------------------------------------------------------------------
  // get_records_by_ids_batch
  //
  // $error is used if export fails
  //
  // $batch => array($record_id => $records);
  // There can be more than one record for longitudinal projects
  // 
  public function get_records_by_ids_batch() {

    $batch = array();
    $cur_records = array();

    $cur_records = array_splice($this->records_by_ids_batch_record_ids,
				0,
			        $this->records_by_ids_batch_size);

    if (!empty($cur_records)) {

      $cur_error = $this->records_by_ids_batch_error.
    	           " - starting with record: '".$cur_records[0]."";
      $results = $this->get_records_by_ids($cur_error,$cur_records);

      // Set up $batch results
      foreach($results as $result) {
        $primary_id = $result[$this->primary];

        // If no results yet for this record, create array
        if (!array_key_exists($primary_id,$batch)) {
      	  $batch[$primary_id] = array();
        }

        array_push($batch[$primary_id],$result);
      }
    }

    return($batch);
  }
  // END get_records_by_ids_batch
  //-------------------------------------------------------------------------


  //-----------------------------------------------------------------------
  // get_records_by_fields
  //
  // $error is used if export fails
  // 
  // $field_values is an array of fields and values to use when
  // determining which records to get. If multiple fields are
  // supplied, the assumption is that a record would need to match the
  // value of each field to be returned.
  //
  public function get_records_by_fields($error,$field_values) {

    // Get all records, but only the fields of interest
    $fields = array_keys($field_values);
    if (!in_array($this->primary,$fields)) {
      array_push($fields,$this->primary);
    }
    $error = "Could not get subset of fields in get_records_by_fields ".
      "using: ".implode(',',$fields).'.';
    $partials = $this->get_partials_all($error,$fields);

    // Loop through records to find ones that match field values of interest
    $records = array();
    foreach ($partials as $cur_partials) {

      $match = true;

      foreach ($field_values as $field => $value) {

	if ((string)$value !== (string)$cur_partials[$field]) {
	  $match = false;
	  break;
	}
      }

      if ($match) {
	array_push($records,(string)$cur_partials[$this->primary]);
      }
    }

    if (empty($records)) {
      return array();
    }

    $error = "Could not get records in get_records_by_fields ".
      "for ids: ".implode(',',$records).'.';

    $results = $this->get_records_by_ids($error,$records);

    return($results);
  }
  // END get_records_by_fields
  //-------------------------------------------------------------------------


  //-----------------------------------------------------------------------
  // get_records
  //
  // $error is used if export fails
  // 
  // $records is array of record_ids to get. If empty, get all
  //
  public function get_records($error,$records,$events,$forms,$fields) {

    $data = array('content' => 'record', 
		  'type' => 'flat', 
		  'format' => 'json', 
		  'records' => $records, 
		  'events' => $events, 
		  'fields' => $fields, 
		  'forms' => $forms, 
		  'exportSurveyFields'=>'false', 
		  'exportDataAccessGroups'=>'false', 
		  );  

    $new_message = $this->export($error,$data);

    return($new_message[0]);
  }
  // END get_records
  //-------------------------------------------------------------------------


  //-------------------------------------------------------------------------
  // get_file_from_project
  //
  // $error is used if export fails
  // 
  public function get_file_from_project($record_id,$field,$error) {

    $data = array(
    	          'content' => 'file',
                  'action' => 'export',
                  'record' => $record_id,
                  'field' => $field,
                  'returnFormat' => 'json'
                  );

    $results = $this->export($error,$data);

    return($results);
  }
  // END get_file_from_project
  //-------------------------------------------------------------------------


  //-------------------------------------------------------------------------
  // export
  //
  // $error is used if export fails
  // 
  // $data is array to use in Rest Request
  //
  public function export($error,$data) {

    $data['token'] = $this->token;

    // Create REDCap API request object
    $request = new RestCallRequest($this->api_url, $data);

    // Initiate the API request, and fetch the results from the request object.
    $request->execute();
    $body_str = $request->getResponseBody();
    $header_array = $request->getResponseInfo();

    // Decode the JSON content returned by REDCap (into an array rather
    // than into an object by specifying the second argument as "true"),
    if (empty($body_str)) {
      $body_array = array();
    }
    else {
      $body_array = json_decode($body_str, true);
    }

    // Make sure we get a valid body
    if ( ('file' === $data['content']) &&
	 (is_array($body_array)) ) {            // Files aren't sent as array

      if (array_key_exists('error',$body_array)) {
	$error .= "Error: '".$body_array['error']."'\n";

	if (preg_match('/There is no file/',$body_array['error'])) {
	  $body_str = 'NOFILE';                 // Not having file isn't error
	}

	else {
	  $this->notifier->notify($error);

	  return false;
	}
      }
    
    }
  
    elseif( ( 'file' !== $data['content']) &&  // Other content should be array
	    ( (! isset($body_array)) || 
	      (empty($body_array)) || 
	      (array_key_exists('error',$body_array)) ) ) {

      if (array_key_exists('error',$body_array)) {
	$error .= "Error: '".$body_array['error']."'\n";
      }

      $this->notifier->notify($error);

      return false;
    }

    if ('file' == $data['content']) {
      return(array($body_str,$header_array));
    }
   
    else {
      return(array($body_array,$header_array));
    }
  }

  // END export
  //-------------------------------------------------------------------------


  //-----------------------------------------------------------------------
  // get_lookup_choices
  //
  // $error is used if export_general fails
  //
  // $results = array($field_name1 => array($category1 => $label1, ...),
  //                  ...)     	      			  
  // 
  public function get_lookup_choices() {

    $results = array();

    // Get all metadata
    $error = 'Unable to retrieve metadata while getting lookup choices';
    $fields = $this->get_metadata($error,'','');
     
    // Foreach field
    foreach ($fields as $field) {

      // Check the type of field
      switch ($field['field_type']) {

        // If it's a radio, dropdown, or checkbox field
        case 'radio':		  
	case 'dropdown':      
	case 'checkbox':      
	  
	  // Get the choices
	  $choices_str = $field['select_choices_or_calculations'];
	  $choices = array_map('trim', explode("|", $choices_str));

	  $field_results = array();

	  // Foreach choice
	  foreach ($choices as $choice) {

	    if ($choice === "") {
	       continue;
	    }

	    // Get the category and label
	    list ($category, $label) = 
	      array_map('trim', explode(",",$choice,2));

	    // Add them to the results for this field
	    $field_results[$category] = $label;

	  }

	  // Add this field to the overall results
	  $results[$field['field_name']] = $field_results;

          break;

        default:
          break;

      }  // end switch
    }  // end foreach

    return $results;
  }

  // END export
  //-------------------------------------------------------------------------


  //-----------------------------------------------------------------------
  // get_metadata
  //
  // $error is used if export_general fails
  // 
  public function get_metadata($error,$fields,$forms) {

    if (empty($this->metadata)) {

       $data = array('content' => 'metadata', 
       		     'type' => 'flat', 
		     'format' => 'json', 
		     'fields' => $fields, 
		     'forms' => $forms, 
		     );  

       $new_message = $this->export_general($error,$data);
       $this->metadata = $new_message[0];
     }

    return($this->metadata);
  }
  // END get_metadata
  //-------------------------------------------------------------------------


  //-----------------------------------------------------------------------
  // get_fieldnames
  //
  // $error is used if export_general fails
  //
  // $results = array($field_name1 => 1, ...)
  // 
  public function get_fieldnames() {

    if (empty($this->fieldnames)) {

      // Get export_field names
      $error = 'Unable to retrieve exportfieldnames while getting field names';
      $fields = $this->get_exportfieldnames($error,'');
     
      // Foreach field
      foreach ($fields as $field) {
        $this->fieldnames[$field['export_field_name']] = 1;
      }
    }

    return($this->fieldnames);
  }

  // END get_fieldnames
  //-------------------------------------------------------------------------


  //-----------------------------------------------------------------------
  // get_exportfieldnames
  //
  // $error is used if export_general fails
  // 
  public function get_exportfieldnames($error,$field) {

    if (empty($this->exportfieldnames)) {

       $data = array('content' => 'exportFieldNames', 
		     'format' => 'json', 
		     'field' => $field, 
		     );  

       $new_message = $this->export_general($error,$data);
       $this->exportfieldnames = $new_message[0];
     }

    return($this->exportfieldnames);
  }
  // END get_exportfieldnames
  //-------------------------------------------------------------------------


  //-------------------------------------------------------------------------
  // export_general
  //
  // $error is used if export_general fails
  // 
  // $data is array to use in Rest Request
  //
  public function export_general($error,$data) {

    $data['token'] = $this->token;

    // Create REDCap API request object
    $request = new RestCallRequest($this->api_url, $data);

    // Initiate the API request, and fetch the results from the request object.
    $request->execute();
    $body_str = $request->getResponseBody();
    $header_array = $request->getResponseInfo();

    // Decode the JSON content returned by REDCap (into an array rather
    // than into an object by specifying the second argument as "true"),
    if (empty($body_str)) {
      $body_array = array();
    }
    else {
      $body_array = json_decode($body_str, true);
    }

    if( (! isset($body_array)) || 
	(empty($body_array)) || 
	(array_key_exists('error',$body_array)) ) {

      if (array_key_exists('error',$body_array)) {
	$error .= "Error: '".$body_array['error']."'\n";
      }

      $this->notifier->notify($error);

      return false;
    }

    return(array($body_array,$header_array));
  }

  // END export_general
  //-------------------------------------------------------------------------


  //-----------------------------------------------------------------------
  // get_project_id
  //
  // $error is used if export_project_info fails
  // 
  public function get_project_id($error) {

    $results = $this->get_project_info($error);

    $project_id = $results['project_id'];

    return($project_id);
  }

  // END get_project_id
  //-------------------------------------------------------------------------


  //-----------------------------------------------------------------------
  // get_project_info
  //
  // $error is used if export_project_info fails
  // 
  public function get_project_info($error) {

    $data = array('content' => 'project', 
		  'format' => 'json', 
		  );  

    $new_message = $this->export_general($error,$data);

    return($new_message[0]);
  }
  // END get_project_info
  //-------------------------------------------------------------------------


  //-------------------------------------------------------------------------
  // import_records -- Import one record into a project
  //
  // $records = array( array( $key1 => $val1, ...) ...)
  //
  // $message is used if import fails
  //
  public function import_records($records,$message) {
		 
    // Encode the records to be imported into JSON content
    $records_json = json_encode($records);
    
    $data = array('token' => $this->token,
		  'format' => 'json',
		  'content' => 'record', 
		  'type' => 'flat', 
		  'overwriteBehavior' => 'normal', // Should be n/a
		  'returnContent' => 'count',
		  'data' => $records_json,
		  );  

    // Create REDCap API request object
    $request = new RestCallRequest($this->api_url, $data);
    
    // Initiate the API request, and fetch the results from the request object.
    $request->execute();
    $body_array = $request->getResponseBody();
    
    // Decode the JSON content returned by REDCap (into an array rather
    // than into an object by specifying the second argument as "true"),
    $new_message = json_decode($body_array, true);
    
    // Retrieve the count of records imported, if available
    $count = 0;
    if (is_array($new_message) && 
	isset($new_message['count'])) { $count = $new_message['count']; }
    
    // If there's a problem or if the count is zero, send email to the admin
    if ((!isset($new_message)) || empty($new_message) || 0 == $count) {
      
      $error = "Could not import records. Component continues.\n";
      $error .= "App: ".$this->app."\n";
      $error .= "Message: ".$message."\n";
      if (isset($new_message['error'])) { 
	$error .= $new_message['error']."\n"; 
      }
      
      $this->notifier->notify($error);
      
      return false;
    }
  
    return true;
  
  }
  // END import_records
  //---------------------------------------------------------------------------


  //-------------------------------------------------------------------------
  // check_advanced_link_auth -- Use authkey to authenticate
  //
  // REDCap's advanced link option sends an authkey. By sending that
  // authkey back to REDCap via the API, we determine whether or not
  // it is a valid authkey and the user of that authkey is still
  // authenticated.
  //
  // If not, get back '0'.
  // If so, get back an array including username, project_id,
  // data_access_group_name, data_access_group_id, and callback_url.
  //
  // Currently this function does no error checking, so calling
  // code needs to check to see whether or not an array is returned or
  // a 0.
  //
  public function check_advanced_link_auth($authkey) {

    // Create REDCap API request object
    $data = array(
		  'authkey' => $authkey,
		  'format' => 'json'
		  );
    $request = new RestCallRequest($this->api_url, $data);

    // Initiate the API request, and fetch the results from the request object.
    $request->execute();
    $body_str = $request->getResponseBody();
    $header_array = $request->getResponseInfo();

    // Decode the JSON content returned by REDCap (into an array rather
    // than into an object by specifying the second argument as "true"),
    $body_array = json_decode($body_str, true);

    // NOTE: If there is an error, $body_array is set to '0'.
    return($body_array);
    
  }
  // END check_advanced_link_auth
  //-------------------------------------------------------------------------


  //-------------------------------------------------------------------------  
  // get_survey_url -- Get the URL for a particular survey in a particular
  //                   event.
  //                                                                           
  //                                                                           
  public function get_survey_url($instrument,$event,$record,$error) {

    $url = $this->get_survey_string('surveyLink',
				    $instrument,$event,$record,$error);

    return $url;
  }
  // END get_survey_url
  //---------------------------------------------------------------------------


  //-------------------------------------------------------------------------  
  // get_survey_return_code -- Get the return code for a particular survey 
  //                           in a particular event.
  //                                                                           
  //                                                                           
  public function get_survey_return_code($instrument,$event,$record,$error) {

    $code = $this->get_survey_string('surveyReturnCode',
				     $instrument,$event,$record,$error);

    return $code;
  }
  // END get_survey_return_code
  //---------------------------------------------------------------------------


  //-------------------------------------------------------------------------  
  // get_survey_string -- For API calls that get a single result string
  //                      related to a particular survey
  //                                                                           
  //                                                                           
  public function get_survey_string($content, 
				    $instrument,$event,$record,$error) {

    $data = array('content' => $content,
		  'format' => 'json', 
		  'instrument' => $instrument,
		  'event' => $event, 
		  'record' => $record, 
		  'returnFormat' => 'json'
		  );  
    $result = $this->api_get_string($data,$error);

    return $result;
  }
  // END get_survey_string
  //---------------------------------------------------------------------------


  //-------------------------------------------------------------------------  
  // api_get_string -- Makes an API call that expects a single string result
  //                                                                           
  // $error is used if export fails
  // 
  // $data is array to use in Rest Request
  //
  public function api_get_string($data,$error) {

    $data['token'] = $this->token;

    // Create REDCap API request object
    $request = new RestCallRequest($this->api_url, $data);

    // Initiate the API request, and fetch the results from the request object.
    $request->execute();
    $body_str = $request->getResponseBody();
    $header_array = $request->getResponseInfo();

    // Decode the JSON content returned by REDCap (into an array rather
    // than into an object by specifying the second argument as "true"),
    $body_array = json_decode($body_str, true);

    // If an error is returned, there will be an 'error' key in
    // the $body_array
    if ((is_array($body_array)) &&
	(array_key_exists('error',$body_array))) {
      $error .= "Error: '".$body_array['error']."'\n";
      
      $this->notifier->notify($error);

      return false;
    }

    // Otherwises, the body_str will have our result
    else {
      return $body_str;
    }

  }
  // END api_get_string
  //---------------------------------------------------------------------------


}
// END class REDCapProject
//-------------------------------------------------------------------------


//---------------------------------------------------------------------------
// REDCapFactory
//
// Allows the creation of a set of REDCapProject objects that share
// attributes, such as what notifier object they use.
//-------------------------------------------------------------------------
//
class REDCapFactory {

  protected $app = '';
  protected $api_url = '';
  protected $notifier = '';  // Object for reporting errors

  public function __construct($app,$api_url,$notifier) {
    $this->app = $app;
    $this->api_url = $api_url;
    $this->notifier = $notifier;
  }

  public function make_project($token,$primary) {

    $project = new REDCapProject(
				 $this->app,
				 $this->api_url,
				 $token,
				 $this->notifier,
				 $primary
				 );

    return ($project);
  }

  public function make_dethandler($pid,$allowed_servers) {
    $dethandler = new REDCapDETHandler($pid,$allowed_servers,$this->notifier);
    return ($dethandler);
  }

  public function set_notifier($notifier) {
    $this->notifier = $notifier;
  }

}
// END class REDCapFactory
//-------------------------------------------------------------------------


//---------------------------------------------------------------------------
// REDCapDETHandler
//
// Functions related to handling calls from REDCap's Data Entry Trigger.
//-------------------------------------------------------------------------
//
class REDCapDETHandler {

  protected $debug = '';

  protected $pid = '';
  protected $allowed_servers;
  protected $notifier = '';  // Object for reporting errors

  public function __construct($pid,$allowed_servers,$notifier) {
    
    $this->debug = 'no';

    $this->pid = $pid;
    $this->notifier = $notifier;

    // Create array of allowed servers (by hostname)
    if (preg_match("/,/", $allowed_servers)) {
      if ('yes' == $this->debug) { 
	print "Found multiple allowed servers<br/>"; 
      }
      $this->allowed_servers = preg_split("/,/", $allowed_servers);
    }
    else {
      $this->allowed_servers = array($allowed_servers);
    }

  }

  //-------------------------------------------------------------------------
  // get_det_params
  //
  public function get_det_params() {

    // If either project_id or record_id are empty, this program assumes
    // that it is being tested by using a URL from a web browser rather
    // than being called by a REDCap Data Entry Trigger.  To perform such
    // a test, use a URL like:
    //
    //     https://redcap-testing.uits.iu.edu/apis/ctp_baseline_handler.php?
    //             project_id=801&record=20   NOTE: _not_ record_id
    //
    // In this case, project_id and record_id are read from GET parameters,
    // $DEBUG is forced to 'yes';
    //
    if (!isset($_POST['project_id']) || !isset($_POST['record'])) {
      $project_id = htmlspecialchars($_GET['project_id']);
      $record_id = htmlspecialchars($_GET['record']); // NOT 'record_id'
      $this->debug = 'yes';
    }
    else {
      $project_id = htmlspecialchars($_POST['project_id']);
      $record_id = htmlspecialchars($_POST['record']); // NOT 'record_id'
    }

    return(array($project_id,$record_id));
  }
  // END get_det_params
  //-------------------------------------------------------------------------


  //-------------------------------------------------------------------------
  // check_allowed_servers
  //
  // Make sure the request is from an approved server. If not, log
  // the failure and end.
  //
  public function check_allowed_servers() {

    // Determine the hostname of the server making this request
    $server_remote_addr = $_SERVER['REMOTE_ADDR'];

    // allow IPv6 local host (::1)
    if ('::1' === $server_remote_addr) {
      return true;
    }

    // filter_var() is only avaialbe for PHP >= 5.2, so ip2long is used here
    // and will need to be changed if IPV6 addresses are to be processed.
    // #if(filter_var($server_remote_addr,FILTER_VALIDATE_IP,FILTER_FLAG_IPV4))

    // If not a valid IP address
    if (!ip2long($server_remote_addr)) {
      $error = "Invalid server remote address: ".$_SERVER['REMOTE_ADDR']."\n";
      $this->notifier->notify($error);
      exit(1);
    }

    // Check to see if the requesting server is allowed
    $hostname = gethostbyaddr($server_remote_addr);
    if(($hostname === null) || ($hostname === "") ||
       ! in_array($hostname,$this->allowed_servers )) {

      $error = 
	"Server remote address not allowed: ".$_SERVER['REMOTE_ADDR']."\n";

      $this->notifier->notify($error);

      exit(1);
    }

    return true;
  }
  // END check_allowed_servers
  //-------------------------------------------------------------------------


  //-------------------------------------------------------------------------
  // check_det_id
  //
  // Checks that the project_id supplied by a call from a Data Entry Trigger
  // supplies the expected project id.
  //
  public function check_det_id($det_id) {

    if ((int) $det_id !== (int) $this->pid) {

      $error = "Project id supplied by data entry trigger ('".$det_id."') ".
	"does not match expected id for survey ('".$this->pid."').";
      $this->notifier->notify($error);
    }

    return true;
  }

  // END check_det_id
  //-------------------------------------------------------------------------


}
// END class REDCapDETHandler
//-------------------------------------------------------------------------

//-------------------------------------------------------------------------
//
// RestCallRequest holds arguments and results for an API call
//
//-------------------------------------------------------------------------
//
class RestCallRequest {

  protected $url;
  protected $requestBody;
  protected $responseBody;
  protected $responseInfo;
	
  public function getResponseBody () {
    return $this->responseBody;
  } 

  public function getResponseInfo () {
    return $this->responseInfo;
  } 
	
  public function __construct ($url, $requestBody) {
    $this->url	         = $url;
    $this->requestBody   = http_build_query($requestBody, '', '&');
    $this->responseBody  = null;
    $this->responseInfo  = null;
  }
	
  public function execute () {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
#   curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, TRUE);

    // 20 minutes till it times out
    curl_setopt($ch, CURLOPT_TIMEOUT, 1200); 
    curl_setopt($ch, CURLOPT_URL, $this->url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: text/xml'));

    // Because we are doing a POST
    curl_setopt($ch, CURLOPT_POSTFIELDS, $this->requestBody);
    curl_setopt($ch, CURLOPT_POST, 1);
	
    $this->responseBody = curl_exec($ch);
    $this->responseInfo = curl_getinfo($ch);
		
    curl_close($ch);
  
    return(true);
  }
	
}
// END class RestCallRequest
//-------------------------------------------------------------------------

