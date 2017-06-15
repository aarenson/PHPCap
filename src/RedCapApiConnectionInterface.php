<?php

/**
 * Contains interface for classes implementing a connection to a REDCap API.
 */

namespace IU\PHPCap;

/**
 * Interface for connection to the API of a REDCap instance.
 * Classes implementing this interface are used to provide low-level
 * access to the REDCap API.
 */
interface RedCapApiConnectionInterface
{

    public function __construct(
        $url,
        $sslVerify,
        $caCertificateFile,
        $timeoutInSeconds,
        $connectionTimeoutInSeconds,
        $errorHandler
    );

    public function __destruct();

    public function call($data);
    public function callWithArray($dataArray);
    public function getCallInfo();
    
    /**
     * Gets the error handler for the connection.
     *
     * return ErrorHandlerInterface the error handler for the connection.
     */
    public function getErrorHandler();
    
    public function setErrorHandler($errorHandler);
        
    public function getUrl();
    
    
    public function getSslVerify();
    
    /**
     * Sets SSL verification for the connection.
     *
     * @param boolean $sslVerify if this is true, then the site being connected to will
     *     have its SSL certificate verified.
     */
    public function setSslVerify($sslVerify);
    
    public function getTimeoutInSeconds();
    public function setTimeoutInSeconds($timeoutInSeconds);
    public function getConnectionTimeoutInSeconds();
    public function setConnectionTimeoutInSeconds($connectionTimeoutInSeconds);
}
