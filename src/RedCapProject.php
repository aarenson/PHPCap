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
     * Contructs a REDCap project for the specifed information.
     *
     * @param string $apiUrl the URL for the API for the REDCap that has the project.
     * @param string $apiToken the API token for this project.
     * @param boolean $sslVerify indicates if SSL connection to REDCap web site should be verified.
     * @param string $caCertificateFile the full path name of the CA (Certificate Authority) certificate file.
     *
     * @throws PHPCapException if any of the arguments are invalid
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
     * @param string $format the format in which to export the records:
     *     <ul>
     *       <li> 'php' - array of maps of values [default]</li>
     *       <li> 'csv' - string of CSV (comma-separated values)</li>
     *       <li> 'json' - string of JSON encoded values</li>
     *       <li> 'xml' - string of XML encoded data</li>
     *       <li> 'odm' - string with CDISC ODM XML format, specifically ODM version 1.3.1</li>
     *     </ul>
     * @param string $type the type of records exported: 'flat' or 'eav'.
     *         'flat' exports one record per row. 'eav' exports one data point per row, so,
     *         for non-longitudinal studies, each record will have the following
     *         fields: record_id, field_name, value. For longitudinal studies, each record
     *         will have the fields: record_id, field_name, value, redcap_event_name.
     * @param array $recordIds array of strings with record id's that are to be retrieved.
     * @param array $fields array of field names to export
     * @param array $forms array of form names for which fields should be exported
     * @param array $events array of event names for which fields should be exported
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
     * @param boolean $exportCheckBoxLabel
     * @param boolean $exportSurveyFields
     * @param boolean $exportDataAccessGroups
     * @param array $filterLogic logic used to restrict the records retrieved, e.g.,
     *         "[last_name] = 'Smith'".
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
        $rawOrLabel = 'raw',
        $rawOrLabelHeaders = 'raw',
        $exportCheckboxLabel = false,
        $exportSurveyFields = false,
        $exportDataAccessGroups = false,
        $filterLogic = null
    ) {
        $data = array(
                'token'        => $this->apiToken,
                'content'      => 'record',
                'format'       => 'json',
                'returnFormat' => 'json'
        );

        #---------------------------------------
        # Process format
        #---------------------------------------
        if ($format == null) {
            $format = 'php';
        }
        $format = strtolower(trim($format));
            
        $legalFormats = array('php', 'csv', 'json', 'xml', 'odm');
            
        if (!in_array($format, $legalFormats)) {
            throw new PhpCapException("Illegal format '".$format."' specified.", PhpCapException::INVALID_ARGUMENT);
        }
 
        if (strcmp($format, 'php') === 0) {
            $data['format'] = 'json';
        } else {
            $data['format'] = $format;
        }
        
        #----------------------------------
        # Process type
        #----------------------------------
        $type = strtolower(trim($type));
        if ($type != null && strcmp($type, 'flat') !== 0 && strcmp($type, 'eav') !== 0) {
            throw new PhpCapException(
                "Invalid type \"".$type."\". Type should be either 'flat' or 'eav'",
                PhpCapException::INVALID_ARGUMENT
            );
        }
        $data['type'] = $type;
        
        #-----------------------------------------
        # Process record IDs
        #-----------------------------------------
        if ($recordIds != null) {
            if (!is_array($recordIds)) {
                throw new PhpCapException(
                    "recordIds has the wrong type; it should be an array.",
                    PhpCapException::INVALID_ARGUMENT
                );
            }
            $data['records'] = $recordIds;
        }
        
        #----------------------------------
        # Process fields
        #----------------------------------
        if ($fields != null) {
            if (!is_array($fields)) {
                throw new PhpCapException(
                    'Argument "fields" has the wrong type; it should be an array.',
                    PhpCapException::INVALID_ARGUMENT
                );
            }
            $data['fields'] = $fields;
        }
        
        #---------------------------------------
        # Process forms
        #---------------------------------------
        if ($forms != null) {
            if (!is_array($forms)) {
                throw new PhpCapException(
                    'Argument "forms" has the wrong type; it should be an array.',
                    PhpCapException::INVALID_ARGUMENT
                );
            }
            $data['forms'] = $forms;
        }
        
        #------------------------------------
        # Process events
        #------------------------------------
        if ($events != null) {
            if (!is_array($events)) {
                throw new PhpCapException(
                    'Argument "events" has the wrong type; it should be an array.',
                    PhpCapException::INVALID_ARGUMENT
                );
            }
            $data['events'] = $events;
        }
        
        #------------------------------------------
        # Process rawOrLabel
        #------------------------------------------
        if ($rawOrLabel != null) {
            if ($rawOrLabel != 'raw' && $rawOrLabel != 'label') {
                throw new PhpCapException(
                    'Invalid value "'.$rawOrLabel.'" specified for rawOrLabel.'.
                    " Valid values are 'raw' and 'label'.",
                    PhpCapException::INVALID_ARGUMENT
                );
            }
            $data['rawOrLabel'] = $rawOrLabel;
        }

        #------------------------------------------
        # Process rawOrLabelHeaders
        #------------------------------------------
        if ($rawOrLabelHeaders != null) {
            if ($rawOrLabelHeaders != 'raw' && $rawOrLabelHeaders != 'label') {
                throw new PhpCapException(
                    'Invalid value "'.$rawOrLabelHeaders.'" specified for rawOrLabelHeaders.'.
                    " Valid values are 'raw' and 'label'.",
                    PhpCapException::INVALID_ARGUMENT
                );
            }
            $data['rawOrLabel'] = $rawOrLabelHeaders;
        }
        
        #---------------------------------------
        # Process exportCheckboxLabel
        #---------------------------------------
        if ($exportCheckboxLabel != null) {
            if (gettype($exportCheckboxLabel) != 'boolean') {
                throw new PhpCapException(
                    'Invalid type for exportCheckBoxLabel. It should be a boolean,'
                    .' but has type: '.gettype($exportCheckboxLabel).'.',
                    PhpCapException::INVALID_ARGUMENT
                );
            }
            $data['exportCheckboxLabel'] = $exportCheckboxLabel;
        }
        
        #---------------------------------------
        # Process exportSurveyFields
        #---------------------------------------
        if ($exportSurveyFields != null) {
            if (gettype($exportSurveyFields) != 'boolean') {
                throw new PhpCapException(
                    'Invalid type for filterLogic. It should be a boolean,'
                    .' but has type: '.gettype($exportSurveyFields).'.',
                    PhpCapException::INVALID_ARGUMENT
                );
            }
            $data['exportSurveyFields'] = $exportSurveyFields;
        }
             
        #---------------------------------------
        # Process exportDataAccessGroups
        #---------------------------------------
        if ($exportDataAccessGroups != null) {
            if (gettype($exportDataAccessGroups) != 'boolean') {
                throw new PhpCapException(
                    'Invalid type for filterLogic. It should be a boolean,'
                    .' but has type: '.gettype($exportDataAccessGroups).'.',
                    PhpCapException::INVALID_ARGUMENT
                );
            }
            $data['exportDataAccessGroups'] = $exportDataAccessGroups;
        }
        
        
        #----------------------------------------
        # Process filter logic
        #----------------------------------------
        if ($filterLogic != null) {
            if (gettype($filterLogic) != 'string') {
                throw new PhpCapException(
                    'Invalid type for filterLogic. It should be a string, but has type: '.gettype($filterlogic).'.',
                    PhpCapException::INVALID_ARGUMENT
                );
            }
            $data['filterLogic'] = $filterLogic;
        }
        
        
        # actually get the records
        $records = $this->connection->callWithArray($data);
        
        # if the 'php' format was used, convert the JSON records returned into a PHP arrray
        if (strcmp($format, 'php') === 0) {
            $records = $this->processJsonExport($records);
        }
     
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
    public function exportRecordsAp($arrayParameter)
    {
        foreach ($arrayParameter as $name => $value) {
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
                case 'rawOrLabel':
                    $rawOrLabel = $value;
                    break;
                case 'rawOrLabelHeaders':
                    $rawOrLabelHeaders = $value;
                    break;
                case 'exportCheckBoxLabel':
                    $exportCheckBoxLabel = $value;
                    break;
                case 'exportSurveyFields':
                    $exportSurveyFields = $value;
                    break;
                case 'exportDataAccessGroups':
                    $exportDataAccessGroups = $value;
                    break;
                case 'filterLogic':
                    $filterLogic = $value;
                    break;
                default:
                    throw new PhpCapException(
                        'Unrecognized argument name "' . $name . '".',
                        PhpCapException::INVALID_ARGUMENT
                    );
            }
        }
        
        $records = $this->exportRecords(
            isset($format)                 ? $format                 : null,
            isset($type)                   ? $type                   : null,
            isset($recordIds)              ? $recordIds              : null,
            isset($fields)                 ? $fields                 : null,
            isset($forms)                  ? $forms                  : null,
            isset($events)                 ? $events                 : null,
            isset($rawOrLabel)             ? $rawOrLabel             : null,
            isset($rawOrLabelHeaders)      ? $rawOrLabelHeaders      : null,
            isset($exportCheckBoxLabel)    ? $exportCheckBoxLabel    : null,
            isset($exportSurveyFields)     ? $exportSurveyFields     : null,
            isset($exportDataAccessGroups) ? $exportDataAccessGroups : null,
            isset($filterLogic)            ? $filterLogic            : null
        );
        
        return $records;
    }
    
    
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
        # Process report ID
        #------------------------------------------------
        if (!isset($reportId)) {
            throw new PhpCapException("No report ID specified for export.", PhpCapException::INVALID_ARGUMENT);
        }
        
        if (is_string($reportId) && !preg_match('/^[0-9]+$/', $reportId)) {
            throw new PhpCapException(
                'Report ID "'.$reportId.'" is non-numeric string.',
                PhpCapException::INVALID_ARGUMENT
            );
        } elseif (is_int($reportId) && $reportId < 0) {
            throw new PhpCapException(
                'Report ID "'.$reportId.'" is a negative integer.',
                PhpCapException::INVALID_ARGUMENT
            );
        }
        
        $data['report_id'] = $reportId;
        
        #----------------------------------------------
        # Process format
        #----------------------------------------------
        $legalFormats = array('php', 'csv', 'json', 'xml');

        if (!in_array($format, $legalFormats)) {
            throw new PhpCapException("Illegal format '".$format."' specified.", PhpCapException::INVALID_ARGUMENT);
        } elseif ($format === 'php') {
            $data['format'] = 'json';
        } else {
            $data['format'] = $format;
        }
        
        #------------------------------------------
        # Process rawOrLabel
        #------------------------------------------
        if ($rawOrLabel != null) {
            if ($rawOrLabel != 'raw' && $rawOrLabel != 'label') {
                throw new PhpCapException(
                    'Invalid value "'.$rawOrLabel.'" specified for rawOrLabel.'.
                    " Valid values are 'raw' and 'label'.",
                    PhpCapException::INVALID_ARGUMENT
                );
            }
            $data['rawOrLabel'] = $rawOrLabel;
        }
        
        #------------------------------------------
        # Process rawOrLabelHeaders
        #------------------------------------------
        if ($rawOrLabelHeaders != null) {
            if ($rawOrLabelHeaders != 'raw' && $rawOrLabelHeaders != 'label') {
                throw new PhpCapException(
                    'Invalid value "'.$rawOrLabelHeaders.'" specified for rawOrLabelHeaders.'.
                    " Valid values are 'raw' and 'label'.",
                    PhpCapException::INVALID_ARGUMENT
                );
            }
            $data['rawOrLabel'] = $rawOrLabelHeaders;
        }
        
        #---------------------------------------
        # Process exportCheckBoxLabel
        #---------------------------------------
        if ($exportCheckboxLabel != null) {
            if (gettype($exportCheckboxLabel) != 'boolean') {
                throw new PhpCapException(
                    'Invalid type for exportCheckBoxLabel. It should be a boolean,'
                    .' but has type: '.gettype($exportCheckboxLabel).'.',
                    PhpCapException::INVALID_ARGUMENT
                );
            }
            $data['exportCheckBoxLabel'] = $exportCheckboxLabel;
        }
        
        $records = $this->connection->callWithArray($data);
        
        # if the 'php' format was used, convert the JSON records returned into a PHP arrray
        if (strcmp($format, 'php') === 0) {
            $records = $this->processJsonExport($records);
        }
         
        return $records;
    }
    
    
    /**
     * Exports the numbers and names of the arms in the project.
     *
     * @return array an array of arrays that have the following keys:
     *     <ul>
     *       <li>'arm_num'</li>
     *       <li>'name'</li>
     *     </ul>
     */
    public function exportArms()
    {
        $data = array(
                'token' => $this->apiToken,
                'content' => 'arm',
                'format' => 'json',
                'returnFormat' => 'json'
        );
        $arms = $this->connection->callWithArray($data);
        $arms = $this->processJsonExport($arms);
        
        return $arms;
    }
    
    
    
    /**
     * Exports information about the project, e.g., project ID, project title, creation time.
     *
     * @return array associative array (map) of project information. See REDCap API documentation
     *         for a list of the fields, or use the print_r function on the results of this method.
     */
    public function exportProjectInfo()
    {
        $data = array(
                'token' => $this->apiToken,
                'content' => 'project',
                'format' => 'json',
                'returnFormat' => 'json'
        );
        $projectInfo = $this->connection->callWithArray($data);
        $projectInfo = $this->processJsonExport($projectInfo);
        
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
    public function exportMetadata()
    {
        $data = array(
                'token' => $this->apiToken,
                'content' => 'metadata',
                'format' => 'json',
                'returnFormat' => 'json'
        );
        $metadata = $this->connection->callWithArray($data);
        $metadata = $this->processJsonExport($metadata);
    
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
        $legalFormats = array('php', 'csv', 'json', 'xml');
        
        $data = array(
                'token'       => $this->apiToken,
                'content'     => 'instrument',
                'format'      => 'json',
                'returnFormat' => 'json'
        );

        $instrumentsData = $this->connection->callWithArray($data);
        
        if (!in_array($format, $legalFormats)) {
            throw new PhpCapException("Illegal format '".$format."' specified.", PhpCapException::INVALID_ARGUMENT);
        } elseif ($format === 'php') {
            $instrumentData = $this->processJsonExport($instrumentsData);
        } else {
            $data['format'] = $format;
        }

        
        #-------------------------------------------
        # Reformat the data as a map from
        # "instrument name" to "instrument label"
        #-------------------------------------------
        $instruments = array();
        foreach ($instrumentData as $instr) {
                $instruments[$instr['instrument_name']] = $instr['instrument_label'];
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
    public function exportInstrumentEventMappings()
    {
        $data = array(
                'token'       => $this->apiToken,
                'content'     => 'formEventMapping',
                'format'      => 'json',
                'returnFormat' => 'json'
        );
        $instrumentEventMappings = $this->connection->callWithArray($data);
        $instrumentEventMappings = $this->processJsonExport($instrumentEventMappings);
          
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
     * @return mixed
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
                'token'   => $this->apiToken,
                'content' => 'record',
                'format'  => $format,
                'type'    => $type,
                
                'overwriteBehavior' => $overwriteBehavior,
                'returnFormat'      => 'json',
                'dateFormat'        => $dateFormat
        );
        
        if ($records == null) {
            throw new PhpCapException("No records specified for import.", PhpCapException::INVALID_ARGUMENT);
        }
        
        # Need to check format first, and this code will depend on format
        #else {if (gettype($records) != 'string') {
        #    throw new PhpCapException("No records specified for import.", PhpCapException::INVALID_ARGUMENT);
        #}
        
        #---------------------------------------
        # Process format
        #---------------------------------------
        if ($format == null) {
            $format = 'php';
        }
        $format = strtolower(trim($format));
        
        $legalFormats = array('php', 'csv', 'json', 'xml', 'odm');
        
        if (!in_array($format, $legalFormats)) {
            throw new PhpCapException("Illegal format '".$format."' specified.", PhpCapException::INVALID_ARGUMENT);
        }
        
        if (strcmp($format, 'php') === 0) {
            $data['format'] = 'json';
        } else {
            $data['format'] = $format;
        }
        

        
        
        // If the PHP format was used, need to convert to JSON
        if ($format === 'php') {
            $records = json_encode($records);
            $data['format'] = 'json';
        }
        
        $data ['data'] = $records;
        
        $result = $this->connection->callWithArray($data);
        
        // Check $result for errors ...
        
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
    public function importFile($filename, $recordId, $field, $event = '')
    {
        if (!file_exists($filename)) {
            throw new PHPCapException(
                'The input file "'.$filename.'" could not be found.',
                PhpCapException::INPUT_FILE_NOT_FOUND
            );
        } elseif (!is_readble($filename)) {
            throw new PHPCapException(
                'The input file "'.$filename.'" was unreadable.',
                PhpCapException::INPUT_FILE_NOT_FOUND
            );
        }
        
        $data = array (
                'token'        => $this->apiToken,
                'content'      => 'file',
                'action'       => 'import',
                'returnFormat' => 'json'
        );
        
        $data['file']   = $filename;
        $data['record'] = $recordId;
        $data['field']  = $field;
        if (isset($event)) {
            $data['event']  = $event;
        }
        
        $callData = http_build_query($data, '', '&');
        $jsonResult = $this->connection->call($callData);
        
        if (isset($jsonResult)) {
            $result = json_decode($jsonResult, true);
            if (array_key_exists('error', $result)) {
                throw new PHPCapException(
                    'The input file "'.$filename.'" cased the following error: '.$result['error'],
                    PhpCapException::INPUT_FILE_ERROR
                );
            }
        }
    }
    

    /**
     * Deletes the specified records from the project.
     *
     * @param array $recordIds array of record IDs to delete
     * @throws PhpCapException
     * @return integer the number of records deleted.
     */
    public function deleteRecords($recordIds)
    {
        $data = array (
                'token'        => $this->apiToken,
                'content'      => 'record',
                'action'       => 'delete',
                'returnFormat' => 'json',
                'records'      => $recordIds
        );
        
        $callData = http_build_query($data, '', '&');
        $result = $this->connection->call($callData);
        
        if (strpos($result, 'error') !== false) {
            $decodedResult = json_decode($result, true);
            if (array_key_exists('error', $decodedResult)) {
                throw new PhpCapException($decodedResult ['error'], PhpCapException::REDCAP_API_ERROR);
            } else {
                throw new PhpCapException("Unrecognized error: " . $result, PhpCapException::REDCAP_API_ERROR);
            }
        }
        
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
        return $this->connection->getTimeoutInSeconds;
    }
    
    /**
     * Sets the timeout for calls to the REDCap API to the specified number of seconds.
     *
     * @param integer $timeoutInSeconds timeout in seconds for cURL calls.
     */
    public function setTimeoutInSeconds($timeoutInSeconds)
    {
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
    public function getConnection()
    {
        return $this->connection;
    }
 
    /**
     * Reads the contents of the specified file and returns it as a string.
     *
     * @param string $filename the name of the file that is to be read.
     * @throws PHPCapException if an error occurs while trying to read the file.
     * @return string the contents of the specified file.
     */
    public static function fileToString($filename)
    {
        if (!file_exists($filename)) {
            throw new PHPCapException(
                'The input file "'.$filename.'" could not be found.',
                PhpCapException::INPUT_FILE_NOT_FOUND
            );
        } elseif (!is_readable($filename)) {
            throw new PHPCapException(
                'The input file "'.$filename.'" was unreadable.',
                PhpCapException::INPUT_FILE_NOT_FOUND
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
                throw new PHPCapException(
                    'An error occurred in input file "'.$filename.'": '.$errorMessage,
                    PhpCapException::INPUT_FILE_ERROR
                );
            } else {
                throw new PHPCapException(
                    'An error occurred in input file "'.$filename.'"',
                    PhpCapException::INPUT_FILE_ERROR
                );
            }
        }
        
        return $contents;
    }
 
    
    public static function writeStringToFile($string, $filename, $append = false)
    {
        $result = true;
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
                throw new PHPCapException(
                    'An error occurred in output file "'.$filename.'": '.$errorMessage,
                    PhpCapException::OUTPUT_FILE_ERROR
                );
            } else {
                throw new PHPCapException(
                    'An error occurred in output file "'.$filename.'"',
                    PhpCapException::OUTPUT_FILE_ERROR
                );
            }
        }
            
        return $result;
    }
    
    public static function appendStringToFile($string, $filename)
    {
        $result = self::writeStringToFile($string, $filename, true);
        return $result;
    }
    
    
    /**
     * Processes JSON exported from REDCap.
     *
     * @param string $jsonRecords
     * @return array processed JSON records.
     * @throws PHPCapException if an error occurs.
     */
    private function processJsonExport($jsonRecords)
    {
        if (empty($jsonRecords)) {
            $records = array ();
        } else {
            $records = json_decode($jsonRecords, true); // true => return as array instead of object
            
            $jsonError = json_last_error();
            
            switch ($jsonError) {
                case JSON_ERROR_NONE:
                    break;
                default:
                    throw new PHPCapException(
                        "JSON error (".$jsonError.") \""
                        .json_last_error_msg()."\" in REDCap API output."
                        ."\nThe first 1,000 characters of output returned from REDCap are:\n"
                        .substr($jsonRecords, 0, 1000),
                        PhpCapException::JSON_ERROR
                    );
                    break;
            }
            
            if (array_key_exists('error', $records)) {
                throw new PhpCapException($records['error'], PhpCapException::REDCAP_API_ERROR);
            }
        }
        
        return $records;
    }
}
