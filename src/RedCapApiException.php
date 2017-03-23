<?php

/**
 * Contains class for REDCap API exceptions.
 */

namespace IU\PHPCap;

/**
 * Class for REDCap API exceptions. 
 *
 */
class RedCapApiException extends \Exception
{
    // Error codes
    const CURL_ERROR = 1;
    const CA_CERTIFICATE_FILE_NOT_FOUND = 2;
    const CA_CERTIFICATE_FILE_UNREADABLE = 3;
    const URL_NOT_FOUND = 4;
    private $curlErrorCode;
    // Can have non-curl errors also
    public function getCurlErrorCode()
    {
        return $this->curlErrorCode;
    }
}
