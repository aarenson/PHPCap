<?php

/**
 * Contains class for creating and using a connection to a REDCap API. 
 */


namespace IU\PHPCap;

require_once(__DIR__."/RedCapApiException.php");

/**
 * A connection to the API of a REDCap instance.
 */
class RedCapApiConnection
{
    const DEFAULT_TIMEOUT_IN_SECONDS = 1200; // 1,200 seconds = 20 minutes
    
    /** @var string the URL of the REDCap site being accessed. */
    private $url;
    
    
    private $sslVerify;
    private $caCertificateFile;
    private $timeOutInSeconds;
    
    /** @var resource cURL handle. */
    private $curlHandle;

    /**
     * Constructor that creates a REDCap API connection for the specified URL, with the
     * specified settings.
     *
     * @param string $url
     *            the URL for the API of the REDCap site that you want to connect to.
     * @param boolean $sslVerify
     * @param string $caCertificateFile
     *            the CA (Certificate Authority) certificate file used for veriying the REDCap site's
     *            SSL certificate (i.e., for verifying that the REDCap site that is
     *            connected to is the one specified).
     * @param integer $timeOutInSeconds
     *
     * @throws RedCapApiException
     */
    public function __construct(
        $url,
        $sslVerify = false,
        $caCertificateFile = '',
        $timeOutInSeconds = self::DEFAULT_TIMEOUT_IN_SECONDS
    ) {
        $this->url = $url;
        $this->sslVerify = $sslVerify;
        $this->caCertificateFile = $caCertificateFile;
        $this->timeOutInSeconds = $timeOutInSeconds;
        
        $this->curlHandle = curl_init();
        
        curl_setopt($this->curlHandle, CURLOPT_SSL_VERIFYPEER, $this->sslVerify);
        
        if ($this->sslVerify && $this->caCertificateFile != null && trim($this->caCertificateFile) != '') {
            curl_setopt($this->curlHandle, CURLOPT_SSL_VERIFYHOST, 2);
            if (! file_exists($this->caCertificateFile)) {
                throw new RedCapApiException('The cert file "' . $this->caCertificateFile
                        . '" does not exist.', RedCapApiException::CA_CERTIFICATE_FILE_NOT_FOUND);
                // Try just letting curl catch this??? - or, check URL too (missing, wrong type)?
            } elseif (! is_readable($this->caCertificateFile)) {
                throw new RedCapApiException('The cert file "' . $this->caCertificateFile
                        . '" exists, but cannot be read.');
            }
            curl_setopt($this->curlHandle, CURLOPT_CAINFO, $this->caCertificateFile);
        }
        
        curl_setopt($this->curlHandle, CURLOPT_TIMEOUT, $this->timeOutInSeconds);
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
     * @param array $callInfo
     *         optional output parameter that, if an argument for it is provided,
     *         is set to information about the call that was made.
     *         See http://php.net/manual/en/function.curl-getinfo.php for more information.
     * @throws RedCapApiException
     * @return string the response returned by the REDCap API for the specified call data.
     *         See the REDCap API documentation for more information.
     */
    public function call($data, & $callInfo = null)
    {
        // ???????? Add timeout parameter, so timeout can be set for specific call (for example, might want
        // to set the timeout for the initial retrieval of project info and metadata to be low, so that
        // if there is a problem, it doesn't take 20 minutes to time out!!!!!!!!!
        // or just add setter to connection so it can be changed????
        $errno = 0;
        $response = '';
        
        // Post speficied data
        curl_setopt($this->curlHandle, CURLOPT_POSTFIELDS, $data);
        $response = curl_exec($this->curlHandle);
        if ($errno = curl_errno($this->curlHandle)) {
            throw new RedCapApiException(curl_error($this->curlHandle), $errno);
        } else {
            // Check for HTTP errors
            $httpCode = curl_getinfo($this->curlHandle, CURLINFO_HTTP_CODE);
            if ($httpCode == 301) {
                $callInfo = curl_getinfo($this->curlHandle);
                throw new RedCapApiException("The page for the specified URL (" . $this->url
                        . ") has moved to " . $callInfo ['redirect_url'] . ". Please update your URL.");
            } elseif ($httpCode == 404) {
                throw new RedCapApiException('
                        The specified URL (' . $this->url . ') appears to be incorrect.'
                        . ' Nothing was found at this URL.', RedCapApiException::URL_NOT_FOUND);
            }
        }
        
        if ($callInfo != null) {
            $callInfo = curl_getinfo($this->curlHandle);
            if ($errno = curl_errno($this->curlHandle)) {
                throw new RedCapApiException(curl_error($this->curlHandle), $errno);
            }
        }
        
        return ($response);
    }
}
