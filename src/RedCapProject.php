<?php 
/**
 * This file contains the REDCap project class for PHPCap.
 */
namespace IU\PHPCap;

require_once(__DIR__."/RedCapApiConnection.php");
require_once(__DIR__."/PhpCapException.php");

/**
 * REDCap project class used to retrieve data from, and modify, REDCap projects.
 */
class RedCapProject
{
    
    /** @var string URL for the REDCap API for the project. */
    private $apiURL;
    
    /** @var string REDCap API token for the project */
    private $apiToken;
    
    /** @var boolean indicates if SSL verification is done for the REDCap site being used. */
    private $sslVerify;
    
    /** @var string the full path of the CA (Certificate Authority) certificate file used for SSL verification. */
    private $caCertificateFile;
    
    /** @var RedCapApiConnection connection to the REDCap API at the $apiURL. */
    private $connection;
    
    //private $projectInfo;
    //private $metadata;
 
    
    /**
     * Contructs a REDCap project for the specifed information.
     * 
     * @param string $apiUrl the URL for the API for the REDCap that has the project.
     * @param string $apiToken the API token for this project.
     * @param boolean $sslVerify indicates if SSL connection to REDCap web site should be verified.
     * @param string $caCertificateFile the full path name of the CA (Certificate Authority) certificate file.
     * 
     * @throws PHPCapException if any of the arguments are invalid
     */
    public function __construct($apiUrl, $apiToken, $sslVerify = false, $caCertificateFile = null) {
        
        #----------------------------------------------------------------------------------------
        # Process the REDCAp API URL
        # Note: standard PHP URL validation will fail for non-ASCII URLs (so it was not used)
        #----------------------------------------------------------------------------------------
        if (!isset($apiUrl)) {
            throw new PhpCapException("The REDCap API URL spefied for the project was null or blank."
                    . gettype($apiUrl), PhpCapException::INVALID_ARGUMENT);
        }
        elseif (gettype($apiUrl) !== 'string') {
            throw new PhpCapException("The REDCap API URL provided (".$apiUrl.") should be a string, but has type: "
                    . gettype($apiUrl), PhpCapException::INVALID_ARGUMENT);    
        }
        $this->apiURL = $apiUrl;
        
        
        #------------------------------------------------------------
        # Process the REDCap API token
        #------------------------------------------------------------
        if (!isset($apiToken)) {
            throw new PhpCapException("The REDCap API token spefied for the project was null or blank."
                    . gettype($apiToken), PhpCapException::INVALID_ARGUMENT);
        }
        elseif (gettype($apiToken) !== 'string') {
            throw new PhpCapException("The REDCap API token provided should be a string, but has type: "
                    . gettype($apiToken), PhpCapException::INVALID_ARGUMENT);
        }
        elseif (!ctype_xdigit($apiToken)) {   // ctype_xdigit - check token for hexidecimal
            throw new PhpCapException("The REDCap API token has an invalid format."
                    ." It should only contain numbers and the letters A, B, C, D, E and F."
                    , PhpCapException::INVALID_ARGUMENT);
        }
        elseif (strlen($apiToken) != 32 && strlen($apiToken) != 64) {
            throw new PhpCapException("The REDCap API token has an invalid format."
                    . " It has a length of ".strlen($apiToken)." characters, but should have a length of"
                    . " 32 or 64 characters (if a super token is being used)."
                    , PhpCapException::INVALID_ARGUMENT);
        }
        $this->apiToken = $apiToken;
        
        #----------------------------------------------------
        # Process SSL verify
        #----------------------------------------------------
        $this->sslVerify         = $sslVerify;
        if (isset($sslVerify) && gettype($sslVerify) !== 'boolean') {
            throw new PhpCapException('The value for $sslVerify be a boolean (true/false), but has type: '
                    . gettype($sslVerify), PhpCapException::INVALID_ARGUMENT);
        }
        
        #-----------------------------------------------------
        # Process CA certificate file
        #-----------------------------------------------------
        if (isset($caCertificateFile) && gettype($caCertificateFile) !== 'string') {
            throw new PhpCapException('The value for $sslVerify be a string, but has type: '
                    . gettype($caCertificateFile), PhpCapException::INVALID_ARGUMENT);
        }
        $this->caCertificateFile = $caCertificateFile;

        
        $this->connection = new RedCapApiConnection($apiUrl, $sslVerify, $caCertificateFile);
    }
    
    /**
     * Exports the specified records.
     * 
     * @param string $type the type of records exported: 'flat' or 'eav'.
     *         'flat' exports one record per row. 'eav' exports one data point per row, so,
     *         for non-longitudinal studies, each record will have the following
     *         fields: record_id, field_name, value. For longitudinal studies, each record 
     *         will have the fields: record_id, field_name, value, redcap_event_name. 
     * @param array $recordIds array of strings with record id's that are to be retrieved. 
     * @param array $fields array of field names to export
     * @param array $forms array of form names for which fields should be exported
     * @param array $events array of event names for which fields should be exported
     * @param array $filterLogic logic used to restrict the records retrieved, e.g.,
     *         "[last_name] = 'Smith'".
     * 
     * @return array of records
     */
    public function exportRecords(
            $type = 'flat',
            $recordIds = null,
            $fields = null,
            $forms = null,
            $events = null,
            $filterLogic = null
    ) {
        $data = array(
                'token'        => $this->apiToken,
                'content'      => 'record',
                'format'       => 'json',
                'returnFormat' => 'json'
        );
        
        if ($type == null) $type = 'flat';
        $type = strtolower($type);
        if (strcmp($type,'flat') !== 0 && strcmp($type,'eav') !== 0) {
            throw new PhpCapException("Invalid type \"".$type."\". Type should be either 'flat' or 'eav'", PhpCapException::INVALID_ARGUMENT);
        }
        $data['type'] = $type;
        
        if ($recordIds != null) {
            if (!is_array($recordIds)) {
                throw new PhpCapException("recordIds has the wrong type; it should be an array.", PhpCapException::INVALID_ARGUMENT);
            }
            $data['records'] = $recordIds;
        }
        
        if ($fields != null) {
            $data['fields'] = $fields;
        }
        
        if ($forms != null) {
            $data['forms'] = $forms;
        }
        
        if ($events != null) {
            $data['events'] = $events;
        }

        if ($filterLogic != null) {
            $data['filterLogic'] = $filterLogic;
        }
        
        $callData = http_build_query($data, '', '&');
        $jsonRecords = $this->connection->call($callData);
        
        $records = $this->processJsonExport($jsonRecords);
        
        return $records;
    }

    
    /**
     * Exports information about the project, e.g., project ID, project title, creation time.
     *                        
     * @return array associative array (map) of project information. See REDCap API documentation
     *         for a list of the fields, or use the print_r function on the results of this method.
     */
    public function exportProjectInfo() {
        $data = array(
                'token' => $this->apiToken,
                'content' => 'project',
                'format' => 'json',
                'returnFormat' => 'json'
        );
        $callData = http_build_query($data, '', '&');
        $projectInfo = $this->connection->call($callData);

        if (empty($projectInfo)) {
            $projectInfo = array ();
        } 
        else {
            $projectInfo = json_decode($projectInfo, true);   // true => return as array instead of object
        }
        
        return $projectInfo;
    }

    /**
     * Exports metadata about the project, i.e., information about the fields in the project.
     * 
     * @return array associative array (map) of metatdata for the project, which consists of
     *         information about each field in the project. Some examples of the information
     *         provided are: 'field_name', 'form_name', 'field_type', 'field_label'.
     *         See REDCap API documentation
     *         for more information, or use the print_r function on the results of this method.
     */
    public function exportMetadata() {
        $data = array(
                'token' => $this->apiToken,
                'content' => 'metadata',
                'format' => 'json',
                'returnFormat' => 'json'
        );
        $callData = http_build_query($data, '', '&');
        $metadata = $this->connection->call($callData);
    
        if (empty($metadata)) {
            $metadata = array ();
        }
        else {
            $metadata = json_decode($metadata, true);   // true => return as array instead of object
        }
    
        return $metadata;
    }
    
    /**
     * Gets the call information for the last cURL call. PHPCap uses cURL to
     * communicate with the REDCap API.
     * 
     * @return array cURL call information for last cURL call made.
     * 
     * @see http://php.net/manual/en/function.curl-getinfo.php for information on what values are returned.
     */
    public function getCallInfo() {
        $callInfo = $this->connection->getCallInfo();
    
        return $callInfo;
    }

    /**
     * Imports the specified records into the project.
     *
     * @param $recordData array
     *            array of associated arrays (maps) where each key is a field name,
     *            and its value is the value to store in that field.
     * @param $type string
     *            if set to 'flat' then each data element is a record, or
     *            if 'eav' then each data element is one value.
     * @param $dateFormat string date format which can be one of the following: 'MDY', 'DMY', 'YMD' [default].
     *            'YMD' => Y-M-D (e.g., 2016-12-31), MDY => M/D/Y format, and DMY => D/M/Y format.
     */
    public function importRecords(
            $recordData, 
            $type = 'flat', 
            $overwriteBehavior = 'normal', 
            $returnContent = 'count',
            $dateFormat = 'YMD'
        )
    {
        $data = array (
                'token' => $this->apiToken,
                'content' => 'record',
                'format' => 'json',
                'returnFormat' => 'json' 
        );
        
        // Need to convert data to JSON
        $jsonRecordData = json_encode($recordData);
        
        $data ['data'] = $jsonRecordData;
        
        $callData = http_build_query($data, '', '&');
        $result = $this->connection->call($callData);
        
        return $result;
    }
    
    
    /**
     * Processes JSON exported from REDCap.
     * 
     * @param string $jsonRecords
     * @return array processed JSON records.
     * @throws PHPCapException if an error occurs.
     */
    private function processJsonExport($jsonRecords) {

        if (empty($jsonRecords)) {
            $records = array ();
        }
        else {
            $records = json_decode($jsonRecords, true);   // true => return as array instead of object
        
            $jsonError = json_last_error();
        
            switch ($jsonError) {
                case JSON_ERROR_NONE:
                    break;
                default:
                    throw new PHPCapException("JSON error (".$jsonError.") \""
                            .json_last_error_msg()."\" in REDCap API output."
                                    ."\nThe first 1,000 characters of output returned from REDCap are:\n"
                                            .substr($jsonRecords,0,1000), PhpCapException::JSON_ERROR);
                    break;
            }
        }
        
        return $records;
    }
}