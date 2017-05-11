<?php
/**
 * This file contains the REDCap project class for PHPCap.
 */
namespace IU\PHPCap;

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
     * Creates a REDCapProject object for the specifed project.
     *
     * Example Usage:
     * <code>
     * <?php
     * require('PHPCap/autoloader.php');
     *
     * use \IU\PHPCap\RedCapProject;
     *
     * $apiUrl = 'https://redcap.someplace.edu/api/'; # replace with your API URL
     * $apiToken = '11111111112222222222333333333344'; # replace with your API token
     * $sslVerify = true;
     *
     * # See the PHPCap documentation for information on how to set this file up
     * $caCertificateFile = 'USERTrustRSACertificationAuthority.crt';
     *
     * $project = new RedCapProject($apiUrl, $apiToken, $sslVerify, $caCertificateFile);
     * </code>
     *
     * @param string $apiUrl the URL for the API for the REDCap that has the project.
     * @param string $apiToken the API token for this project.
     * @param boolean $sslVerify indicates if SSL connection to REDCap web site should be verified.
     * @param string $caCertificateFile the full path name of the CA (Certificate Authority) certificate file.
     *
     * @throws PhpCapException if any of the arguments are invalid
     */
    public function __construct($apiUrl, $apiToken, $sslVerify = false, $caCertificateFile = null)
    {
        #----------------------------------------------------------------------------------------
        # Process the REDCAp API URL
        # Note: standard PHP URL validation will fail for non-ASCII URLs (so it was not used)
        #----------------------------------------------------------------------------------------
        if (!isset($apiUrl)) {
            throw new PhpCapException(
                "The REDCap API URL specified for the project was null or blank.",
                PhpCapException::INVALID_ARGUMENT
            );
        } elseif (gettype($apiUrl) !== 'string') {
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
        } elseif (gettype($apiToken) !== 'string') {
            throw new PhpCapException("The REDCap API token provided should be a string, but has type: "
                    . gettype($apiToken), PhpCapException::INVALID_ARGUMENT);
        } elseif (!ctype_xdigit($apiToken)) {   // ctype_xdigit - check token for hexidecimal
            throw new PhpCapException(
                "The REDCap API token has an invalid format."
                ." It should only contain numbers and the letters A, B, C, D, E and F.",
                PhpCapException::INVALID_ARGUMENT
            );
        } elseif (strlen($apiToken) != 32 && strlen($apiToken) != 64) {
            throw new PhpCapException(
                "The REDCap API token has an invalid format."
                . " It has a length of ".strlen($apiToken)." characters, but should have a length of"
                . " 32 or 64 characters (if a super token is being used).",
                PhpCapException::INVALID_ARGUMENT
            );
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
     * Example usage:
     * <code>
     * $records = $project->exportRecords($format = 'csv', $type = 'flat');
     * $recordIds = [1001, 1002, 1003];
     * $records = $project->exportRecords('xml', 'eav', $recordIds);
     * </code>
     *
     * @param string $format the format in which to export the records:
     *     <ul>
     *       <li> 'php' - array of maps of values [default]</li>
     *       <li> 'csv' - string of CSV (comma-separated values)</li>
     *       <li> 'json' - string of JSON encoded values</li>
     *       <li> 'xml' - string of XML encoded data</li>
     *       <li> 'odm' - string with CDISC ODM XML format, specifically ODM version 1.3.1</li>
     *     </ul>
     * @param string $type the type of records exported:
     *     <ul>
     *       <li>'flat' - exports one record per row.</li>
     *       <li>'eav'  - exports one data point per row:, so,
     *         for non-longitudinal studies, each record will have the following
     *         fields: record_id, field_name, value. For longitudinal studies, each record
     *         will have the fields: record_id, field_name, value, redcap_event_name.
     *       </li>
     *     </ul>
     * @param array $recordIds array of strings with record id's that are to be retrieved.
     * @param array $fields array of field names to export
     * @param array $forms array of form names for which fields should be exported
     * @param array $events array of event names for which fields should be exported
     * @param array $filterLogic logic used to restrict the records retrieved, e.g.,
     *         "[last_name] = 'Smith'".
     * @param string $rawOrLabel indicates what should be exported for options of multiple choice fields:
     *     <ul>
     *       <li> 'raw' - export the raw coded values [default]</li>
     *       <li> 'label' - export the labels</li>
     *     </ul>
     * @param string $rawOrLabelHeaders when exporting with 'csv' format 'flat' type, indicates what format
     *         should be used for the CSV headers:
     *         <ul>
     *           <li> 'raw' - export the variable/field names [default]</li>
     *           <li> 'label' - export the field labels</li>
     *         </ul>
     * @param boolean $exportCheckboxLabel specifies the format for checkbox fields for the case where
     *         $format = 'csv', $rawOrLabel = true, and $type = 'flat'. For other cases this
     *         parameter is effectively ignored.
     *     <ul>
     *       <li> true - checked checkboxes will have a value equal to the checkbox option's label
     *           (e.g., 'Choice 1'), and unchecked checkboxes will have a blank value.
     *       </li>
     *       <li> false - [default] checked checkboxes will have a value of 'Checked', and
     *            unchecked checkboxes will have a value of 'Unchecked'.
     *       </li>
     *     </ul>
     * @param boolean $exportSurveyFields
     * @param boolean $exportDataAccessGroups
     *
     * @return mixed If 'php' format is specified, an array of records will be returned, where each record
     *     is an array where the keys are the fields names, and the values are the field values. For other
     *     formats, a string is returned that contains the records in the specified format.
     */
    public function exportRecords(
        $format = 'php',
        $type = 'flat',
        $recordIds = null,
        $fields = null,
        $forms = null,
        $events = null,
        $filterLogic = null,
        $rawOrLabel = 'raw',
        $rawOrLabelHeaders = 'raw',
        $exportCheckboxLabel = false,
        $exportSurveyFields = false,
        $exportDataAccessGroups = false
    ) {
        $data = array(
                'token'        => $this->apiToken,
                'content'      => 'record',
                'format'       => 'json',
                'returnFormat' => 'json'
        );
        
        #---------------------------------------
        # Process the arguments
        #---------------------------------------
        $legalFormats = array('php', 'csv', 'json', 'xml', 'odm');
        $data['format'] = $this->processFormatArgument($format, $legalFormats);
        
        $data['type']    = $this->processTypeArgument($type);
        $data['records'] = $this->processRecordIdsArgument($recordIds);
        $data['fields']  = $this->processFieldsArgument($fields);
        $data['forms']   = $this->processFormsArgument($forms);
        $data['events']  = $this->processEventsArgument($events);
        
        $data['rawOrLabel']             = $this->processRawOrLabelArgument($rawOrLabel);
        $data['rawOrLabelHeaders']      = $this->processRawOrLabelHeadersArgument($rawOrLabelHeaders);
        $data['exportCheckboxLabel']    = $this->processExportCheckboxLabelArgument($exportCheckboxLabel);
        $data['exportSurveyFields']     = $this->processExportSurveyFieldsArgument($exportSurveyFields);
        $data['exportDataAccessGroups'] = $this->processExportDataAccessGroupsArgument($exportDataAccessGroups);
        
        $data['filterLogic'] = $this->processFilterLogicArgument($filterLogic);
        
        #---------------------------------------
        # Get the records and process them
        #---------------------------------------
        $records = $this->connection->callWithArray($data);
        $records = $this->processExportResult($records, $format);
      
        return $records;
    }

    /**
     * Export records using an array parameter, where the keys of the array
     * passed to this method are the argument names, and the values are the
     * argument values. The argument names to use correspond to the variable
     * names in the exportRecords method.
     *
     * Example usage:
     *
     * <code>
     * # return all records with last name "Smith" in CSV format
     * $records = $project->exportRecordsAp(['format' => 'csv', 'filterLogic' => "[last_name] = 'Smith'"]);
     *
     * # export only records that have record ID 1001, 1002, or 1003
     * $result = $project->exportRecordsAp(['recordIds' => [1001, 1002, 1003]]);
     *
     * # export only the fields on the 'lab_data' form and field 'study_id'
     * $records = $project->exportRecordsAp(['forms' => ['lab_data'], 'fields' => ['study_id']]);
     * </code>
     *
     * @see exportRecords()
     *
     * @param array $argumentArray array of arguments.
     * @return mixed the specified records.
     */
    public function exportRecordsAp($arrayParameter = [])
    {
        if (func_num_args() > 1) {
            $message = __METHOD__.'() was called with '.func_num_args().' arguments, but '
                    .' it accepts at most 1 argument.';
            throw new PhpCapException($message, PhpCapException::TOO_MANY_ARGUMENTS);
        } elseif (!isset($arrayParameter)) {
            $arrayParameter = [];
        } elseif (!is_array($arrayParameter)) {
            $message = 'The argument has type "'
                    .gettype($arrayParameter)
                    .'", but it needs to be an array.';
            throw new PhpCapException($message, PhpCapException::INVALID_ARGUMENT);
        }
        
        $num = 1;
        foreach ($arrayParameter as $name => $value) {
            if (gettype($name) !== 'string') {
                $message = 'Argument name number '.$num.' in the array argument has type '
                        .gettype($name).', but it needs to be a string.';
                throw new PhpCapException($message, PhpCapException::INVALID_ARGUMENT);
            }
            
            switch ($name) {
                case 'format':
                    $format = $value;
                    break;
                case 'type':
                    $type = $value;
                    break;
                case 'recordIds':
                    $recordIds = $value;
                    break;
                case 'fields':
                    $fields = $value;
                    break;
                case 'forms':
                    $forms = $value;
                    break;
                case 'events':
                    $events = $value;
                    break;
                case 'filterLogic':
                    $filterLogic = $value;
                    break;
                case 'rawOrLabel':
                    $rawOrLabel = $value;
                    break;
                case 'rawOrLabelHeaders':
                    $rawOrLabelHeaders = $value;
                    break;
                case 'exportCheckboxLabel':
                    $exportCheckboxLabel = $value;
                    break;
                case 'exportSurveyFields':
                    $exportSurveyFields = $value;
                    break;
                case 'exportDataAccessGroups':
                    $exportDataAccessGroups = $value;
                    break;
                default:
                    throw new PhpCapException(
                        'Unrecognized argument name "' . $name . '".',
                        PhpCapException::INVALID_ARGUMENT
                    );
            }
            $num++;
        }
        
        $records = $this->exportRecords(
            isset($format)                 ? $format                 : 'php',
            isset($type)                   ? $type                   : 'flat',
            isset($recordIds)              ? $recordIds              : null,
            isset($fields)                 ? $fields                 : null,
            isset($forms)                  ? $forms                  : null,
            isset($events)                 ? $events                 : null,
            isset($filterLogic)            ? $filterLogic            : null,
            isset($rawOrLabel)             ? $rawOrLabel             : 'raw',
            isset($rawOrLabelHeaders)      ? $rawOrLabelHeaders      : 'raw',
            isset($exportCheckboxLabel)    ? $exportCheckboxLabel    : false,
            isset($exportSurveyFields)     ? $exportSurveyFields     : false,
            isset($exportDataAccessGroups) ? $exportDataAccessGroups : false
        );
        
        return $records;
    }
    
    /**
     * Exports the records produced by the specified report.
     *
     * @param mixed $reportId integer or numeric string ID of the report to use.
     * @param string $format output data format.
     * @param string $rawOrLabel
     * @param string $rawOrLabelHeaders
     * @param string $exportCheckboxLabel
     * @return mixed the records generated by the speficied report in the specified format.
     */
    public function exportReports(
        $reportId,
        $format = 'php',
        $rawOrLabel = 'raw',
        $rawOrLabelHeaders = 'raw',
        $exportCheckboxLabel = false
    ) {
        $data = array(
                'token' => $this->apiToken,
                'content' => 'report',
                'returnFormat' => 'json'
        );
        
        #------------------------------------------------
        # Process arguments
        #------------------------------------------------
        $data['report_id'] = $this->processReportIdArgument($reportId);

        $legalFormats = array('csv', 'json', 'php', 'xml');
        $data['format'] = $this->processFormatArgument($format, $legalFormats);

        $data['rawOrLabel']          = $this->processRawOrLabelArgument($rawOrLabel);
        $data['rawOrLabelHeaders']   = $this->processRawOrLabelHeadersArgument($rawOrLabelHeaders);
        $data['exportCheckboxLabel'] = $this->processExportCheckboxLabelArgument($exportCheckboxLabel);
        
        #---------------------------------------------------
        # Get and process records
        #---------------------------------------------------
        $records = $this->connection->callWithArray($data);
        $records = $this->processExportResult($records, $format);
         
        return $records;
    }
    
    /**
     * Exports the specified file.
     *
     * @param string $recordId the record ID for the file to be exported.
     * @param string $field the name of the field containing the file to export.
     * @param string $event name of event for file export (for longitudinal studies).
     * @param string $repeatInstance
     * @return string the contents of the file that was exported.
     */
    public function exportFile($recordId, $field, $event = null, $repeatInstance = null)
    {
        $data = array(
                'token'        => $this->apiToken,
                'content'      => 'file',
                'action'       => 'export',
                'returnFormat' => 'json'
        );
        
        #--------------------------------------------
        # Process arguments
        #--------------------------------------------
        $data['record']           = $this->processRecordIdArgument($recordId);
        $data['field']            = $this->processFieldArgument($field);
        $data['event']            = $this->processEventArgument($event);
        $data['repeat_instance']  = $this->processRepeatInstanceArgument($repeatInstance);
        
        #-------------------------------
        # Get and process file
        #-------------------------------
        $file = $this->connection->callWithArray($data);
        $file = $this->processExportResult($file, $format = 'file');
        
        return $file;
    }
    
    /**
     * Exports the numbers and names of the arms in the project.
     *
     * @param $format string the format used to export the arm data.
     *
     * @return mixed For 'php' format, array of arrays that have the following keys:
     *     <ul>
     *       <li>'arm_num'</li>
     *       <li>'name'</li>
     *     </ul>
     */
    public function exportArms($format = 'php', $arms = [])
    {
        $data = array(
                'token' => $this->apiToken,
                'content' => 'arm',
                'returnFormat' => 'json'
        );
        
        $legalFormats = array('csv', 'json', 'php', 'xml');
        $data['format'] = $this->processFormatArgument($format, $legalFormats);
        $data['arms']   = $this->processArmsArgument($arms);
        
        $arms = $this->connection->callWithArray($data);
        $arms = $this->processExportResult($arms, $format);
        
        return $arms;
    }
    
    /**
     * Exports information about the specified events.
     *
     * Example usage:
     * <code>
     * #export information about all events in CSV (Comma-Separated Values) format.
     * $eventInfo = $project->exportEvents('csv');
     * </code>
     *
     * @param string $format the format for the export.
     * @param array $arms the arms to export.
     * @return array information about the specified events.
     */
    public function exportEvents($format = 'php', $arms = [])
    {
        $data = array(
                'token' => $this->apiToken,
                'content' => 'event',
                'returnFormat' => 'json'
        );
        
        #---------------------------------------
        # Process arguments
        #---------------------------------------
        $legalFormats = array('csv', 'json', 'php', 'xml');
        $data['format'] = $this->processFormatArgument($format, $legalFormats);
        $data['arms'] = $this->processArmsArgument($arms);

        
        #------------------------------------------------------
        # Get and process events
        #------------------------------------------------------
        $events = $this->connection->callWithArray($data);
        $events = $this->processExportResult($events, $format);

        return $events;
    }
    
    
    
    /**
     * Exports information about the project, e.g., project ID, project title, creation time.
     *
     * @param $format string
     *
     * @return array associative array (map) of project information. See REDCap API documentation
     *         for a list of the fields, or use the print_r function on the results of this method.
     */
    public function exportProjectInfo($format = 'php')
    {
        $data = array(
                'token'        => $this->apiToken,
                'content'      => 'project',
                'returnFormat' => 'json'
        );
        
        #---------------------------------------
        # Process format
        #---------------------------------------
        $legalFormats = array('csv', 'json', 'php', 'xml');
        $data['format'] = $this->processFormatArgument($format, $legalFormats);
        
        #---------------------------------------
        # Get and process project information
        #---------------------------------------
        $projectInfo = $this->connection->callWithArray($data);
        $projectInfo = $this->processExportResult($projectInfo, $format);
        
        return $projectInfo;
    }

    
    /**
     * Exports metadata about the project, i.e., information about the fields in the project.
     *
     * @param $format string
     *
     * @return array associative array (map) of metatdata for the project, which consists of
     *         information about each field. Some examples of the information
     *         provided are: 'field_name', 'form_name', 'field_type', 'field_label'.
     *         See REDCap API documentation
     *         for more information, or use the print_r function on the results of this method.
     */
    public function exportMetadata($format = 'php', $fields = [], $forms = [])
    {
        $data = array(
                'token' => $this->apiToken,
                'content' => 'metadata',
                'returnFormat' => 'json'
        );
        
        #---------------------------------------
        # Process format
        #---------------------------------------
        $legalFormats = array('csv', 'json', 'php', 'xml');
        $data['format'] = $this->processFormatArgument($format, $legalFormats);
        $data['forms']  = $this->processFormsArgument($forms);
        $data['fields'] = $this->processFieldsArgument($fields);
        
        #-------------------------------------------
        # Get and process metadata
        #-------------------------------------------
        $metadata = $this->connection->callWithArray($data);
        $metadata = $this->processExportResult($metadata, $format);
        
        return $metadata;
    }
    
    /**
     * Gets the REDCap version number of the instance being used by the project.
     *
     * @return string the REDCap version number of the instance being used by the project.
     */
    public function exportRedcapVersion()
    {
        $data = array(
                'token' => $this->apiToken,
                'content' => 'version'
        );
        $redcapVersion = $this->connection->callWithArray($data);
        
        return $redcapVersion;
    }
    
    /**
     * Exports information about the instruments (data entry forms) for the project.
     *
     * Example usage:
     * <code>
     * $instruments = $project->getInstruments();
     * foreach ($instruments as $instrumentName => $instrumentLabel) {
     *     print "{$instrumentName} : {$instrumentLabel}\n";
     * }
     * </code>
     *
     * @param $format string format instruments are exported in:
     *     <ul>
     *       <li>'php' - returns data as a PHP array [default]</li>
     *       <li>'csv' - string of CSV (comma-separated values)</li>
     *       <li>'json' - string of JSON encoded data</li>
     *       <li>'xml' - string of XML encoded data</li>
     *     </ul>
     * @return mixed For the 'php' format, and array map of instrument names to instrument labels is returned.
     *     For all other formats a string is returned.
     */
    public function exportInstruments($format = 'php')
    {
        $data = array(
                'token'       => $this->apiToken,
                'content'     => 'instrument',
                'returnFormat' => 'json'
        );

        $legalFormats = array('csv', 'json', 'php', 'xml');
        $data['format'] = $this->processFormatArgument($format, $legalFormats);
        
        $instrumentsData = $this->connection->callWithArray($data);

        $instrumentsData = $this->processExportResult($instrumentsData, $format);
            
        #------------------------------------------------------
        # If format is 'php', reformat the data as
        # a map from "instrument name" to "instrument label"
        #------------------------------------------------------
        if ($format == 'php') {
            $instruments = array ();
            foreach ($instrumentsData as $instr) {
                $instruments [$instr ['instrument_name']] = $instr ['instrument_label'];
            }
        } else {
            $instruments = $instrumentsData;
        }
        
        return $instruments;
    }
    
    /**
     * Gets the instrument to event mapping for the project.
     *
     * For example, the following code:
     * <code>
     * $map = $project->exportInstrumentEventMappings();
     * print_r($map[0]); # print first element of map
     * </code>
     * might generate the following output:
     * <pre>
     * Array
     * (
     *     [arm_num] => 1
     *     [unique_event_name] => enrollment_arm_1
     *     [form] => demographics
     * )
     * </pre>
     *
     * @return arrray an array of arrays that have the following keys:
     *     <ul>
     *       <li>'arm_num'</li>
     *       <li>'unique_event_name'</li>
     *       <li>'form'</li>
     *     </ul>
     */
    public function exportInstrumentEventMappings($format = 'php', $arms = [])
    {
        $data = array(
                'token'        => $this->apiToken,
                'content'      => 'formEventMapping',
                'format'       => 'json',
                'returnFormat' => 'json'
        );
        
        #------------------------------------------
        # Process arguments
        #------------------------------------------
        $legalFormats = array('csv', 'json', 'php', 'xml');
        $data['format'] = $this->processFormatArgument($format, $legalFormats);
        $data['arms']   = $this->processArmsArgument($arms);
        
        #---------------------------------------------
        # Get and process instrument-event mappings
        #---------------------------------------------
        $instrumentEventMappings = $this->connection->callWithArray($data);
        $instrumentEventMappings = $this->processExportResult($instrumentEventMappings, $format);
          
        return $instrumentEventMappings;
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
     *            <ul>
     *              <li> 'flat' - each data element is a record</li>
     *              <li> 'eav' - each data element is one value</li>
     *            </ul>
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
     * @return mixed if 'count' was specified for 'returnContent', then an integer will
     *         be returned that is the number of records imported.
     *         If 'ids' was specified, then an array of record IDs that were imported will
     *         be returned.
     */
    public function importRecords(
        $records,
        $format = 'php',
        $type = 'flat',
        $overwriteBehavior = 'normal',
        $returnContent = 'count',
        $dateFormat = 'YMD'
    ) {
        
        $data = array (
                'token'         => $this->apiToken,
                'content'       => 'record',
                'returnFormat'  => 'json'
        );
        
        #---------------------------------------
        # Process format
        #---------------------------------------
        $legalFormats = array('csv', 'json', 'odm', 'php', 'xml');
        $data['format'] = $this->processFormatArgument($format, $legalFormats);
        $data['data']   = $this->processRecordsArgument($records, $format);
        $data['type']   = $this->processTypeArgument($type);
        
        $data['overwriteBehavior'] = $this->processOverwriteBehaviorArgument($overwriteBehavior);
        $data['returnContent']     = $this->processReturnContentArgument($returnContent);
        $data['dateFormat']        = $this->processDateFormatArgument($dateFormat);
        
        $result = $this->connection->callWithArray($data);
        
        $this->processNonExportResult($result);
        

        #--------------------------------------------------------------------------
        # Process result, which should either be a count of the records imported,
        # or a list of the record IDs that were imported
        #
        # The result should be a string in JSON for all formats.
        # Need to convert the result to a PHP data structure.
        #--------------------------------------------------------------------------
        $phpResult = json_decode($result, true); // true => return as array instead of object
            
        $jsonError = json_last_error();
            
        switch ($jsonError) {
            case JSON_ERROR_NONE:
                $result = $phpResult;
                # If this is a count, then just return the count, and not an
                # array that has a count index with the count
                if (isset($result) && is_array($result) && array_key_exists('count', $result)) {
                    $result = $result['count'];
                }
                break;
            default:
                # Hopefully the REDCap API will always return valid JSON, and this
                # will never happen.
                $message =  'JSON error ('.$jsonError.') "'.json_last_error_msg().
                    '" while processing import return value: "'.
                    $result.'".';
                throw new PhpCapException($message, PhpCapException::JSON_ERROR);
                break;
        }
                
        return $result;
    }
    
   
    /**
     * Imports the file into the field of the record (with the specified event, if any).
     *
     * @param string $filename the name of the file to import.
     * @param string $recordId the record ID of the record to import the file into.
     * @param string $field the field of the record to import the file into.
     * @param string $event the event of the record to import the file into.
     * @param string $repeatInstance
     *
     * @throws PhpCapException
     */
    public function importFile($filename, $recordId, $field, $event = null, $repeatInstance = null)
    {
        $data = array (
                'token'        => $this->apiToken,
                'content'      => 'file',
                'action'       => 'import',
                'returnFormat' => 'json'
        );
        
        #----------------------------------------
        # Process non-file arguments
        #----------------------------------------
        $data['file']             = $this->processFilenameArgument($filename);
        $data['record']           = $this->processRecordIdArgument($recordId);
        $data['field']            = $this->processFieldArgument($field);
        $data['event']            = $this->processEventArgument($event);
        $data['repeat_instance']  = $this->processRepeatInstanceArgument($repeatInstance);

 
        #---------------------------------------------------------------------
        # For unknown reasons, "call" (instead of "callWithArray") needs to
        # be used here (probably something to do with the 'file' data).
        # REDCap's "API Playground" (also) makes no data conversion for this
        # method.
        #---------------------------------------------------------------------
        $result = $this->connection->call($data);
        
        $this->processNonExportResult($result);
    }
    

    /**
     * Deletes the specified records from the project.
     *
     * @param array $recordIds array of record IDs to delete
     * @throws PhpCapException
     * @return integer the number of records deleted.
     */
    public function deleteRecords($recordIds, $arm = null)
    {
        $data = array (
                'token'        => $this->apiToken,
                'content'      => 'record',
                'action'       => 'delete',
                'returnFormat' => 'json',
        );

        $data['records'] = $this->processRecordIdsArgument($recordIds);
        $data['arm']     = $this->processArmArgument($arm);
        
        $result = $this->connection->callWithArray($data);
        
        $this->processNonExportResult($result);
        
        return $result;
    }
    
    /**
     * Deletes the specified file.
     *
     * @param string $recordId the record ID of the file to delete.
     * @param string $field the field name of the file to delete.
     * @param string $event the event of the file to delete.
     * @param string $repeatInstance
     */
    public function deleteFile($recordId, $field, $event = null, $repeatInstance = null)
    {
        $data = array (
                'token'        => $this->apiToken,
                'content'      => 'file',
                'action'       => 'delete',
                'returnFormat' => 'json'
        );
        
        #----------------------------------------
        # Process arguments
        #----------------------------------------
        $data['record']           = $this->processRecordIdArgument($recordId);
        $data['field']            = $this->processFieldArgument($field);
        $data['event']            = $this->processEventArgument($event);
        $data['repeat_instance']  = $this->processRepeatInstanceArgument($repeatInstance);
        
        $result = $this->connection->callWithArray($data);
        
        $this->processNonExportResult($result);
       
        return $result;
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
    public function getCallInfo()
    {
        $callInfo = $this->connection->getCallInfo();
    
        return $callInfo;
    }
    
    
    
    /**
     * Gets the timeout in seconds for calls to the REDCap API.
     *
     * @return integer timeout in seconds for cURL calls.
     */
    public function getTimeoutInSeconds()
    {
        return $this->connection->getTimeoutInSeconds();
    }
    
    /**
     * Sets the timeout for calls to the REDCap API to the specified number of seconds.
     *
     * @param integer $timeoutInSeconds timeout in seconds for cURL calls.
     */
    public function setTimeoutInSeconds($timeoutInSeconds)
    {
        $this->connection->setTimeoutInSeconds($timeoutInSeconds);
    }
    
    /**
     * Returns the underlying REDCap API connection being used by the project.
     * This can be used to make calls to the REDCap API, possibly to access functionality
     * not supported by PHPCap.
     *
     * @return RedCapApiConnection the underlying REDCap API connection being
     *         used by the project.
     */
    public function getConnection()
    {
        return $this->connection;
    }
 
    /**
     * Reads the contents of the specified file and returns it as a string.
     *
     * @param string $filename the name of the file that is to be read.
     * @throws PhpCapException if an error occurs while trying to read the file.
     * @return string the contents of the specified file.
     */
    public static function fileToString($filename)
    {
        if (!file_exists($filename)) {
            throw new PhpCapException(
                'The input file "'.$filename.'" could not be found.',
                PhpCapException::INPUT_FILE_NOT_FOUND
            );
        } elseif (!is_readable($filename)) {
            throw new PhpCapException(
                'The input file "'.$filename.'" was unreadable.',
                PhpCapException::INPUT_FILE_UNREADABLE
            );
        }
        
        $contents = file_get_contents($filename);

        if ($contents === false) {
            $error = error_get_last();
            $errorMessage = null;
            if ($error != null && array_key_exists('message', $error)) {
                $errorMessage = $error['message'];
            }
            
            if (isset($errorMessage)) {
                throw new PhpCapException(
                    'An error occurred in input file "'.$filename.'": '.$errorMessage,
                    PhpCapException::INPUT_FILE_ERROR
                );
            } else {
                throw new PhpCapException(
                    'An error occurred in input file "'.$filename.'"',
                    PhpCapException::INPUT_FILE_ERROR
                );
            }
        }
        
        return $contents;
    }
 
    /**
     * Writes the specified string to the specified file.
     *
     * @param string $string the string to write to the file.
     * @param string $filename the name of the file to write the string.
     * @param boolean $append if true, the file is appended if it already exists. If false,
     *        the file is created if it doesn't exist, and overwritten if it does.
     * @throws PhpCapException if an error occurs.
     * @return mixed false on failure, and the number of bytes written on success.
     */
    public static function writeStringToFile($string, $filename, $append = false)
    {
        $result = false;
        if ($append === true) {
            $result = file_put_contents($filename, $string, FILE_APPEND);
        } else {
            $result = file_put_contents($filename, $string);
        }
        
        if ($result === false) {
            $error = error_get_last();
            $errorMessage = null;
            if ($error != null && array_key_exists('message', $error)) {
                $errorMessage = $error['message'];
            }
            
            if (isset($errorMessage)) {
                throw new PhpCapException(
                    'An error occurred in output file "'.$filename.'": '.$errorMessage,
                    PhpCapException::OUTPUT_FILE_ERROR
                );
            } else {
                throw new PhpCapException(
                    'An error occurred in output file "'.$filename.'"',
                    PhpCapException::OUTPUT_FILE_ERROR
                );
            }
        }
            
        return $result;
    }
    
    /**
     * Appends the specified string to the specified file.
     *
     * @param string $string the string to append.
     * @param string $filename the name of the file that is appended.
     * @return mixed false on failure, and the number of bytes appended on success.
     */
    public static function appendStringToFile($string, $filename)
    {
        $result = self::writeStringToFile($string, $filename, true);
        return $result;
    }
    
    /**
     * Processes an export result from the REDCap API.
     *
     * @param string $result
     * @param unknown $format
     * @throws PhpCapException
     */
    private function processExportResult(& $result, $format)
    {
        if ($format == 'php') {
            $phpResult = json_decode($result, true); // true => return as array instead of object
                
            $jsonError = json_last_error();
                
            switch ($jsonError) {
                case JSON_ERROR_NONE:
                    $result = $phpResult;
                    break;
                default:
                    throw new PhpCapException(
                        "JSON error (" . $jsonError . ") \"" . json_last_error_msg() .
                        "\" in REDCap API output." .
                        "\nThe first 1,000 characters of output returned from REDCap are:\n" .
                        substr($result, 0, 1000),
                        PhpCapException::JSON_ERROR
                    );
                    break;
            }
                
            if (array_key_exists('error', $result)) {
                throw new PhpCapException($result ['error'], PhpCapException::REDCAP_API_ERROR);
            }
        } else {
            // If this is a format other than 'php', look for a JSON error, because
            // all formats return errors as JSON
            $matches = array();
            $hasMatch = preg_match('/^[\s]*{"error":"([^"]+)"}[\s]*$/', $result, $matches);
            if ($hasMatch === 1) {
                // note: $matches[0] is the complete string that matched
                //       $matches[1] is just the error message part
                $message = $matches[1];
                throw new PhpCapException($message, PhpCapException::REDCAP_API_ERROR);
            }
        }
        
        return $result;
    }
    
    private function processNonExportResult(& $result)
    {
        $matches = array();
        $hasMatch = preg_match('/^[\s]*{"error":"([^"]+)"}[\s]*$/', $result, $matches);
        if ($hasMatch === 1) {
            // note: $matches[0] is the complete string that matched
            //       $matches[1] is just the error message part
            $message = $matches[1];
            throw new PhpCapException($message, PhpCapException::REDCAP_API_ERROR);
        }
    }
    
    private function processFormatArgument(& $format, $legalFormats)
    {
        if (!isset($format)) {
            $format = 'php';
        }
        
        if (gettype($format) !== 'string') {
            $message = 'The format specified has type "'.gettype($format).'", but it should be a string.';
            throw new PhpCapException($message, PhpCapException::INVALID_ARGUMENT);
        }
        
        $format = strtolower(trim($format));
        
        if (!in_array($format, $legalFormats)) {
            $message = 'Invalid format "'.$format.'" specified.'
                .' The format should be one of the following: "'.
                implode('", "', $legalFormats).'".';
            throw new PhpCapException($message, PhpCapException::INVALID_ARGUMENT);
        }
        
        $dataFormat = '';
        if (strcmp($format, 'php') === 0) {
            $dataFormat = 'json';
        } else {
            $dataFormat = $format;
        }
        
        return $dataFormat;
    }
    
    private function processTypeArgument($type)
    {
        if (!isset($type)) {
            $type = 'flat';
        }
        $type = strtolower(trim($type));
        
        if (strcmp($type, 'flat') !== 0 && strcmp($type, 'eav') !== 0) {
            throw new PhpCapException(
                "Invalid type '".$type."' specified. Type should be either 'flat' or 'eav'",
                PhpCapException::INVALID_ARGUMENT
            );
        }
        return $type;
    }
    
    private function processRecordIdsArgument($recordIds)
    {
        if (!isset($recordIds)) {
            $recordIds = array();
        } else {
            if (!is_array($recordIds)) {
                throw new PhpCapException(
                    'The record IDs argument has type "'.gettype($recordIds).'"; it should be an array.',
                    PhpCapException::INVALID_ARGUMENT
                );
            } else {
                foreach ($recordIds as $recordId) {
                    $type = gettype($recordId);
                    if (strcmp($type, 'integer') !== 0 && strcmp($type, 'string') !== 0) {
                        $message = 'A record ID with type "'.$type.'" was found.'.
                                ' Record IDs should be integers or strings.';
                        throw new PhpCapException($message, PhpCapException::INVALID_ARGUMENT);
                    }
                }
            }
        }
        return $recordIds;
    }
    
    
    private function processFieldsArgument($fields)
    {
        if (!isset($fields)) {
            $fields = array();
        } else {
            if (!is_array($fields)) {
                throw new PhpCapException(
                    'Argument "fields" has the wrong type; it should be an array.',
                    PhpCapException::INVALID_ARGUMENT
                );
            } else {
                foreach ($fields as $field) {
                    $type = gettype($field);
                    if (strcmp($type, 'string') !== 0) {
                        $message = 'A field with type "'.$type.'" was found in the fields array.'.
                                ' Fields should be strings.';
                        throw new PhpCapException($message, PhpCapException::INVALID_ARGUMENT);
                    }
                }
            }
        }
        
        return $fields;
    }
    
    private function processFieldArgument($field)
    {
        if (!isset($field)) {
            $message = 'No field was specified.';
            throw new PhpCapException($message, PhpCapException::INVALID_ARGUMENT);
        } elseif (gettype($field) !== 'string') {
            $message = 'Field has type "'.gettype($field).'", but should be a string.';
            throw new PhpCapException($message, PhpCapException::INVALID_ARGUMENT);
        }
        return $field;
    }
    
    
    private function processFormsArgument($forms)
    {
        if (!isset($forms)) {
            $forms = array();
        } else {
            if (!is_array($forms)) {
                throw new PhpCapException(
                    'The forms argument has invalid type "'.gettype($forms).'"; it should be an array.',
                    PhpCapException::INVALID_ARGUMENT
                );
            } else {
                foreach ($forms as $form) {
                    $type = gettype($form);
                    if (strcmp($type, 'string') !== 0) {
                        $message = 'A form with type "'.$type.'" was found in the forms array.'.
                                ' Forms should be strings.';
                        throw new PhpCapException($message, PhpCapException::INVALID_ARGUMENT);
                    }
                }
            }
        }
    
        return $forms;
    }
    
    private function processEventsArgument($events)
    {
        if (!isset($events)) {
            $events = array();
        } else {
            if (!is_array($events)) {
                throw new PhpCapException(
                    'The events argument has invalid type "'.gettype($events).'"; it should be an array.',
                    PhpCapException::INVALID_ARGUMENT
                );
            } else {
                foreach ($events as $event) {
                    $type = gettype($event);
                    if (strcmp($type, 'string') !== 0) {
                        $message = 'An event with type "'.$type.'" was found in the events array.'.
                                ' Events should be strings.';
                        throw new PhpCapException($message, PhpCapException::INVALID_ARGUMENT);
                    }
                }
            }
        }
    
        return $events;
    }


    private function processEventArgument($event)
    {
        if (!isset($event)) {
            ; // This might be OK
        } elseif (gettype($event) !== 'string') {
            $message = 'Event has type "'.gettype($event).'", but should be a string.';
            throw new PhpCapException($message, PhpCapException::INVALID_ARGUMENT);
        }
        return $event;
    }
    
    private function processRawOrLabelArgument($rawOrLabel)
    {
        if (!isset($rawOrLabel)) {
            $rawOrLabel = 'raw';
        } else {
            if ($rawOrLabel !== 'raw' && $rawOrLabel !== 'label') {
                throw new PhpCapException(
                    'Invalid value "'.$rawOrLabel.'" specified for rawOrLabel.'.
                    " Valid values are 'raw' and 'label'.",
                    PhpCapException::INVALID_ARGUMENT
                );
            }
        }
        return $rawOrLabel;
    }
    

    private function processRawOrLabelHeadersArgument($rawOrLabelHeaders)
    {
        if (!isset($rawOrLabelHeaders)) {
            $rawOrLabelHeaders = 'raw';
        } else {
            if ($rawOrLabelHeaders !== 'raw' && $rawOrLabelHeaders !== 'label') {
                throw new PhpCapException(
                    'Invalid value "'.$rawOrLabelHeaders.'" specified for rawOrLabelHeaders.'.
                    " Valid values are 'raw' and 'label'.",
                    PhpCapException::INVALID_ARGUMENT
                );
            }
        }
        return $rawOrLabelHeaders;
    }
    
    private function processExportCheckboxLabelArgument($exportCheckboxLabel)
    {
        if ($exportCheckboxLabel == null) {
            $exportCheckboxLabel = false;
        } else {
            if (gettype($exportCheckboxLabel) !== 'boolean') {
                throw new PhpCapException(
                    'Invalid type for exportCheckboxLabel. It should be a boolean (true or false),'
                    .' but has type: '.gettype($exportCheckboxLabel).'.',
                    PhpCapException::INVALID_ARGUMENT
                );
            }
        }
        return $exportCheckboxLabel;
    }
    

    private function processExportSurveyFieldsArgument($exportSurveyFields)
    {
        if ($exportSurveyFields == null) {
            $exportSurveyFields = false;
        } else {
            if (gettype($exportSurveyFields) !== 'boolean') {
                throw new PhpCapException(
                    'Invalid type for exportSurveyFields. It should be a boolean (true or false),'
                    .' but has type: '.gettype($exportSurveyFields).'.',
                    PhpCapException::INVALID_ARGUMENT
                );
            }
        }
        return $exportSurveyFields;
    }
    
    private function processExportDataAccessGroupsArgument($exportDataAccessGroups)
    {
        if ($exportDataAccessGroups == null) {
            $exportDataAccessGroups = false;
        } else {
            if (gettype($exportDataAccessGroups) !== 'boolean') {
                throw new PhpCapException(
                    'Invalid type for exportDataAccessGroups. It should be a boolean (true or false),'
                    .' but has type: '.gettype($exportDataAccessGroups).'.',
                    PhpCapException::INVALID_ARGUMENT
                );
            }
        }
        return $exportDataAccessGroups;
    }
    
    private function processFilterLogicArgument($filterLogic)
    {
        if ($filterLogic == null) {
            $filterLogic = '';
        } else {
            if (gettype($filterLogic) !== 'string') {
                throw new PhpCapException(
                    'Invalid type for filterLogic. It should be a string, but has type "'.gettype($filterLogic).'".',
                    PhpCapException::INVALID_ARGUMENT
                );
            }
        }
        return $filterLogic;
    }
    
    private function processReportIdArgument($reportId)
    {
        if (!isset($reportId)) {
            throw new PhpCapException("No report ID specified for export.", PhpCapException::INVALID_ARGUMENT);
        }

        if (is_string($reportId)) {
            if (!preg_match('/^[0-9]+$/', $reportId)) {
                throw new PhpCapException(
                    'Report ID "'.$reportId.'" is non-numeric string.',
                    PhpCapException::INVALID_ARGUMENT
                );
            }
        } elseif (is_int($reportId)) {
            if ($reportId < 0) {
                $message = 'Report ID "'.$reportId.'" is a negative integer.';
                throw new PhpCapException($message, PhpCapException::INVALID_ARGUMENT);
            }
        } else {
            $message = 'The report ID has type "'.gettype($reportId).
            '", but it should be an integer or a (numeric) string.';
            throw new PhpCapException($message, PhpCapException::INVALID_ARGUMENT);
        }
        
        return $reportId;
    }
    
    private function processArmsArgument($arms)
    {
        if (!isset($arms)) {
            $arms = array();
        } else {
            if (!is_array($arms)) {
                throw new PhpCapException(
                    'The arms argument has invalid type "'.gettype($arms).'"; it should be an array.',
                    PhpCapException::INVALID_ARGUMENT
                );
            }
        }
        
        foreach ($arms as $arm) {
            if (is_string($arm)) {
                if (! preg_match('/^[0-9]+$/', $arm)) {
                    throw new PhpCapException(
                        'Arm number "' . $arm . '" is non-numeric string.',
                        PhpCapException::INVALID_ARGUMENT
                    );
                }
            } elseif (is_int($arm)) {
                if ($arm < 0) {
                    throw new PhpCapException(
                        'Arm number "' . $arm . '" is a negative integer.',
                        PhpCapException::INVALID_ARGUMENT
                    );
                }
            } else {
                $message = 'An arm was found in the arms array that has type "'.gettype($arm).
                    '"; it should be an integer or a (numeric) string.';
                throw new PhpCapException($message, PhpCapException::INVALID_ARGUMENT);
            }
        }
        
        return $arms;
    }
    
    private function processArmArgument($arm)
    {
        if (!isset($arm)) {
            ;  // That's OK
        } elseif (is_string($arm)) {
            if (! preg_match('/^[0-9]+$/', $arm)) {
                throw new PhpCapException(
                    'Arm number "' . $arm . '" is non-numeric string.',
                    PhpCapException::INVALID_ARGUMENT
                );
            }
        } elseif (is_int($arm)) {
            if ($arm < 0) {
                throw new PhpCapException(
                    'Arm number "' . $arm . '" is a negative integer.',
                    PhpCapException::INVALID_ARGUMENT
                );
            }
        } else {
            $message = 'The arm argument has type "'.gettype($arm).
                '"; it should be an integer or a (numeric) string.';
            throw new PhpCapException($message, PhpCapException::INVALID_ARGUMENT);
        }
            
        return $arm;
    }
    
    private function processRecordIdArgument($recordId)
    {
        if (!isset($recordId)) {
            throw new PhpCapException("No record ID specified.", PhpCapException::INVALID_ARGUMENT);
        }
    
        if (!is_string($recordId) && !is_int($recordId)) {
            $message = 'The record ID has type "'.gettype($recordId).
                '", but it should be a string or integer.';
            throw new PhpCapException($message, PhpCapException::INVALID_ARGUMENT);
        }
    
        return $recordId;
    }
    
    private function processRepeatInstanceArgument($repeatInstance)
    {
        if (!isset($repeatInstance)) {
            ; // Might be OK
        } elseif (!is_string($repeatInstance) && !is_int($repeatInstance)) {
            $message = 'The repeat instance has type "'.gettype($repeatInstance).
            '", but it should be a string or integer.';
            throw new PhpCapException($message, PhpCapException::INVALID_ARGUMENT);
        }
    
        return $repeatInstance;
    }
    
    private function processFilenameArgument($filename)
    {
        if (!isset($filename)) {
            $message = 'No filename specified.';
            throw new PhpCapException($message, PhpCapException::INVALID_ARGUMENT);
        } elseif (gettype($filename) !== 'string') {
            $message = "Argument 'filename' has type '".gettype($filename)."', but should be a string.";
            throw new PhpCapException($message, PhpCapException::INVALID_ARGUMENT);
        } elseif (!file_exists($filename)) {
            throw new PhpCapException(
                'The input file "'.$filename.'" could not be found.',
                PhpCapException::INPUT_FILE_NOT_FOUND
            );
        } elseif (!is_readable($filename)) {
            throw new PhpCapException(
                'The input file "'.$filename.'" was unreadable.',
                PhpCapException::INPUT_FILE_UNREADABLE
            );
        }
       
        $basename = pathinfo($filename, PATHINFO_BASENAME);
        $curlFile = curl_file_create($filename, 'text/plain', $basename);
        
        return $curlFile;
    }
    
    private function processRecordsArgument($records, $format)
    {
        if (!isset($records)) {
            $message = 'No records specified.';
            throw new PhpCapException($message, PhpCapException::INVALID_ARGUMENT);
        } elseif ($format === 'php') {
            if (!is_array($records)) {
                $message = "Argument 'records' has type '".gettype($records)."', but should be an array.";
                throw new PhpCapException($message, PhpCapException::INVALID_ARGUMENT);
            }
            $records = json_encode($records);
            
            $jsonError = json_last_error();
            
            switch ($jsonError) {
                case JSON_ERROR_NONE:
                    break;
                default:
                    $message =  'JSON error ('.$jsonError.') "'. json_last_error_msg().
                            '" while processing records argument.';
                    throw new PhpCapException($message, PhpCapException::JSON_ERROR);
                    break;
            }
        } else { // All other formats
            if (gettype($records) !== 'string') {
                $message = "Argument 'records' has type '".gettype($records)."', but should be a string.";
                throw new PhpCapException($message, PhpCapException::INVALID_ARGUMENT);
            }
        }
    
        return $records;
    }
    
    private function processOverwriteBehaviorArgument($overwriteBehavior)
    {
        if (!isset($overwriteBehavior)) {
            $overwriteBehavior = 'normal';
        } elseif ($overwriteBehavior !== 'normal' && $overwriteBehavior !== 'overwrite') {
            $message = 'Invalid value "'.$overwriteBehavior.'" specified for overwriteBehavior.'.
                    " Valid values are 'normal' and 'overwrite'.";
            throw new PhpCapException($message, PhpCapException::INVALID_ARGUMENT);
        }
    
        return $overwriteBehavior;
    }
    
    private function processDateFormatArgument($dateFormat)
    {
        if (!isset($dateFormat)) {
            $dateFormat = 'YMD';
        } else {
            if (gettype($dateFormat) === 'string') {
                $dateFormat = strtoupper($dateFormat);
            }
            
            $legalDateFormats = ['MDY', 'DMY', 'YMD'];
            if (!in_array($dateFormat, $legalDateFormats)) {
                $message = 'Invalid date format "'.$dateFormat.'" specified.'
                        .' The date format should be one of the following: "'.
                        implode('", "', $legalDateFormats).'".';
                throw new PhpCapException($message, PhpCapException::INVALID_ARGUMENT);
            }
        }
    
        return $dateFormat;
    }
    

    private function processReturnContentArgument($returnContent)
    {
        if (!isset($returnContent)) {
            $overwriteBehavior = 'count';
        } elseif ($returnContent !== 'count' && $returnContent !== 'ids') {
            $message = 'Invalid value "'.$returnContent.'" specified for overwriteBehavior.'.
                    " Valid values are 'count' and 'ids'.";
            throw new PhpCapException($message, PhpCapException::INVALID_ARGUMENT);
        }
    
        return $returnContent;
    }
}
