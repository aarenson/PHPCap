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
    private $projectInfo;
    private $metadata;
 
    
    /**
     * Contructs a REDCap project for the specifed information.
     * 
     * @param string $apiUrl the URL for the API for the REDCap that has the project.
     * @param string $apiToken the API token for this project.
     * @param boolean $sslVerify indicates if SSL connection to REDCap web site should be verified.
     * @param string $caCertificateFile the full path name of the CA (Certificate Authority) certificate file.
     */
    public function __construct($apiUrl, $apiToken, $sslVerify = false, $caCertificateFile = '') {
        
        #----------------------------------------------------------------------------------------
        # Process the REDCAp API URL
        # Note: standard PHP URL validation will fail for non-ASCII URLs (so it was not used)
        #----------------------------------------------------------------------------------------
        if (!isset($apiUrl)) {
            throw new PhpCapException("The REDCap API URL spefied for the project was null or blank."
                    . gettype($apiUrl), PhpCapException::ILLEGAL_ARGUMENT);
        }
        elseif (gettype($apiUrl) !== 'string') {
            throw new PhpCapException("The REDCap API URL provided (".$apiUrl.") should be a string, but has type: "
                    . gettype($apiUrl), PhpCapException::ILLEGAL_ARGUMENT);    
        }
        $this->apiURL = $apiUrl;
        
        
        // ctype_xdigit - check token for hexidecimal
        
        // Process the REDCap API token
        if (!isset($apiToken)) {
            throw new PhpCapException("The REDCap API token spefied for the project was null or blank."
                    . gettype($apiToken), PhpCapException::ILLEGAL_ARGUMENT);
        }
        elseif (gettype($apiToken) !== 'string') {
            throw new PhpCapException("The REDCap API token provided should be a string, but has type: "
                    . gettype($apiToken), PhpCapException::ILLEGAL_ARGUMENT);
        }
        elseif (!ctype_xdigit($apiToken)) {   // ctype_xdigit - check token for hexidecimal
            throw new PhpCapException("The REDCap API token has an invalid format."
                    ." It should only contain numbers and ther letter A, B, C, D, E and F."
                    , PhpCapException::ILLEGAL_ARGUMENT);
        }
        elseif (strlen($apiToken) != 32 && strlen($apiToken) != 64) {
            throw new PhpCapException("The REDCap API token has an invalid format."
                    . " It has a length of ".strlen($apiToken)." characters, but should have a length of"
                    . " 32 or 64 characters (if a super token is being used)."
                    , PhpCapException::ILLEGAL_ARGUMENT);
        }
        $this->apiToken = $apiToken;
        
        $this->sslVerify         = $sslVerify;
        if (isset($sslVerify) && gettype($sslVerify) !== 'boolean') {
            throw new PhpCapException('The value for $sslVerify be a boolean (true/false), but has type: '
                    . gettype($sslVerify), PhpCapException::ILLEGAL_ARGUMENT);
        }
        
        $this->caCertificateFile = $caCertificateFile;
        
        $this->connection = new RedCapApiConnection($apiUrl, $sslVerify, $caCertificateFile);
    }
    
    public function exportRecords(&$callInfo = NULL) {
        
    }
    
    /**
     * Exports information about the project, e.g., project ID, project title, creation time.
     * 
     * @param array $callInfo optional output parameter used to return call information,
     *                        for example: URL, content type, total time. 
     *                        
     * @return array associative array (map) of call information field names to values. Example fields
     * names include 'total_time', 'size_upload', 'size_download'.
     * 
     * @see http://php.net/manual/en/function.curl-getinfo.php description of the fields returned
     */
    public function exportProjectInfo(&$callInfo = null) {
        $data = array(
                'token' => $this->apiToken,
                'content' => 'project',
                'format' => 'json',
                'returnFormat' => 'json'
        );
        $callData = http_build_query($data, '', '&');
        $projectInfo = $this->connection->call($callData, $callInfo);

        if (empty($projectInfo)) {
            $projectInfo = array ();
        } 
        else {
            $projectInfo = json_decode($projectInfo, true);   // true => return as array instead of object
        }
        
        return $projectInfo;
    }


    public function exportMetadata(&$callInfo = null) {
        $data = array(
                'token' => $this->apiToken,
                'content' => 'metadata',
                'format' => 'json',
                'returnFormat' => 'json'
        );
        $callData = http_build_query($data, '', '&');
        $projectInfo = $this->connection->call($callData, $callInfo);
    
        if (empty($projectInfo)) {
            $projectInfo = array ();
        }
        else {
            $projectInfo = json_decode($projectInfo, true);   // true => return as array instead of object
        }
    
        return $projectInfo;
    }
}