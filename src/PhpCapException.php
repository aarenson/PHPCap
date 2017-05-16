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
 * <code>
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
 * </code>
 *
 * @see http://php.net/manual/en/class.exception.php
 *         Information on additional methods provided by parent class Exception.
 */
class PhpCapException extends \Exception
{
    // Error codes
    
    /** Invalid argument passed to a PHPCap method. */
    const INVALID_ARGUMENT = 1;
    
    /** Too many arguments were passed to the method. */
    const TOO_MANY_ARGUMENTS = 2;

    /** An invalid URL was used. */
    const INVALID_URL = 3;
    
    /** A CA certificate file was specified, but it could not be found. */
    const CA_CERTIFICATE_FILE_NOT_FOUND = 4;
    
    /** The CA certificate file could not be read. */
    const CA_CERTIFICATE_FILE_UNREADABLE = 5;

    /** A cURL error occurred. */
    const CURL_ERROR = 6;
    
    /** The REDCap API generated an error. */
    const REDCAP_API_ERROR = 7;
    
    /** A JSON error occurred. This would typically happen when PHPCap is expecting
     * the REDCap API to return data in JSON format, but the result returned is not valid JSON.
     */
    const JSON_ERROR = 8;
    
    /** The output file could not be found, or was found and could not be written */
    const OUTPUT_FILE_ERROR     = 9;

    /** The input file could not be found. */
    const INPUT_FILE_NOT_FOUND  = 10;
    
    /** The input file was found, but is unreadable. */
    const INPUT_FILE_UNREADABLE = 11;
    
    /** The input file contents are invalid. */
    const INPUT_FILE_ERROR      = 12;
    

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
     * @param \Exception $previous the previous exception.
     */
    public function __construct($message, $code, $curlErrorNumber = null, $httpStatusCode = null, $previous = null)
    {
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
    public function getHttpStatusCode()
    {
        return $this->httpStatusCode;
    }
}
