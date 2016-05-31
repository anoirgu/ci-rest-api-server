<?php defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * CodeIgniter Rest API Server
 * @author Sujeet <sujeetkv90@gmail.com>
 * @link https://github.com/sujeet-kumar/ci-rest-api-server
 */

/**
 * Abstract class for Base Api Controller
 */
abstract class Api_Controller_Abstract extends CI_Controller
{	
	protected $_response_schema = array(
		'status'	=> 0, // 0 or 1
		'message'	=> '',
		'redirect'	=> '',
		'payload'	=> array()
	);
	protected $_status_field = 'status';
	protected $_message_field = 'message';
	
	protected $_message_language = 'english';
	
	protected $_response_formats = array(
		'json'		=> 'application/json',
		'xml'		=> 'application/xml'
	);
	protected $_response_format = 'json';
	
	protected $_jsonp = false;
	protected $_jsonp_callback_field = 'callback';
	
	protected $_xml_basenode = 'xml';
	
	protected $_http_methods = array('GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS', 'HEAD');
	protected $_http_method = 'get';
	protected $_emulate_http_method = false;
	protected $_allowed_http_methods = array();
	
	protected $_force_https = false;
	
	protected $_allow_cors = false;
	protected $_allowed_cors_headers = array(
		'Accept',
		'Content-Type',
		'X-Requested-With',
		'Origin',
		'Access-Control-Request-Method'
	);
	protected $_allowed_cors_methods = array('GET', 'OPTIONS', 'HEAD');
	protected $_allow_cors_any_origin = false;
	protected $_allowed_cors_origins = array();
	
	protected $_response_code = 200;
	protected $_response_status = '';
	protected $_response_body = NULL;
	
	protected $_dispatch_resource = true;
	
	public function __construct(){
		parent::__construct();
		
		if(version_compare(CI_VERSION, '3.0.0', '<')){
			throw new Api_Controller_Exception(get_class($this).' requires CodeIgniter 3.x');
		}
		
		$this->output->parse_exec_vars = false;
		$this->output->enable_profiler = false;
		
		$this->load->helper('inflector');
		
		$this->lang->load('api_controller', $this->_message_language);
		
		$this->_http_method = $this->_httpMethod();
	}
	
	
	/************************** Controller Interface **************************/
	
	/**
	 * Setup api configuration
	 * @param array $config
	 */
	protected function _setup($config){
		if(! is_array($config)){
			throw new Api_Controller_Exception('Setup Error: Invalid setup config');
		}else{
			empty($config['response_format']) or $this->_format($config['response_format']);
			
			if(!empty($config['response_schema'])){
				if(! is_array($config['response_schema'])){
					throw new Api_Controller_Exception('Setup Error: Invalid response_schema');
				}else{
					$this->_response_schema = $config['response_schema'];
				}
			}
			
			empty($config['status_field']) or $this->_status_field = $config['status_field'];
			empty($config['message_field']) or $this->_message_field = $config['message_field'];
			empty($config['message_language']) or $this->_message_language = $config['message_language'];
			isset($config['emulate_http_method']) and $this->_emulate_http_method = (bool) $config['emulate_http_method'];
			isset($config['force_https']) and $this->_force_https = (bool) $config['force_https'];
			isset($config['jsonp']) and $this->_jsonp = (bool) $config['jsonp'];
			empty($config['jsonp_callback_field']) or $this->_jsonp_callback_field = $config['jsonp_callback_field'];
			empty($config['xml_basenode']) or $this->_xml_basenode = $config['xml_basenode'];
			
			isset($config['allow_cors']) and $this->_allow_cors = (bool) $config['allow_cors'];
			isset($config['allow_cors_any_origin']) and $this->_allow_cors_any_origin = (bool) $config['allow_cors_any_origin'];
			if(!empty($config['allowed_cors_headers'])){
				if(! is_array($config['allowed_cors_headers'])){
					throw new Api_Controller_Exception('Setup Error: Invalid allowed_cors_headers');
				}else{
					$this->_allowed_cors_headers = $config['allowed_cors_headers'];
				}
			}
			if(!empty($config['allowed_cors_methods'])){
				if(! is_array($config['allowed_cors_methods'])){
					throw new Api_Controller_Exception('Setup Error: Invalid allowed_cors_methods');
				}else{
					$this->_allowed_cors_methods = $config['allowed_cors_methods'];
				}
			}
			if(!empty($config['allowed_cors_origins'])){
				if(! is_array($config['allowed_cors_origins'])){
					throw new Api_Controller_Exception('Setup Error: Invalid allowed_cors_methods');
				}else{
					$this->_allowed_cors_origins = $config['allowed_cors_origins'];
				}
			}
		}
	}
	
	/**
	 * Set api response
	 * @param mixed $data
	 * @param int $status_code
	 * @param string $status_text
	 */
	protected function _response($data, $status_code = Status::HTTP_OK, $status_text = ''){
		$this->_status($status_code, $status_text);
		$this->_response_body = is_array($data) ? array_merge($this->_response_schema, $data) : $data;
	}
	
	/**
	 * Set api response status code
	 * @param int $status_code
	 * @param string $status_text
	 */
	protected function _status($status_code, $status_text = ''){
		empty($status_code) or $this->_response_code = (int) $status_code;
		empty($status_text) or $this->_response_status = strval($status_text);
	}
	
	/**
	 * Set api response format
	 * @param string $response_format
	 */
	protected function _format($response_format){
		if(empty($response_format)){
			throw new Api_Controller_Exception('Invalid response_format');
		}else{
			$format = strtolower($response_format);
			
			isset($this->_response_formats[$format]) 
			or $this->_response_formats[$format] = $response_format;
			
			$this->_response_format = $format;
		}
	}
	
	/**
	 * Set allowed HTTP method for controller resources
	 * @param string $resource
	 * @param array|string $http_method
	 * @param bool $override
	 */
	protected function _httpAllow($resource, $http_method, $override = false){
		isset($this->_allowed_http_methods[$resource]) or $this->_allowed_http_methods[$resource] = array();
		
		is_array($http_method) or $http_method = array($http_method);
		$http_method = array_map('strtoupper', $http_method);
		
		if($unsupported = array_diff($http_method, $this->_http_methods)){
			throw new Api_Controller_Exception('Can not allow un-supported methods: ' . implode(', ', $unsupported));
		}
		
		$this->_allowed_http_methods[$resource] = $override 
			? $http_method 
			: array_unique(array_merge($this->_allowed_http_methods[$resource], $http_method));
	}
	
	/**
	 * Get allowed response formats
	 */
	protected function _allowedFormats(){
		return array_keys($this->_response_formats);
	}
	
	/**
	 * Get supported HTTP methods
	 */
	protected function _supportedHttpMethods(){
		return $this->_http_methods;
	}
	
	/**
	 * Get allowed HTTP methods
	 */
	protected function _allowedHttpMethods(){
		$scope = array();
		$end_resource = $this->router->method;
		
		if(isset($this->_allowed_http_methods['*']) and is_array($this->_allowed_http_methods['*'])){
			$scope = $this->_allowed_http_methods['*'];
		}
		if(isset($this->_allowed_http_methods[$end_resource]) and is_array($this->_allowed_http_methods[$end_resource])){
			$scope = array_unique(array_merge($scope, $this->_allowed_http_methods[$end_resource]));
		}
		return $scope;
	}
	
	/**
	 * Override controller resource dispatch
	 * @param bool $override
	 */
	protected function _overrideDispatch($override = true){
		$this->_dispatch_resource = (! $override);
	}
	
	
	/************************** Internal Methods **************************/
	
	/* Handles controller api request */
	public final function _remap($method, $params = array()){
		method_exists($this, '_beforeDispatch') and call_user_func(array($this, '_beforeDispatch'));
		
		($this->_allow_cors === true) and $this->_implementCORS();
		
		if($this->_force_https === true and !is_https()){
			$this->_response(array(
				$this->_status_field => 0,
				$this->_message_field => $this->lang->line('unsupported_protocol')
			), Status::HTTP_FORBIDDEN);
		}elseif(! method_exists($this, $method)){
			$this->_response(array(
				$this->_status_field => 0,
				$this->_message_field => $this->lang->line('resource_not_found')
			), Status::HTTP_NOT_FOUND);
		}elseif(method_exists($this, '_isAuthorized') and ! call_user_func(array($this, '_isAuthorized'))){
			if($this->_response_body === NULL){
				$this->_response(array(
					$this->_status_field => 0,
					$this->_message_field => $this->lang->line('unauthorized')
				), Status::HTTP_UNAUTHORIZED);
			}
		}elseif(! $this->_checkHttpMethod()){
			($allowed_methods = $this->_allowedHttpMethods()) 
			and $this->output->set_header('Allow: ' . implode(',', $allowed_methods));
			
			$this->_response(array(
				$this->_status_field => 0,
				$this->_message_field => $this->lang->line('http_method_not_allowed')
			), Status::HTTP_METHOD_NOT_ALLOWED);
		}else{
			($this->_dispatch_resource === true) and call_user_func_array(array($this, $method), $params);
		}
		
		$this->_renderResponse();
		
		method_exists($this, '_afterDispatch') and call_user_func(array($this, '_afterDispatch'));
	}
	
	/* Renders api response */
	protected function _renderResponse(){
		$this->output->set_status_header($this->_response_code, $this->_response_status);
		$this->output->set_content_type($this->_response_formats[$this->_response_format]);
		
		if(! is_null($this->_response_body)){
			$converter = '_arrayTo'.strtoupper($this->_response_format);
			if(method_exists($this, $converter)){
				if($this->_response_format == 'xml'){
					$param = $this->_xml_basenode;
				}else{
					$param = $this->_jsonp ? $this->_jsonp_callback_field : NULL;
				}
				$output = $this->$converter($this->_response_body, $param);
			}else{
				$output = $this->_response_body;
			}
			$this->output->set_output($output);
		}
	}
	
	/* Detect HTTP method of request */
	protected function _httpMethod(){
		$method = NULL;
		
		if($this->_emulate_http_method){
			$method = $this->input->post('_method') 
						? $this->input->post('_method') 
						: $this->input->server('HTTP_X_HTTP_METHOD_OVERRIDE');
		}
		
		empty($method) or $method = $this->input->method();
		
        return empty($method) ? 'GET' : strtoupper($method);
    }
	
	/* Validates request against allowed HTTP methods */
	protected function _checkHttpMethod(){
		if(! in_array($this->_http_method, $this->_http_methods)){
			return false;
		}else{
			return in_array($this->_http_method, $this->_allowedHttpMethods());
		}
	}
	
	/* Implements CORS */
	protected function _implementCORS(){
		$allowed_headers = implode(', ', $this->_allowed_cors_headers);
		$allowed_methods = implode(', ', $this->_allowed_cors_methods);
		
		if($this->_allow_cors_any_origin === true){
			
			$this->output->set_header('Access-Control-Allow-Origin: *');
			$this->output->set_header('Access-Control-Allow-Headers: ' . $allowed_headers);
			$this->output->set_header('Access-Control-Allow-Methods: ' . $allowed_methods);
			
		}elseif($origin = $this->input->get_request_header('Origin') and in_array($origin, $this->_allowed_cors_origins)){
			
			$this->output->set_header('Access-Control-Allow-Origin: ' . $origin);
			$this->output->set_header('Access-Control-Allow-Headers: ' . $allowed_headers);
			$this->output->set_header('Access-Control-Allow-Methods: ' . $allowed_methods);
			
		}
	}
	
	/* Converts Array to JSON */
	protected function _arrayToJSON($array, $jsonp_callback_field = NULL){
		is_array($array) or $array = array();
		if(! empty($jsonp_callback_field) and $callback = $this->input->post_get($jsonp_callback_field)){
			if(! preg_match('/^[a-z_\$][a-z0-9\$_]*(\.[a-z_\$][a-z0-9\$_]*)*$/i', $callback)){
				$array['warning'] = sprintf($this->lang->line('invalid_jsonp_callback'), $callback);
			}else{
				return $callback.'('.json_encode($array).');';
			}
		}
		return json_encode($array);
	}
	
	/* Converts Array to XML */
	protected function _arrayToXML($array, $basenode = 'xml', $struct = NULL){
		($struct !== NULL) or $struct = simplexml_load_string("<?xml version='1.0' encoding='utf-8'?><$basenode />");
		
		if(is_array($array)){
			foreach($array as $key => $value){
				!is_numeric($key) or $key = (singular($basenode) != $basenode) ? singular($basenode) : 'item';
				
				$key = preg_replace('/[^a-z_\-0-9]/i', '', $key);
				
				!is_bool($value) or $value = (int) $value;
				
				if(is_array($value)){
					$node_struct = $struct->addChild($key);
					$this->_arrayToXML($value, $key, $node_struct);
				}else{
					$value = htmlspecialchars(html_entity_decode($value, ENT_QUOTES, 'UTF-8'), ENT_QUOTES, 'UTF-8');
					$struct->addChild($key, $value);
				}
			}
		}
		
		return $struct->asXML();
    }
}

/**
 * Exception class for api controller
 */
class Api_Controller_Exception extends Exception
{
	
}


/**
 * HTTP status collection
 * commonly used http response codes
 */
final class Status
{
	/* Informational */
	const HTTP_CONTINUE = 100;
	const HTTP_SWITCHING_PROTOCOLS = 101;
	const HTTP_PROCESSING = 102;                               // RFC2518
	
	/* Success */
	const HTTP_OK = 200;
	const HTTP_CREATED = 201;
	const HTTP_ACCEPTED = 202;
	const HTTP_NON_AUTHORITATIVE_INFORMATION = 203;
	const HTTP_NO_CONTENT = 204;
	const HTTP_RESET_CONTENT = 205;
	const HTTP_PARTIAL_CONTENT = 206;
	const HTTP_MULTI_STATUS = 207;                             // RFC4918
	const HTTP_ALREADY_REPORTED = 208;                         // RFC5842
	const HTTP_IM_USED = 226;                                  // RFC3229
	
	/* Redirection */
	const HTTP_MULTIPLE_CHOICES = 300;
	const HTTP_MOVED_PERMANENTLY = 301;
	const HTTP_FOUND = 302;
	const HTTP_SEE_OTHER = 303;
	const HTTP_NOT_MODIFIED = 304;
	const HTTP_USE_PROXY = 305;
	const HTTP_TEMPORARY_REDIRECT = 307;
	const HTTP_PERMANENTLY_REDIRECT = 308;                     // RFC7238
	
	/* Client Error */
	const HTTP_BAD_REQUEST = 400;
	const HTTP_UNAUTHORIZED = 401;
	const HTTP_PAYMENT_REQUIRED = 402;
	const HTTP_FORBIDDEN = 403;
	const HTTP_NOT_FOUND = 404;
	const HTTP_METHOD_NOT_ALLOWED = 405;
	const HTTP_NOT_ACCEPTABLE = 406;
	const HTTP_PROXY_AUTHENTICATION_REQUIRED = 407;
	const HTTP_REQUEST_TIMEOUT = 408;
	const HTTP_CONFLICT = 409;
	const HTTP_GONE = 410;
	const HTTP_LENGTH_REQUIRED = 411;
	const HTTP_PRECONDITION_FAILED = 412;
	const HTTP_REQUEST_ENTITY_TOO_LARGE = 413;
	const HTTP_REQUEST_URI_TOO_LONG = 414;
	const HTTP_UNSUPPORTED_MEDIA_TYPE = 415;
	const HTTP_REQUESTED_RANGE_NOT_SATISFIABLE = 416;
	const HTTP_EXPECTATION_FAILED = 417;
	const HTTP_I_AM_A_TEAPOT = 418;                            // RFC2324
	const HTTP_UNPROCESSABLE_ENTITY = 422;                     // RFC4918
	const HTTP_LOCKED = 423;                                   // RFC4918
	const HTTP_FAILED_DEPENDENCY = 424;                        // RFC4918
	const HTTP_UPGRADE_REQUIRED = 426;                         // RFC2817
	const HTTP_PRECONDITION_REQUIRED = 428;                    // RFC6585
	const HTTP_TOO_MANY_REQUESTS = 429;                        // RFC6585
	const HTTP_REQUEST_HEADER_FIELDS_TOO_LARGE = 431;          // RFC6585
	
	/* Server Error */
	const HTTP_INTERNAL_SERVER_ERROR = 500;
	const HTTP_NOT_IMPLEMENTED = 501;
	const HTTP_BAD_GATEWAY = 502;
	const HTTP_SERVICE_UNAVAILABLE = 503;
	const HTTP_GATEWAY_TIMEOUT = 504;
	const HTTP_VERSION_NOT_SUPPORTED = 505;
	const HTTP_INSUFFICIENT_STORAGE = 507;                     // RFC4918
	const HTTP_LOOP_DETECTED = 508;                            // RFC5842
	const HTTP_NOT_EXTENDED = 510;                             // RFC2774
	const HTTP_NETWORK_AUTHENTICATION_REQUIRED = 511;
	
	/* prevent instantiation */
	private function __construct(){
		throw new Exception("Can't get an instance of Status class");
	}
}

/* End of file Api_Controller_Abstract.php */