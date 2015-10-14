<?php
/**
 * Convio Open API Library
 *
 * Simple wrapper ease in the process of configuring and executing calls to the Convio API. This wrapper assumes
 * that necessary administrative set up procedures have been successfully followed by the site administrator(s)
 * and that the site is ready and able to receive API requests. For more information, please consult the
 * documenation at: http://open.convio.com.
 *
 * @author Nick Bartkowiak <nbartkowiak@convio.com>
 * @copyright Copyright (c) 2009, Convio Inc.
 * @version 0.1
 *
 * @package OpenAPI
 *
 * Copyright 2010 Convio, Inc.
 */

class ConvioOpenAPI
{
	/**
	 * @var string The host name of the secure Convio cluster that the site resides on. In most cases this should
	 * be set to "secure2.convio.net" or "secure3.convio.net."
	 * @access public
	 */
	public $host;

	/**
	 * @var string The Convio organization short name.
	 * @access public
	 */
	public $short_name;

	/**
	 * @var string This is the API Key that was chosen by the client and set up using the Convio Administrator's
	 * panel.
	 * @access public
	 */
	public $api_key;
	
	/**
	 * @var string The version of the API that is to be used. For now this should always be set to "1.0."
	 * @access public
	 */
	public $v = '1.0';

	/**
	 * @var string The desired format in which the server response should be returned in. By default, responses
	 * will be formatted as PHP objects, but either "xml" or "json" is acceptable.
	 * @access public
	 */
	public $response_format = 'php';

	/**
	 * @var string Username of the Convio user that is authorized to make API calls to the Convio server. This
	 * administrator is set up by the client during the API configuration process and is used to tell the Convio
	 * server that the PHP application making the call is authorized to do so.
	 * @access public
	 */
	public $login_name = NULL;
	
	/**
	 * @var string Username of the Convio user that is authorized to make API calls to the Convio server. This
	 * administrator is set up by the client during the API configuration process and is used to tell the Convio
	 * server that the PHP application making the call is authorized to do so.
	 * @access public
	 */
	public $login_password = NULL;
	
	/**
	 * @var string Name of the API servlet that is to be called.
	 * @access private
	 */
	private $__servlet;
	
	/**
	 * @var string Name of the method that is to be called. It must be a method that is associated with the
	 * servlet that is passed.
	 * @access private
	 */
	private $__method;
	
	/**
	 * @var array An array of method specific parameters to be sent through with the API call.
	 * @access private
	 */
	private $__methodParams = array();
	
	/**
	 * Compiles all the configuration parameters and method specific parameters together into one urlencoded
	 * parameter string ready to be sent through to the Convio server via an HTTP POST.
	 *
	 * @access private
	 * 
	 * @uses ConvioOpenAPI::response_format
	 * @uses ConvioOpenAPI::v
	 * @uses ConvioOpenAPI::api_key
	 * @uses ConvioOpenAPI::login_name
	 * @uses ConvioOpenAPI::login_password
	 * @uses ConvioOpenAPI::method
	 * @uses ConvioOpenAPI::__methodParams
	 *
	 * @return A urlencoded parameter string that is ready for posting to the Convio API.
	 */
	private function __getPostData()
	{
		$response_format = $this->response_format;
		if ($this->response_format == 'php') $response_format = 'json';
		$baseData   = http_build_query(array('v'=>$this->v,'api_key'=>$this->api_key,'response_format'=>$response_format,'login_name'=>$this->login_name,'login_password'=>$this->login_password,'method'=>$this->__method));
		$methodData = http_build_query($this->__methodParams);
		return sprintf('%s&%s', $baseData, $methodData);
	}
	
	/**
	 * Combines the given parameters into a valid API Servlet URL, which will be used to process the POSTed
	 * parameters.
	 * 
	 * @access private
	 * 
	 * @uses ConvioOpenAPI::host
	 * @uses ConvioOpenAPI::short_name
	 * @uses ConvioOpenAPI::__servlet
	 *
	 * @return Valid API Servlet URL ready to receive POSTed parameters.
	 */
	private function __getUrl()
	{
		return sprintf('https://%s/%s/site/%s', $this->host, $this->short_name, $this->__servlet);
	}
	
	/**
	 * This method is the heavy-lifting section of the library. After the URL has been correctly created and the
	 * parameters are encoded properly, this method actually makes the call to the API. It first checks to see
	 * if it has access to cURL, if so it uses that, if not it makes a simply fopen call. cURL is prefferable,
	 * because we can get more information on the call, which is particularly helpful in the case of a Convio 403
	 * error being thrown.
	 *
	 * @access private
	 *
	 * @uses ConvioOpenAPI::response_format
	 * @uses ConvioOpenAPI::__getUrl()
	 * @uses ConvioOpenAPI::__getPostdata()
	 * 
	 * @return Depending on the response format, this method will return the API response as either a PHP object,
	 * a string of XML, or a string of JSON.
	 */
	private function __makeCall()
	{
		$url  = $this->__getUrl();
		$post = $this->__getPostData();
		
		// Here is where we check for cURL. If we don't find it we make a fopen call...
		if (function_exists('curl_exec') === FALSE)
		{
			$context = stream_context_create(array('http'=>array('method'=>'POST','content'=>$post)));
			$fp = @fopen($url, 'rb', FALSE, $context);
			$response = @stream_get_contents($fp);
			@fclose($fp);
			
			if ($response == '') $response = sprintf("The server returned no useable data. This likely points to a NULL result. Try installing php-curl for better error handling.\n");
		}
		// ...If we do find it, we can make the call using cURL.
		else
		{
			$curl = curl_init();
			curl_setopt($curl, CURLOPT_URL, $url);
			curl_setopt($curl, CURLOPT_POST, TRUE);
			curl_setopt($curl, CURLOPT_POSTFIELDS, $post);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
			$response = curl_exec($curl);
			
			if ($response == '') $response = sprintf("cURL Error %s: %s\n", curl_errno($curl), curl_error($curl));
			
			curl_close($curl);
		}
		
		if ($this->response_format == 'php') $response = json_decode($response);
		
		return $response;
	}
	
	/**
	 * Public facing interface for this library. This is the method that actually takes the parameters from whatever
	 * controller is asking for the information and passes them on to the API. Whatever the response from the API
	 * is, it is passed on to the controller.
	 *
	 * @access public
	 *
	 * @param string $servletMethod A string combining the API servlet to be called and the method of that servlet
	 * that should be called. The string should be in the format "ApiServlet_apiMethod." Example: SRConsAPI_getUser.
	 *
	 * @param array $params An array of API method specific parameters that are to be sent through to the api. The
	 * indices of this array should correspond exactly to API parameters listed in the Convio Open API documentation
	 * found at http://open.convio.com/api/apidoc/.
	 *
	 * @uses ConvioOpenAPI::__servlet
	 * @uses ConvioOpenAPI::__method
	 * @uses ConvioOpenAPI::__methodParams
	 * @uses ConvioOpenAPI::__makeCall()
	 *
	 * @return This will return whatever the __makeCall() method returns. Depending on the response format, that
	 * will be either a PHP object, a string of XML, or a string of JSON that is representative of the response
	 * from the API.
	 */
	public function call($servletMethod, $params = NULL)
	{
		$this->__servlet = array_shift(explode('_', $servletMethod));
		$this->__method  = array_pop(explode('_', $servletMethod));
		if ($params !== NULL) $this->__methodParams = $params;
		return $this->__makeCall();
	}
	
}
