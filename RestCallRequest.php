<?php

class RestCallRequest {

	protected $url;
	protected $requestBody;
	protected $responseBody;
	protected $responseInfo;
	
	public function getResponseBody () {
		return $this->responseBody;
	} 
	public function getResponseInfo () {
		return $this->responseInfo;
	} 
	
	public function __construct ($url, $verb, $requestBody)	{
		$this->url	     = $url;
		$this->requestBody   = http_build_query($requestBody, '', '&');
		$this->responseBody  = null;
		$this->responseInfo  = null;
	}
	
	public function execute () {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
#		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, TRUE);

		// 20 minutes till it times out
		curl_setopt($ch, CURLOPT_TIMEOUT, 1200); 
		curl_setopt($ch, CURLOPT_URL, $this->url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, 
			    array('Accept: text/xml'));

		// Because we are doing a POST
		curl_setopt($ch, CURLOPT_POSTFIELDS, $this->requestBody);
		curl_setopt($ch, CURLOPT_POST, 1);
	
		$this->responseBody = curl_exec($ch);
		$this->responseInfo = curl_getinfo($ch);
		
		curl_close($ch);
			
	}
	
}  # end RestCallRequest
