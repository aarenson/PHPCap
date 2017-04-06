<?php
/**
 * This file contains the PHPCapException class.
 */

namespace IU\PHPCap;

/**
 * Exception class for PHPCap exceptions. This is the exception that PHPCap will
 * throw when it encounters an error.
 * 
 * Example usage:
 * 
 * ```php
 * ...
 * try {
 *     $projectInfo = $project->exportProjectInfo();
 * }
 * catch (PhpCapException $exception) {
 *     print "The following error occurred: {$exception->getMessage()}\n";
 *     print "Error code: {$exception->getCode()}\n";
 *     $curlErrorNumber = $exception->getCurlErrorNumber();
 *     if (isset($curlErrorNumber)) {
 *         print "A cURL error occurred.\n";
 *         print "cURL error number: {$curlErrorNumber}\n";
 *     }
 *     print "Stack trace:\n{$exception->getTraceAsString()}\n";
 * }
 * ...
 * ```
 * 
 * @see http://php.net/manual/en/class.exception.php 
 *         Information on additional methods provided by parent class Exception.
 */
class PhpCapException extends \Exception
{
    // Error codes
    
    /** Invalid argument passed to a PHPCap method. */
    const INVALID_ARGUMENT = 1;
    
    /** A cURL error occurred. */
    const CURL_ERROR = 2;
    
    /** A CA certificate file was specified, but it could not be found. */
    const CA_CERTIFICATE_FILE_NOT_FOUND = 3;
    
    const CA_CERTIFICATE_FILE_UNREADABLE = 4;
    const INVALID_URL = 5;
    const REDCAP_API_EXCEPTION = 6;   // An error response from the REDCap API
    const JSON_ERROR = 7;
    
    
    /** @var integer|null cURL error number */
    private $curlErrorNumber;
    
    /** @var integer|null HTTP status code */
    private $httpStatusCode;
    
    /**
     * Constructor.
     * 
     * @param string $message the error message.
     * @param integer $code the error code.
     * @param integer $curlErrorNumber the cURL error number (set to null if no cURL error occurred).
     * @param integer $httpStatusCode the HTTP status code (set to null if no HTTP status code was returned).
     * @param \Exception $previous the previous exception
     */
    public function __construct($message, $code, $curlErrorNumber = null, $httpStatusCode = null, $previous = null) {
        parent::__construct($message, $code, $previous);
        $this->curlErrorNumber  = $curlErrorNumber;
        $this->httpStatusCode = $httpStatusCode;
    }
    
    
    /**
     * Returns the cURL error number, or null if no cURL error occurred.
     * 
     * @return integer|null cURL error number, or null if there was no cURL error.
     */
    public function getCurlErrorNumber()
    {
        return $this->curlErrorNumber;
    }
    

    /**
     * Returns the HTTP status code, or null if this was not set.
     *
     * @return integer|null HTTP status code, or null if this was not set.
     */
    public function getHttpStatusCode() {
        return $this->httpStatusCode;
    }
}
