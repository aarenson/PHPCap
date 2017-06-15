<?php

namespace IU\PHPCap;

/**
 * REDCap class used to represent a REDCap instance/site. This class
 * is typically only useful if your progam needs to create
 * REDCap projects and/or needs to access more than one
 * REDCap project.
 */
class RedCap
{
    protected $superToken;
    
    /** connection to the REDCap API at the $apiURL. */
    protected $connection;
 
    protected $errorHandler;
    
    public function __construct(
        $apiUrl,
        $superToken = null,
        $sslVerify = false,
        $caCertificateFile = null,
        $errorHandler = null,
        $connection = null
    ) {
        # Need to set errorHandler to default to start in case there is an
        # error with the errorHandler passed as an argument
        # (to be able to handle that error!)
        $this->errorHandler = new ErrorHandler();
        if (isset($errorHandler)) {
            $this->errorHandler = $this->processErrorHandlerArgument($errorHandler);
        }
    
        if (isset($connection)) {
            $this->connection = $this->processConnectionArgument();
        } else {
            $apiUrl    = $this->processApiUrlArgument($apiUrl);
            $sslVerify = $this->processSslVerifyArgument($sslVerify);
            $caCertificateFile = $this->processCaCertificateFileArgument($caCertificateFile);
        
            $this->connection = new RedCapApiConnection($apiUrl, $sslVerify, $caCertificateFile);
        }
        
        $this->superToken = $this->processSuperTokenArgument($superToken);
    }

    
 
    /**
     * Creates a REDCap project with the specified data.
     *
     * The data fields that can be set are as follows:
     * <ul>
     *   <li>
     *     <b>project_title</b> - the title of the project.
     *   </li>
     *   <li>
     *     <b>purpose</b> - the purpose of the project:
     *     <ul>
     *       <li>0 - Practice/Just for fun</li>
     *       <li>1 - Other</li>
     *       <li>2 - Research</li>
     *       <li>3 - Quality Improvement</li>
     *       <li>4 - Operational Support</li>
     *     </ul>
     *   </li>
     *   <li>
     *     <b>purpose_other</b> - text descibing purpose if purpose above is specified as 1.
     *   </li>
     *   <li>
     *     <b>project_notes</b> - notes about the project.
     *   </li>
     *   <li>
     *     <b>is_longitudinal</b> - indicates if the project is longitudinal (0 = False [default],
     *     1 = True).
     *   </li>
     *   <li>
     *     <b>surveys_enabled</b> - indicates if surveys are enabled (0 = False [default], 1 = True).
     *   </li>
     *   <li>
     *     <b>record_autonumbering_enabled</b> - indicates id record autonumbering is enabled
     *     (0 = False [default], 1 = True).
     *   </li>
     * </ul>
     *
     * @param mixed $projectData the data used for project creation. Note that if
     *     'php' format is used, the data needs to be an array where the keys are
     *     the field names and the values are the field values.
     * @param string $format
     * @param unknown $odm
     * @return unknown
     */
    public function createProject(
        $projectData,
        $format = 'php',
        $odm = null
    ) {
        // Note: might want to clone error handler, in case state variables
        // have been added that should differ for different uses, e.g.,
        // a user message that is displayed where you have multiple project
        // objects
        $data = array(
                'token'        => $this->superToken,
                'content'      => 'project',
                'returnFormat' => 'json'
        );
        
        #---------------------------------------------
        # Process the arguments
        #---------------------------------------------
        $legalFormats = array('csv', 'json', 'php', 'xml');
        $data['format'] = $this->processFormatArgument($format, $legalFormats);
        $data['data']   = $this->processImportDataArgument($projectData, 'projectData', $format);
        
        if (isset($odm)) {
            $data['odm'] = $odm;
        }
        
        #---------------------------------------
        # Create the project
        #---------------------------------------
        $apiToken = $this->connection->callWithArray($data);
        
        $this->processNonExportResult($apiToken);
        
        $connection   = clone $this->connection;
        $errorHandler = clone $this->errorHandler;
        
        $project = new RedCapProject(
            $apiUrl = null,
            $apiToken,
            $sslVerify = null,
            $caCertificateFile = null,
            $errorHandler,
            $connection
        );
        
        return $project;
    }
    
    /**
     * Gets the REDCap project for the specified API token.
     *
     * @param string $apiToken the API token for the project to get.
     *
     * @return \IU\PHPCap\RedCapProject the project for the specified API token.
     */
    public function getProject($apiToken)
    {
        $connection   = clone $this->connection;
        $errorHandler = clone $this->errorHandler;
        
        $project = new RedCapProject(
            $apiUrl = null,
            $apiToken,
            $sslVerify = null,
            $caCertificateFile = null,
            $errorHandler,
            $connection
        );
        
        return $project;
    }
    
    
    protected function processApiUrlArgument($apiUrl)
    {
        # Note: standard PHP URL validation will fail for non-ASCII URLs (so it was not used)
        if (!isset($apiUrl)) {
            $message = 'The REDCap API URL specified for the project was null or blank.';
            $code    = ErrorHandlerInterface::INVALID_ARGUMENT;
            $this->errorHandler->throwException($message, $code);
        } elseif (gettype($apiUrl) !== 'string') {
            $message = 'The REDCap API URL provided ('.$apiUrl.') should be a string, but has type: '
                . gettype($apiUrl);
            $code = ErrorHandlerInterface::INVALID_ARGUMENT;
            $this->errorHandler->throwException($message, $code);
        }
        return $apiUrl;
    }
    
    
    protected function processCaCertificateFileArgument($caCertificateFile)
    {
        if (isset($caCertificateFile) && gettype($caCertificateFile) !== 'string') {
            $message = 'The value for $sslVerify must be a string, but has type: '
                .gettype($caCertificateFile);
                $code    = ErrorHandlerInterface::INVALID_ARGUMENT;
                $this->errorHandler->throwException($message, $code);
        }
        return $caCertificateFile;
    }
    
    protected function processConnectionArgument($connection)
    {
        if (!($connection instanceof RedCapApiConnectionInterface)) {
            $message = 'The connection argument is not valid, because it doesn\'t implement '
                .RedCapApiConnectionInterface::class.'.';
                $code = ErrorHandlerInterface::INVALID_ARGUMENT;
                $this->errorHandler->throwException($message, $code);
        }
        return $connection;
    }
    
    protected function processFormatArgument(& $format, $legalFormats)
    {
        if (!isset($format)) {
            $format = 'php';
        }
        
        if (gettype($format) !== 'string') {
            $message = 'The format specified has type "'.gettype($format).'", but it should be a string.';
            $this->errorHandler->throwException($message, ErrorHandlerInterface::INVALID_ARGUMENT);
        }
        
        $format = strtolower(trim($format));
        
        if (!in_array($format, $legalFormats)) {
            $message = 'Invalid format "'.$format.'" specified.'
                .' The format should be one of the following: "'.
                implode('", "', $legalFormats).'".';
                $this->errorHandler->throwException($message, ErrorHandlerInterface::INVALID_ARGUMENT);
        }
        
        $dataFormat = '';
        if (strcmp($format, 'php') === 0) {
            $dataFormat = 'json';
        } else {
            $dataFormat = $format;
        }
        
        return $dataFormat;
    }
    
    protected function processImportDataArgument($data, $dataName, $format)
    {
        if (!isset($data)) {
            $message = "No value specified for required argument '".$dataName."'.";
            $this->errorHandler->throwException($message, ErrorHandlerInterface::INVALID_ARGUMENT);
        } elseif ($format === 'php') {
            if (!is_array($data)) {
                $message = "Argument '".$dataName."' has type '".gettype($data)."'"
                    .", but should be an array.";
                    $this->errorHandler->throwException($message, ErrorHandlerInterface::INVALID_ARGUMENT);
            }
            $data = array($data); // Needs to be an array within an array to work
            $data = json_encode($data);
            
            $jsonError = json_last_error();
            
            switch ($jsonError) {
                case JSON_ERROR_NONE:
                    break;
                default:
                    $message =  'JSON error ('.$jsonError.') "'. json_last_error_msg().
                    '"'." while processing argument '".$dataName."'.";
                    $this->errorHandler->throwException($message, ErrorHandlerInterface::JSON_ERROR);
                    break;
            }
        } else { // All other formats
            if (gettype($data) !== 'string') {
                $message = "Argument '".$dataName."' has type '".gettype($data)."'"
                    .", but should be a string.";
                    $this->errorHandler->throwException($message, ErrorHandlerInterface::INVALID_ARGUMENT);
            }
        }
        
        return $data;
    }
    
    protected function processNonExportResult(& $result)
    {
        $matches = array();
        $hasMatch = preg_match('/^[\s]*{"error":"([^"]+)"}[\s]*$/', $result, $matches);
        if ($hasMatch === 1) {
            // note: $matches[0] is the complete string that matched
            //       $matches[1] is just the error message part
            $message = $matches[1];
            $this->errorHandler->throwException($message, ErrorHandlerInterface::REDCAP_API_ERROR);
        }
    }
    
    
    protected function processSslVerifyArgument($sslVerify)
    {
        if (isset($sslVerify) && gettype($sslVerify) !== 'boolean') {
            $message = 'The value for $sslVerify must be a boolean (true/false), but has type: '
                .gettype($sslVerify);
                $code = ErrorHandlerInterface::INVALID_ARGUMENT;
                $this->errorHandler->throwException($message, $code);
        }
        return $sslVerify;
    }
    
    
    protected function processSuperTokenArgument($superToken)
    {
        if (!isset($superToken)) {
            ;  // OK; just means that createProject can't be used
        } elseif (gettype($superToken) !== 'string') {
            $this->errorHandler->throwException("The REDCap super token provided should be a string, but has type: "
                . gettype($superToken), ErrorHandlerInterface::INVALID_ARGUMENT);
        } elseif (!ctype_xdigit($superToken)) {   // ctype_xdigit - check token for hexidecimal
            $this->errorHandler->throwException(
                "The REDCap super token has an invalid format."
                ." It should only contain numbers and the letters A, B, C, D, E and F.",
                ErrorHandlerInterface::INVALID_ARGUMENT
            );
        } elseif (strlen($superToken) != 64) {
            $this->errorHandler->throwException(
                "The REDCap super token has an invalid format."
                . " It has a length of ".strlen($superToken)." characters, but should have a length of"
                . " 64 characters.",
                ErrorHandlerInterface::INVALID_ARGUMENT
            );
        }
        
        return $superToken;
    }
}
