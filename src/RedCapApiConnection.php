<?php

/**
 * Contains class for creating and using a connection to a REDCap API.
 */

namespace IU\PHPCap;

/**
 * A connection to the API of a REDCap instance. This class provides a low-level
 * interface to the REDCap API, and is primarily intended for internal use by PHPCap,
 * but could be used directly by a user to access REDCap functionality not provided
 * by PHPCap.
 */
class RedCapApiConnection
{
    const DEFAULT_TIMEOUT_IN_SECONDS = 1200; // 1,200 seconds = 20 minutes
    
    /** @var string the URL of the REDCap site being accessed. */
    private $url;
    
    /** @var boolean true if the SSL connection should be verified, and false if it should not. */
    private $sslVerify;
    
    /** @var the CA (Certificate Authority) file to use for SSL verification. */
    private $caCertificateFile;
    
    /** @var the timeout in seconds for calls that are made to the REDCap API. */
    private $timeoutInSeconds;
    
    /** @var resource cURL handle. */
    private $curlHandle;

    /**
     * Constructor that creates a REDCap API connection for the specified URL, with the
     * specified settings.
     *
     * @param string $url
     *            the URL for the API of the REDCap site that you want to connect to.
     * @param boolean $sslVerify indicates if verification should be done for the SSL
     *            connection to REDCap. Setting this to false is not secure.
     * @param string $caCertificateFile
     *            the CA (Certificate Authority) certificate file used for veriying the REDCap site's
     *            SSL certificate (i.e., for verifying that the REDCap site that is
     *            connected to is the one specified).
     * @param integer $timeoutInSeconds the timeout in seconds for the connection.
     *
     * @throws PhpCapException
     */
    public function __construct(
        $url,
        $sslVerify = false,
        $caCertificateFile = '',
        $timeoutInSeconds = self::DEFAULT_TIMEOUT_IN_SECONDS
    ) {
        $this->url = $url;
        $this->sslVerify = $sslVerify;
        $this->caCertificateFile = $caCertificateFile;
        $this->timeoutInSeconds = $timeoutInSeconds;
        // Have fixed and variable data/time call timeout
        // - fixed would be used for metadata, project info, etc.
        // - variable data would be used export data
        // exception this class has no knowledge of calling method??? So would need to set
        // this in the class above this.
        // need to be able to reset
        
        $this->curlHandle = curl_init();
        
        curl_setopt($this->curlHandle, CURLOPT_SSL_VERIFYPEER, $this->sslVerify);
        
        if ($this->sslVerify && $this->caCertificateFile != null && trim($this->caCertificateFile) != '') {
            curl_setopt($this->curlHandle, CURLOPT_SSL_VERIFYHOST, 2);
            if (! file_exists($this->caCertificateFile)) {
                throw new PhpCapException('The cert file "' . $this->caCertificateFile
                        . '" does not exist.', PhpCapException::CA_CERTIFICATE_FILE_NOT_FOUND);
                // Try just letting curl catch this??? - or, check URL too (missing, wrong type)?
            } elseif (! is_readable($this->caCertificateFile)) {
                throw new PhpCapException('The cert file "' . $this->caCertificateFile
                        . '" exists, but cannot be read.', PhpCapException::CA_CERTIFICATE_FILE_UNREADABLE);
            }
            curl_setopt($this->curlHandle, CURLOPT_CAINFO, $this->caCertificateFile);
        }
        
        curl_setopt($this->curlHandle, CURLOPT_TIMEOUT, $this->timeoutInSeconds);
        curl_setopt($this->curlHandle, CURLOPT_URL, $this->url);
        curl_setopt($this->curlHandle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->curlHandle, CURLOPT_HTTPHEADER, array ('Accept: text/xml'));
        curl_setopt($this->curlHandle, CURLOPT_POST, 1);
    }

    /**
     * Destructor that closes the cURL handle (if it is set).
     */
    public function __destruct()
    {
        if (isset($this->curlHandle)) {
            curl_close($this->curlHandle);
            $this->curlHandle = null;
        }
    }

    /**
     * Makes a call to REDCap's API and returns the results.
     *
     * @param mixed $data
     *         data for the call.
     * @throws PhpCapException
     * @return string the response returned by the REDCap API for the specified call data.
     *         See the REDCap API documentation for more information.
     */
    public function call($data)
    {
        if (!is_string($data)) {
            throw new PhpCapException(
                "Data passed to ".__METHOD__
                ." has type ".gettype($data). ", but should be a string.",
                PhpCapException::INVALID_ARGUMENT
            );
        }
        
        $errno = 0;
        $response = '';
        
        // Post speficied data
        curl_setopt($this->curlHandle, CURLOPT_POSTFIELDS, $data);
        $response = curl_exec($this->curlHandle);
        if ($errno = curl_errno($this->curlHandle)) {
            throw new PhpCapException(curl_error($this->curlHandle), PhpCapException::CURL_ERROR, $errno);
        } else {
            // Check for HTTP errors
            $httpCode = curl_getinfo($this->curlHandle, CURLINFO_HTTP_CODE);
            if ($httpCode == 301) {
                $callInfo = curl_getinfo($this->curlHandle);
                throw new PhpCapException(
                    "The page for the specified URL (" . $this->url. ") has moved to ".
                        $callInfo ['redirect_url'] . ". Please update your URL.",
                    PhpCapException::INVALID_URL,
                    null,
                    $httpCode
                );
            } elseif ($httpCode == 404) {
                throw new PhpCapException('
                        The specified URL (' . $this->url . ') appears to be incorrect.'
                        . ' Nothing was found at this URL.', PhpCapException::INVALID_UR, null, $httpCode);
            }
        }
        
        return ($response);
    }

    
    /**
     * Returns call information for the most recent call.
     *
     * @throws PhpCapException
     * @return array an associative array of values of call information for the most recent call made.
     *
     * @see <a href="http://php.net/manual/en/function.curl-getinfo.php">http://php.net/manual/en/function.curl-getinfo.php</a>
     *      for information on what values are returned.
     */
    public function getCallInfo()
    {
        $callInfo = curl_getinfo($this->curlHandle);
        if ($errno = curl_errno($this->curlHandle)) {
            throw new PhpCapException(curl_error($this->curlHandle), PhpCapException::CURL_ERROR, $errno);
        }
        
        return $callInfo;
    }


    /**
     * Gets the timeout in seconds for cURL calls.
     *
     * @return integer timeout in seconds for cURL calls.
     */
    public function getTimeoutInSeconds()
    {
        return $this->timeoutInSeconds;
    }
    
    /**
     * Sets the timeout for cURL calls to the specified amount of seconds.
     *
     * @param integer $timeoutInSeconds timeout in seconds for cURL calls.
     */
    public function setTimeoutInSeconds($timeoutInSeconds)
    {
        $this->timeoutInSeconds = $timeoutInSeconds;
    }
}
