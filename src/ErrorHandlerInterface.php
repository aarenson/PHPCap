<?php

namespace IU\PHPCap;

/**
 * Interface for error handlers for PHPCap.
 */
interface ErrorHandlerInterface
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

    /** A connection error occurred. */
    const CONNECTION_ERROR = 6;
    
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
    
    public function throwException($message, $code, $connectionErrorNumber, $httpStatusCode, $previousException);
}
