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
    const DEFAULT_CONNECTION_TIMEOUT_IN_SECONDS = 20;
    
    /** resource cURL handle. */
    private $curlHandle;
    
    /** cURL options */
    private $curlOptions;

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
        $this->curlOptions = array();
        
        $this->curlHandle = curl_init();
        
        $this->setCurlOption(CURLOPT_SSL_VERIFYPEER, $sslVerify);
        $this->setCurlOption(CURLOPT_SSL_VERIFYHOST, 2);
        
        if ($sslVerify && $caCertificateFile != null && trim($caCertificateFile) != '') {
            if (! file_exists($caCertificateFile)) {
                throw new PhpCapException('The cert file "' . $caCertificateFile
                        . '" does not exist.', PhpCapException::CA_CERTIFICATE_FILE_NOT_FOUND);
            } elseif (! is_readable($caCertificateFile)) {
                throw new PhpCapException('The cert file "' . $caCertificateFile
                        . '" exists, but cannot be read.', PhpCapException::CA_CERTIFICATE_FILE_UNREADABLE);
            }

            $this->setCurlOption(CURLOPT_CAINFO, $caCertificateFile);
        }
        
        $this->setCurlOption(CURLOPT_TIMEOUT, $timeoutInSeconds);
        $this->setCurlOption(CURLOPT_CONNECTTIMEOUT, self::DEFAULT_CONNECTION_TIMEOUT_IN_SECONDS);
        $this->setCurlOption(CURLOPT_URL, $url);
        $this->setCurlOption(CURLOPT_RETURNTRANSFER, true);
        $this->setCurlOption(CURLOPT_HTTPHEADER, array ('Accept: text/xml'));
        $this->setCurlOption(CURLOPT_POST, 1);
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
        if (!is_string($data) && !is_array($data)) {
            throw new PhpCapException(
                "Data passed to ".__METHOD__
                ." has type ".gettype($data). ", but should be a string or an array.",
                PhpCapException::INVALID_ARGUMENT
            );
        }
        
        $errno = 0;
        $response = '';
        
        // Post specified data (and do NOT save this in the options array)
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
                    "The page for the specified URL (" . $this->getCurlOption(CURLOPT_URL). ") has moved to ".
                        $callInfo ['redirect_url'] . ". Please update your URL.",
                    PhpCapException::INVALID_URL,
                    null,
                    $httpCode
                );
            } elseif ($httpCode == 404) {
                throw new PhpCapException('
                        The specified URL (' . $this->getCurlOption(CURLOPT_URL). ') appears to be incorrect.'
                        . ' Nothing was found at this URL.', PhpCapException::INVALID_URL, null, $httpCode);
            }
        }
        
        return ($response);
    }
    
    /**
     * Calls REDCap's API using a with a correctly formatted string version
     * of the specified array and returns the results.
     *
     * @param $dataArray array the array of data that is converted to a
     *         string and then passed to the REDCap API.
     * @throws PhpCapException
     * @return string the response returned by the REDCap API for the specified call data.
     *         See the REDCap API documentation for more information.
     */
    public function callWithArray($dataArray)
    {
        $data = http_build_query($dataArray, '', '&');
        return $this->call($data);
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
        return $this->getCurlOption(CURLOPT_TIMEOUT);
    }
    
    /**
     * Sets the timeout for cURL calls to the specified amount of seconds.
     *
     * @param integer $timeoutInSeconds timeout in seconds for cURL calls.
     */
    public function setTimeoutInSeconds($timeoutInSeconds)
    {
        $this->setCurlOption(CURLOPT_TIMEOUT, $timeoutInSeconds);
    }
    
    /**
     * Sets the specified cURL option to the specified value.
     *
     * @see <a href="http://php.net/manual/en/function.curl-setopt.php">http://php.net/manual/en/function.curl-setopt.php</a>
     *      for information on cURL options.
     *
     * @param integer $option the cURL option that is being set.
     * @param mixed $value the value that the cURL option is being set to.
     * @return boolean Returns true on success and false on failure.
     */
    public function setCurlOption($option, $value)
    {
        $this->curlOptions[$option] = $value;
        $result = curl_setopt($this->curlHandle, $option, $value);
        return $result;
    }

    /**
     * Gets the value for the specified cURL option number.
     *
     * @see <a href="http://php.net/manual/en/function.curl-setopt.php">http://php.net/manual/en/function.curl-setopt.php</a>
     * for information on cURL options.
     *
     * @param integer $option cURL option number.
     * @return mixed if the specified option has a value that has been set in the code,
     *     then the value is returned. If no value was set, then null is returned.
     *     Note that the cURL CURLOPT_POSTFIELDS option value is not saved,
     *     because it is reset with every call and can can be very large.
     *     As a result, null will always be returned for this cURL option.
     */
    public function getCurlOption($option)
    {
        $optionValue = null;
        if (array_key_exists($option, $this->curlOptions)) {
            $optionValue = $this->curlOptions[$option];
        }
        return $optionValue;
    }
}
