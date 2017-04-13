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
     *         information about each field. Some examples of the information
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
     * Gets the REDCap version number of the instance being used by the project.
     * 
     * @return string the REDCap version number of the instance being used by the project.
     */
    public  function exportRedcapVersion() {
        $data = array(
                'token' => $this->apiToken,
                'content' => 'version'
        );
        $callData = http_build_query($data, '', '&');
        $redcapVersion = $this->connection->call($callData);
        
        return $redcapVersion;
    }
    
    
    
    /**
     * Gets the call information for the last cURL call. PHPCap uses cURL to
     * communicate with the REDCap API.
     * 
     * @return array cURL call information for last cURL call made.
     * 
     * @see <a href="http://php.net/manual/en/function.curl-getinfo.php">
     *      http://php.net/manual/en/function.curl-getinfo.php
     *      </a>
     *      for information on what values are returned.
     */
    public function getCallInfo() {
        $callInfo = $this->connection->getCallInfo();
    
        return $callInfo;
    }

    /**
     * Imports the specified records into the project.
     *
     * @param mixed $records
     *            If the 'php' (default) format is being used, an array of associated arrays (maps)
     *            where each key is a field name,
     *            and its value is the value to store in that field. If any other format is being used, then
     *            the records are represented by a string.
     * @param string $format One of the following formats can be specified
     *            <ul>
     *              <li> 'php' - array of maps of values [default]</li>
     *              <li> 'csv' - string of CSV (comma-separated values)</li>
     *              <li> 'json' - string of JSON encoded values</li>
     *              <li> 'xml' - string of XML encoded data</li>
     *              <li> 'odm' - CDISC ODM XML format, specifically ODM version 1.3.1</li>
     *            </ul>
     * @param string $type
     *            if set to 'flat' then each data element is a record, or
     *            if 'eav' then each data element is one value.
     * @param string $overwriteBehavior
     *            <ul>
     *              <li>normal - blank/empty values will be ignored [default]</li>
     *              <li>overwrite - blank/empty values are valid and will overwrite data</li>
     *            </ul>
     * @param string $returnContent 'count' (the default) or 'ids'.
     * @param string $dateFormat date format which can be one of the following:
     *            <ul>
     *              <li>'YMD' - Y-M-D format (e.g., 2016-12-31) [default]</li>
     *              <li>'MDY' - M/D/Y format (e.g., 12/31/2016)</li>
     *              <li>'DMY' - D/M/Y format (e.g., 31/12/2016)</li>
     *           </ul>
     * @return mixed
     */
    public function importRecords(
            $records, 
            $format = 'php',
            $type = 'flat', 
            $overwriteBehavior = 'normal', 
            $returnContent = 'count',
            $dateFormat = 'YMD'
        )
    {
        
        $data = array (
                'token'   => $this->apiToken,
                'content' => 'record',
                'format'  => $format,
                'type'    => $type,
                
                'overwriteBehavior' => $overwriteBehavior,
                'returnFormat'      => 'json',
                'dateFormat'        => $dateFormat
        );
        
        // If the PHP format was used, need to convert to JSON
        if ($format === 'php') {
            $records = json_encode($records);
            $data['format'] = 'json';
        }
        
        $data ['data'] = $records;
        
        $callData = http_build_query($data, '', '&');
        
        $result = $this->connection->call($callData);
        
        // Check $result for errors ...
        
        return $result;
    }
    
    /**
     * Imports the records from the specified file into the project.
     *
     * @param string $filename
     *            The name of the file containing the records to import.
     * @param string $format One of the following formats can be specified
     *            <ul>
     *              <li> 'csv' - string of CSV (comma-separated values)</li>
     *              <li> 'json' - string of JSON encoded values</li>
     *              <li> 'xml' - string of XML encoded data (default)</li>
     *              <li> 'odm' - CDISC ODM XML format, specifically ODM version 1.3.1</li>
     *            </ul>
     * @param string $type
     *            if set to 'flat' then each data element is a record, or
     *            if 'eav' then each data element is one value.
     * @param string $overwriteBehavior
     *            <ul>
     *              <li>normal - blank/empty values will be ignored [default]</li>
     *              <li>overwrite - blank/empty values are valid and will overwrite data</li>
     *            </ul>
     * @param string $returnContent 'count' (the default) or 'ids'.
     * @param string $dateFormat date format which can be one of the following:
     *            <ul>
     *              <li>'YMD' - Y-M-D format (e.g., 2016-12-31) [default]</li>
     *              <li>'MDY' - M/D/Y format (e.g., 12/31/2016)</li>
     *              <li>'DMY' - D/M/Y format (e.g., 31/12/2016)</li>
     *           </ul>
     * @return mixed
     */
    public function importRecordsFromFile(
            $filename,
            $format = 'xml',
            $type = 'flat',
            $overwriteBehavior = 'normal',
            $returnContent = 'count',
            $dateFormat = 'YMD'
            )
    {
        $records = file_get_contents($filename);
        
        $result = $this->importRecords($records, $format, $type, $overwriteBehavior, $returnContent, $dateFormat);
    
        return $result;
    }
    
    
    /**
     * Imports the file into the field of the record (with the specified event, if any).
     * 
     * @param string $filename the name of the file to import.
     * @param string $recordId the record ID of the record to import the file into.
     * @param string $field the field of the record to import the file into.
     * @param string $event the event of the record to import the file into.
     * @throws PHPCapException
     */
    public function importFile($filename, $recordId, $field, $event='') {

        if (!file_exists($filename)) {
            throw new PHPCapException('The input file could not be found.',
                    PhpCapException::INPUT_FILE_ERROR);
        }
        
        
        $data = array (
                'token'        => $this->apiToken,
                'content'      => 'file',
                'action'       => 'import',
                'returnFormat' => 'json'
        );
        
        $data['file']   = $filename;
        $data['record'] = $record;
        $data['field']  = $field;
        if (isset($event)) {
            $data['event']  = $event;
        }
        
        $callData = http_build_query($data, '', '&');
        $jsonResult = $this->connection->call($callData);
        
        if (isset($jsonResult)) {
            $result = json_decode($jsonResult, true);
            if (array_key_exists('error', $result)) {
                throw new PHPCapException($result['error'], PhpCapException::INPUT_FILE_ERROR);
            }
        }
    }
    
    
    /**
     * Gets the timeout in seconds for calls to the REDCap API.
     *
     * @return integer timeout in seconds for cURL calls.
     */
    public function getTimeoutInSeconds() {
        return $this->connection->getTimeoutInSeconds;
    }
    
    /**
     * Sets the timeout for calls to the REDCap API to the specified number of seconds.
     *
     * @param integer $timeoutInSeconds timeout in seconds for cURL calls.
     */
    public function setTimeoutInSeconds($timeoutInSeconds) {
        $this->connection->setTimeoutInSeconds = $timeoutInSeconds;
    }
    
    /**
     * Returns the underlying REDCap API connection being used by the project.
     * This can be used to make calls to the REDCap API, possibly to access functionality
     * not supported by PHPCap.
     *         
     * @return RedCapApiConnection the underlying REDCap API connection being
     *         used by the project. 
     */
    public function getConnection() {
        return $this->connection;
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
            
            if (array_key_exists('error', $records)) {
                throw new PhpCapException($records['error'], PhpCapException::REDCAP_API_EXCEPTION);
            }
        }
        
        return $records;
    }
}