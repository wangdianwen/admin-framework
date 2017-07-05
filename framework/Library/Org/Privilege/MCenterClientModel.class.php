<?php
/*
 * Copyright (C)2011 1verge.com (http://www.youku.com)
 * Description: mcenter client
 * Author: dongjingtao <dongjingtao@youku.com>
 */

// Example:
/*
 $app_key = '202593251504e4d655a5';
 $app_secret = '476885D846956A4AC376BDD6CB7DE76204E4D655A5';
 $ticket = '15F1A04D915655C428D8C3B54153DB9304E50648D2';
 $mc = new MCenterClient($app_key, $app_secret, $ticket);
 $ret = $mc->app_multi_get('1,2,3');
 var_dump($ret);
 $params = array('login_name'=>'test', 'password'=>'D0B4571BBC511B156E232970061B06E6', 'gender'=>1,
 				'email'=>'test@youku.com', 'mobile'=>'13900001111');
 $ret = $mc->request('operator/create', $params, 'POST');
 var_dump($ret);
*/
namespace Org\Privilege;
use Think\Model;
class MCenterAuthUtil{
	public static function urlencode_rfc3986($input) {
		if (is_array($input)) {
			return array_map(array(__NAMESPACE__ .'\MCenterAuthUtil', 'urlencode_rfc3986'), $input);
		}
		if (is_scalar($input)) {
			return str_replace('+', ' ',
				str_replace('%7E', '~', rawurlencode($input))
			);
		}
		return '';
	}

	public static function urldecode_rfc3986($string) {
		return urldecode($string);
	}

	public static function hash_hmac_rfc2104($algo, $data, $key, $raw_output = false) {
		if (function_exists('hash_hmac')) {
			return hash_hmac($algo, $data, $key, $raw_output);
		}
		$blocksize = 64;
		if (strlen($key) > $blocksize) {
			$key = pack('H*', call_user_func($algo, $key));
		}
		$key = str_pad($key, $blocksize, chr(0x00));
		$ipad = str_repeat(chr(0x36), $blocksize);
		$opad = str_repeat(chr(0x5c), $blocksize);
		$k_ipad = $key ^ $ipad ;
		$k_opad = $key ^ $opad;
		$output = call_user_func($algo, $k_opad . pack("H*", call_user_func($algo, $k_ipad . $data)));
		if($raw_output) {
			$output = pack('H*', $output);
		}
		return $output;
	}

	public static function is_private_ip($ip) {
		return (
			($ip & 0xFF000000) == 0x00000000 || # 0.0.0.0/8
			($ip & 0xFF000000) == 0x0A000000 || # 10.0.0.0/8
			($ip & 0xFF000000) == 0x7F000000 || # 127.0.0.0/8
			($ip & 0xFFF00000) == 0xAC100000 || # 172.16.0.0/12
			($ip & 0xFFFF0000) == 0xA9FE0000 || # 169.254.0.0/16
			($ip & 0xFFFF0000) == 0xC0A80000);  # 192.168.0.0/16
	}

	public static function get_client_ip($long = false, $not_private = true) {
		$client_ip = '';
		$ips = getenv('HTTP_CLIENT_IP').','.getenv('HTTP_X_FORWARDED_FOR').','.getenv('REMOTE_ADDR');
		if (preg_match("/\d+\.\d+\.\d+\.\d+/", $ips, $matchs)) {
			$client_ip = $matchs[0];
		}
		if (empty($client_ip) && !empty($_SERVER['REMOTE_ADDR'])) {
			$client_ip = $_SERVER['REMOTE_ADDR'];
		}
		if (empty($client_ip)) {
			$client_ip = 'unknown';
		}

		// find the first ip in list, the ip must not a private and "unknown"
		// e.g. "unknown, 192.168.1.120, 202.192.1.2, 61.212.2.32, 172.16.3.101", return 202.192.1.2
		if ($not_private) {
			try {
				$arr = explode(',', $ips);
				foreach ($arr as $item) {
					$chk_item = trim($item);
					if ($chk_item != '' && strcasecmp($chk_item, 'unknown') && !self::is_private_ip(ip2long($chk_item))) {
						$client_ip = $chk_item;
						break;
					}
				}
			} catch (Exception $e) {}
		}
		
 		if($long){
			$client_ip = sprintf('%u', ip2long($client_ip));
 		}
		return $client_ip;
	}

	public static function get_self_url() {
		$s = (!empty($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] == 'on')) ? 's' : '';
		$protocol = strtolower($_SERVER['SERVER_PROTOCOL']);
		if (($pos = strpos($protocol, '/')) !== false){
			$protocol = substr($protocol, 0, $pos);
		}
		$protocol .= $s;
		$server = !empty($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : $_SERVER['SERVER_ADDR'];
		$port = ($_SERVER['SERVER_PORT'] != '80') ? (':'.$_SERVER['SERVER_PORT']) : '';
		return $protocol.'://'.$server.$port.$_SERVER['REQUEST_URI'];
	}

	// helper to try to sort out headers for people who aren't running apache
	public static function get_headers() {
		if (function_exists('apache_request_headers')) {
			// we need this to get the actual Authorization: header
			// because apache tends to tell us it doesn't exist
			return apache_request_headers();
		}
		// otherwise we don't have apache and are just going to have to hope
		// that $_SERVER actually contains what we need
		$out = array();
		foreach ($_SERVER as $key => $value) {
			if (substr($key, 0, 5) == "HTTP_") {
				// this is chaos, basically it is just there to capitalize the first
				// letter of every word that is not an initial HTTP and strip HTTP
				// code from przemek
				$key = str_replace(
					" ",
					"-",
					ucwords(strtolower(str_replace("_", " ", substr($key, 5))))
				);
				$out[$key] = $value;
			}
		}
		return $out;
	}

	// This function takes a input like a=b&a=c&d=e and returns the parsed
	// parameters like this
	// array('a' => array('b','c'), 'd' => 'e')
	public static function parse_parameters( $input ) {
		if (!isset($input) || !$input) return array();

		$pairs = explode('&', $input);

		$parsed_parameters = array();
		foreach ($pairs as $pair) {
			$split = explode('=', $pair, 2);
			$parameter = MCenterAuthUtil::urldecode_rfc3986($split[0]);
			$value = isset($split[1]) ? MCenterAuthUtil::urldecode_rfc3986($split[1]) : '';

			if (isset($parsed_parameters[$parameter])) {
				// We have already recieved parameter(s) with this name, so add to the list
				// of parameters with this name

				if (is_scalar($parsed_parameters[$parameter])) {
					// This is the first duplicate, so transform scalar (string) into an array
					// so we can add the duplicates
					$parsed_parameters[$parameter] = array($parsed_parameters[$parameter]);
				}

				$parsed_parameters[$parameter][] = $value;
			} else {
				$parsed_parameters[$parameter] = $value;
			}
		}
		return $parsed_parameters;
	}

	public static function build_http_query($params) {
		if (!$params) return '';

		// Urlencode both keys and values
		$keys = MCenterAuthUtil::urlencode_rfc3986(array_keys($params));
	    $values = MCenterAuthUtil::urlencode_rfc3986(array_values($params));
	    $params = array_combine($keys, $values);

		// Parameters are sorted by name, using lexicographical byte value ordering.
		uksort($params, 'strcmp');

		$pairs = array();
		foreach ($params as $parameter => $value) {
			if (is_array($value)) {
				// If two or more parameters share the same name, they are sorted by their value
				natsort($value);
				foreach ($value as $duplicate_value) {
					$pairs[] = $parameter . '=' . $duplicate_value;
				}
			} else {
				$pairs[] = $parameter . '=' . $value;
			}
		}
		return implode('&', $pairs);
	}
}


class MCenterAuthSignMethod {
	public function check_signature(&$request, $source, $secret, $signature, $ticket = null) {
		$built = $this->build_signature($request, $source, $secret, $ticket);
		return $built === $signature;
	}
}

class MCenterAuthSignMethod_HMAC_SHA1 extends MCenterAuthSignMethod {
	public function get_name() {
		return "HMAC-SHA1";
	}

	/**
	 * add by guohongwei@youku.com @20150123 for multi curl is_allow
	 */
	public function build_multi_signature($request, $source, $secret, $ticket = null) {
		$base_string_arr = $request->get_multi_signature_base_string();
		$key_parts = array($source, $secret);
		if(!empty($ticket)) {
			$key_parts[] = $ticket;
		}
		$key = implode('&', MCenterAuthUtil::urlencode_rfc3986($key_parts));
		$sign = array();
		foreach($base_string_arr as $k => $base_string) {
			$sign[$k] = base64_encode(MCenterAuthUtil::hash_hmac_rfc2104('sha1', $base_string, $key, true));
		}
		return $sign;
	}

	public function build_signature($request, $source, $secret, $ticket = null) {
		$base_string = $request->get_signature_base_string();
		$key_parts = array($source, $secret);
		if(!empty($ticket)) {
			$key_parts[] = $ticket;
		}
		$key = implode('&', MCenterAuthUtil::urlencode_rfc3986($key_parts));
		return base64_encode(MCenterAuthUtil::hash_hmac_rfc2104('sha1', $base_string, $key, true));
	}
}


/**
 * MCenterAuthRequest
 */
class MCenterAuthRequest {
	private $parameters;
	private $http_method;
	private $http_url;
	// for debug purposes
	public $base_string;
	public static $version = '1.5';
	public static $POST_INPUT = 'php://input';

	function __construct($http_method, $http_url, $parameters=NULL) {
		@$parameters or $parameters = array();
		$this->parameters = $parameters;
		$this->http_method = $http_method;
		$this->http_url = $http_url;
	}


	/**
	 * attempt to build up a request from what was passed to the server
	 */
	public static function from_request($http_method=NULL, $http_url=NULL, $parameters=NULL) {
		$server = !empty($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : $_SERVER['SERVER_ADDR'];
		$scheme = (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] != "on")
			? 'http'
			: 'https';
		@$http_url or $http_url = $scheme .
			'://' . $server .
			':' .
			$_SERVER['SERVER_PORT'] .
			$_SERVER['REQUEST_URI'];
		@$http_method or $http_method = $_SERVER['REQUEST_METHOD'];

		// We weren't handed any parameters, so let's find the ones relevant to
		// this request.
		// If you run XML-RPC or similar you should use this to provide your own
		// parsed parameter-list
		if (!$parameters) {
			// Find request headers
			$request_headers = MCenterAuthUtil::get_headers();

			// Parse the query-string to find GET parameters
			$parameters = MCenterAuthUtil::parse_parameters($_SERVER['QUERY_STRING']);

			// It's a POST request of the proper content-type, so parse POST
			// parameters and add those overriding any duplicates from GET
			if ($http_method == "POST"
				&& @strstr($request_headers["Content-Type"],
					"application/x-www-form-urlencoded")
			) {
				$post_data = MCenterAuthUtil::parse_parameters(
					file_get_contents(self::$POST_INPUT)
				);
				$parameters = array_merge($parameters, $post_data);
			}
		}

		return new MCenterAuthRequest($http_method, $http_url, $parameters);
	}

	/**
	 * add by guohongwei@youku.com @20150123 for multi curl is_allow
	 */
	public static function multi_from_key_and_ticket($app_key, $ticket, $http_method, $http_url, $parameters=NULL) {
		@$parameters or $parameters = array();
		$defaults = array(
			'source' => $app_key,
			'timestamp' => MCenterAuthRequest::generate_timestamp(),
			'client_ip' => MCenterAuthUtil::get_client_ip(false, false)
		);
		if (!empty($ticket)) {
			$defaults['ticket'] = $ticket;
		}
		foreach($parameters as $k => $v) {
			$parameters[$k] = array_merge($defaults, $v);
		}
		return new MCenterAuthRequest($http_method, $http_url, $parameters);
	}
	/**
	 * pretty much a helper function to set up the request
	 */
	public static function from_key_and_ticket($app_key, $ticket, $http_method, $http_url, $parameters=NULL) {
		@$parameters or $parameters = array();
		$defaults = array(
			'source' => $app_key,
			'timestamp' => MCenterAuthRequest::generate_timestamp(),
			'client_ip' => MCenterAuthUtil::get_client_ip(false, false)
		);
		if (!empty($ticket)) {
			$defaults['ticket'] = $ticket;
		}
		$parameters = array_merge($defaults, $parameters);
		return new MCenterAuthRequest($http_method, $http_url, $parameters);
	}

	/**
	 * add by guohongwei@youku.com @20150123 for multi curl is_allow
	 * $value 二维数组
	 */
	public function set_multi_parameter($name, $value, $allow_duplicates = true) {
		$vtype = 0;
		if(is_array($value)) { $vtype = 1; }
		elseif(is_string($value)) { $vtype = 2; }
		if($vtype>0) {
			foreach($this->parameters as $k => $v) {
				if ($allow_duplicates && isset($this->parameters[$k][$name])) {
					if (is_scalar($this->parameters[$k][$name])) {
						$this->parameters[$k][$name] = array($this->parameters[$k][$name]);
					}

					$this->parameters[$k][$name][] = $vtype==1?$value[$k]:$value;
				} else {
					$this->parameters[$k][$name] = $vtype==1?$value[$k]:$value;
				}
			}	
		}
	}

	public function set_parameter($name, $value, $allow_duplicates = true) {
		if ($allow_duplicates && isset($this->parameters[$name])) {
			// We have already added parameter(s) with this name, so add to the list
			if (is_scalar($this->parameters[$name])) {
				// This is the first duplicate, so transform scalar (string)
				// into an array so we can add the duplicates
				$this->parameters[$name] = array($this->parameters[$name]);
			}

			$this->parameters[$name][] = $value;
		} else {
			$this->parameters[$name] = $value;
		}
	}

	public function get_parameter($name) {
		return isset($this->parameters[$name]) ? $this->parameters[$name] : NULL;
	}

	public function get_parameters() {
		return $this->parameters;
	}

	public function unset_parameter($name) {
		unset($this->parameters[$name]);
	}

	/**
	 * add by guohongwei@youku.com @20150123 for multi curl is_allow
	 * The request params, sorted and concatenated into a normalized string array. multi operate
	 * @return string
	 */
	public function get_multi_signable_parameters() {
		// Grab all params
		$params = $this->parameters;
		foreach($params as $k => $param) {
			// Remove signature if present
			if (isset($params[$k]['sign'])) {
				unset($params[$k]['sign']);
			}
			$params[$k] = MCenterAuthUtil::build_http_query($param);
		}
		return $params;
	}

	/**
	 * The request params, sorted and concatenated into a normalized string.
	 * @return string
	 */
	public function get_signable_parameters() {
		// Grab all params
		$params = $this->parameters;
		// Remove signature if present
		if (isset($params['sign'])) {
			unset($params['sign']);
		}
		return MCenterAuthUtil::build_http_query($params);
	}
	
	/**
	 * add by guohongwei@youku.com @20150123 for multi curl is_allow
	 * Returns the base string array of this request
	 *
	 * The base string defined as the method, the url
	 * and the params (normalized), each urlencoded
	 * and the concated with &.
	 */
	public function get_multi_signature_base_string() {
		$parts = array(
			$this->get_normalized_http_method(),
			$this->get_normalized_http_url(),
		);
		$basestrarr = array();
		$msignparams = $this->get_multi_signable_parameters();
		foreach($msignparams as $k => $signparam) {
			$signparts = array_merge($parts, array($signparam));
			$signparts = MCenterAuthUtil::urlencode_rfc3986($signparts);
			$basestrarr[$k] = implode('&', $signparts);
		}
		return $basestrarr;
	}
	/**
	 * Returns the base string of this request
	 *
	 * The base string defined as the method, the url
	 * and the params (normalized), each urlencoded
	 * and the concated with &.
	 */
	public function get_signature_base_string() {
		$parts = array(
			$this->get_normalized_http_method(),
			$this->get_normalized_http_url(),
			$this->get_signable_parameters()
		);
		$parts = MCenterAuthUtil::urlencode_rfc3986($parts);
		return implode('&', $parts);
	}

	/**
	 * just uppercases the http method
	 */
	public function get_normalized_http_method() {
		return strtoupper($this->http_method);
	}

	/**
	 * parses the url and rebuilds it to be
	 * scheme://host/path
	 */
	public function get_normalized_http_url() {
		$parts = parse_url($this->http_url);
		$scheme = isset($parts['scheme']) ? $parts['scheme'] : 'http';
		$port = isset($parts['port']) ? $parts['port'] : '';
		$host = isset($parts['host']) ? $parts['host'] : '';
		$path = isset($parts['path']) ? $parts['path'] : '';
		$port or $port = ($scheme == 'https') ? '443' : '80';
		if (($scheme == 'https' && $port != '443')
			|| ($scheme == 'http' && $port != '80')) {
				$host = "$host:$port";
			}
		return "$scheme://$host$path";
	}

	/**
	 * builds a url usable for a GET request
	 */
	public function to_url() {
		$post_data = $this->to_postdata();
		$out = $this->get_normalized_http_url();
		if ($post_data) {
			$out .= '?'.$post_data;
		}
		return $out;
	}

	/**
	 * add by guohongwei@youku.com @20150123 for multi curl is_allow
	 * builds the data for multi curl request
	 */
	public function to_multipostdata() {
		$postdata = array();
		foreach($this->parameters as $k => $v) {
			$postdata[$k] = MCenterAuthUtil::build_http_query($v);
		}	
		return $postdata;
	}

	/**
	 * builds the data one would send in a POST request
	 */
	public function to_postdata() {
		return MCenterAuthUtil::build_http_query($this->parameters);
	}
	
	public function __toString() {
		return $this->to_url();
	}

	/**
	 * add by guohongwei@youku.com @20150123 for multi curl is_allow
	 */
	public function multi_sign_request($signature_method, $source, $secret, $ticket = null) {
		$this->set_multi_parameter(
			'signature_method',
			$signature_method->get_name(),
			false
		);
		$signatures = $signature_method->build_multi_signature($this, $source, $secret, $ticket);
		$this->set_multi_parameter('sign', $signatures, false);
	}

	public function sign_request($signature_method, $source, $secret, $ticket = null) {
		$this->set_parameter(
			'signature_method',
			$signature_method->get_name(),
			false
		);
		$signature = $signature_method->build_signature($this, $source, $secret, $ticket);
		$this->set_parameter('sign', $signature, false);
	}

	/**
	 * util function: current timestamp
	 */
	private static function generate_timestamp() {
		return time();
	}

	/**
	 * util function: current nonce
	 */
	private static function generate_nonce($len) {
		$x = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
		$n = strlen($x);
		$str = '';
		for($i = 0; $i < $len; ++$i){
			$j = mt_rand() % $n;
			$str .= $x{$j};
		}
		return $str;
	}
}


/**
 * MCenterAuth 认证类
 * @version 1.2.1
 */
class MCenterAuth {
	const DECODE_JSON_TO_OBJECT = 1;
	const DECODE_JSON_TO_ASSOC_ARRAY = 2;
    const MAX_URL_LENGTH_OF_GET_REQUEST = 2048;
    const MAX_VALUE_LENGTH_OF_GET_REQUEST = 512;

	/**
	 * Contains the last HTTP status code returned. 
	 *
	 * @ignore
	 */
	public $http_code;
	public $http_error;
	/**
	 * Contains the last API call.
	 *
	 * @ignore
	 */
	public $url;
	/**
	 * Set up the API root URL.
	 *
	 * @ignore
	 */
	public $host;
	/**
	 * Set timeout default.
	 *
	 * @ignore
	 */
	public $timeout = 30;
	/**
	 * Set connect timeout.
	 *
	 * @ignore
	 */
	public $connecttimeout = 30;
	/**
	 * return transfer.
	 *
	 * @ignore
	 */
	public $return_transfer = true;
	/**
	 * Verify SSL Cert.
	 *
	 * @ignore
	 */
	public $ssl_verifypeer = false;
	/**
	 * Respons format.
	 *
	 * @ignore
	 */
	public $format = 'json';
	/**
	 * Decode returned json data.
	 *
	 * @ignore
	 */
	public $decode_json = self::DECODE_JSON_TO_ASSOC_ARRAY;
	/**
	 * Contains the last HTTP headers returned.
	 *
	 * @ignore
	 */
	public $http_info = array();
	public $http_header = array();
	/**
	 * Set the useragnet.
	 *
	 * @ignore
	 */
	public $useragent = 'Youku MCenter Client v1.4';
	/**
	 * Set the caller.
	 *
	 * @ignore
	 */
	public $caller = 'WebModules';

	public static $mcenter_api_url = '';

	/**
	 * construct MCenterAuth object
	 */
	function __construct($app_key, $app_secret, $ticket = null, $caller = '') {
		$this->sha1_method = new MCenterAuthSignMethod_HMAC_SHA1();
		$this->app_key = $app_key;
		$this->app_secret = $app_secret;
		$this->ticket = $ticket;
		$this->host = self::get_url();
		if (!empty($caller)) {
			$this->caller = $caller;
		}
	}

	public static function get_url(){
		$mcenter_api_server = C('MCENTER_API_SERVER');
		if(!empty($mcenter_api_server)){
			if(is_string($mcenter_api_server)){
				self::$mcenter_api_url = $mcenter_api_server;
			}else if(is_array($mcenter_api_server)){
				$index = array_rand($mcenter_api_server);
				$server = $mcenter_api_server[$index];
				if(!empty($server)){
					self::$mcenter_api_url = $server; 
				}
			}
		}
        if (defined('MCENTER_API_URL') && empty(self::$mcenter_api_url)) {
            self::$mcenter_api_url = MCENTER_API_URL;
        }
		return self::$mcenter_api_url;
	}

	/**
	 * GET wrappwer for mcAuthRequest.
	 *
	 * @return mixed
	 */
	function get($url, $parameters = array()) {
		$response = $this->mcAuthRequest($url, 'GET', $parameters);
		if ($this->return_transfer && $this->format === 'json' && $this->decode_json) {
			$ret = json_decode($response, ($this->decode_json === self::DECODE_JSON_TO_ASSOC_ARRAY));
			return $ret ? $ret : $response;
		}
		return $response;
	}

	/**
	 * add by guohongwei@youku.com @20150123 for multi curl is_allow
	 * Multi POST for mcAuthRequest
	 *
	 * @return mixed
	 */
	function mpost($url, $parameters = array()) {
		$response = $this->mcAuthMultiRequest($url, 'POST', $parameters);
		if ($this->return_transfer && $this->format === 'json' && $this->decode_json) {
			$ret = json_decode($response, ($this->decode_json === self::DECODE_JSON_TO_ASSOC_ARRAY));
			return $ret ? $ret : $response;
		}
		return $response;
	}

	/**
	 * POST wreapper for mcAuthRequest.
	 *
	 * @return mixed
	 */
	function post($url, $parameters = array()) {
		$response = $this->mcAuthRequest($url, 'POST', $parameters);
		if ($this->return_transfer && $this->format === 'json' && $this->decode_json) {
			$ret = json_decode($response, ($this->decode_json === self::DECODE_JSON_TO_ASSOC_ARRAY));
			return $ret ? $ret : $response;
		}
		return $response;
	}

	/**
	 * DELTE wrapper for mcAuthRequest.
	 *
	 * @return mixed
	 */
	function delete($url, $parameters = array()) {
		$response = $this->mcAuthRequest($url, 'DELETE', $parameters);
		if ($this->return_transfer && $this->format === 'json' && $this->decode_json) {
			$ret = json_decode($response, ($this->decode_json === self::DECODE_JSON_TO_ASSOC_ARRAY));
			return $ret ? $ret : $response;
		}
		return $response;
	}
	
	/**
	* LOCATION to url 
	* 
	*/
	function location($url, $parameters=array()){
		$this->mcAuthRequest($url, 'LOCATION', $parameters);
	}

	/**
	 * add by guohongwei@youku.com @20150123 for multi curl is_allow
	 * Format and sign multi Auth/API request
	 * only for post 
	 * @return string
	 */
	function mcAuthMultiRequest($url, $method, $parameters) {
		// check length of url and params
		$method = strtoupper($method);
		if($method!="POST") { return false; }
		
		if (strrpos($url, 'http://') !== 0) {
			$url = rtrim(trim($this->host), '/')."/{$url}.{$this->format}";
		}
		
		$request = MCenterAuthRequest::multi_from_key_and_ticket($this->app_key, $this->ticket, $method, $url, $parameters);
		$request->multi_sign_request($this->sha1_method, $this->app_key, $this->app_secret, $this->ticket);

		$res = $this->multiHttp($request->get_normalized_http_url(), $method, $request->to_multipostdata());
		$ret = array();
		foreach($res as $k => $v) {
			$ret[$k]['response'] = !empty($v['response'])?$v['response']:'';
			$ret[$k]['error'] = !empty($v['error'])?$v['error']:'';
		}
		return json_encode($ret);

	}

	/**
	 * Format and sign an Auth / API request
	 *
	 * @return string
	 */
	function mcAuthRequest($url, $method, $parameters) {
		// check length of url and params
		$method = strtoupper($method);
		
		//when login,use MCENTER_API_URL,other use rand ip
		if($method == 'LOCATION' && defined('MCENTER_API_URL') && MCENTER_API_URL != ''){
			$this->host = MCENTER_API_URL;
		}
		
		if (strrpos($url, 'http://') !== 0 && strrpos($url, 'http://') !== 0) {
			$url = rtrim(trim($this->host), '/')."/{$url}.{$this->format}";
		}
		
		if ($method == 'GET') {
			$request = MCenterAuthRequest::from_key_and_ticket($this->app_key, $this->ticket, $method, $url, $parameters);
			if (strlen($request->to_url()) > self::MAX_URL_LENGTH_OF_GET_REQUEST) {
				$method = 'POST';
			} elseif (!empty($parameters) && is_array($parameters)) {
				foreach ($parameters as $value) {
					if (strlen($value) > self::MAX_VALUE_LENGTH_OF_GET_REQUEST) {
						$method = 'POST';
						break;
					}
				}
			}
		}

		$http_method = ($method != 'LOCATION') ? $method : 'GET';
		$request = MCenterAuthRequest::from_key_and_ticket($this->app_key, $this->ticket, $http_method, $url, $parameters);
		$request->sign_request($this->sha1_method, $this->app_key, $this->app_secret, $this->ticket);

		switch ($method) {
		case 'GET':
			return $this->http($request->to_url(), 'GET');
			break;
		case 'LOCATION':
			header('Location: '.$request->to_url());
			break;
		default:
			return $this->http($request->get_normalized_http_url(), $method, $request->to_postdata());
		}
	}

	/**
	 * add by guohongwei@youku.com @20150123 for multi curl is_allow
	 * use multi_curl,make http requests post
	 * libraries/Mcurl.php used
	 * only post/get
	 * @multireqfields 二维数组
	 * 若无参数则$multireqfields = array(array(),array(),...) 不可为空
	 * 有参数则$multireqfields = array('k1'=>array('f1'=>'v1','f2'=>'v2'),...)
	 */
	function multiHttp($url, $method, $multireqfields) {
		if(!is_array($multireqfields)||is_array($multireqfields)&&count($multireqfields)==0) { return false; }
		//header
		$headers = array(
			'X-Forwarded-For:'.$this->caller.','.$this->useragent,
			'X-Caller:'.$this->caller
		);
		$curl_options = array(
			'HTTP_VERSION' => CURL_HTTP_VERSION_1_0,
			'USERAGENT' => $this->useragent,
			'CONNECTTIMEOUT' => $this->connecttimeout,
			'TIMEOUT' => $this->timeout,
			'AUTOREFERER' => true,
			'RETURNTRANSFER' => $this->return_transfer,
			'ENCODING' => '',
			'SSL_VERIFYPEER' => $this->ssl_verifypeer,
			'HEADERFUNCTION' => array($this, 'getHeader'),
			'HTTPHEADER' => $headers,
			'HEADER' => false
		);
		//include mcurl library in ci framework
		$CI = &get_instance();
		$CI->load->library('mcurl');
		foreach($multireqfields as $k => $v) {
			$CI->mcurl->add_call($k,$method,$url,$v,$curl_options);
		}

		$result = $CI->mcurl->execute();
		if(empty($result)) {
			return false;
		}
		return $result;
	}

	/**
	 * Make an HTTP request
	 *
	 * @return string API results
	 */
	function http($url, $method, $postfields = null) {
		// header
		$headers = array(
			'X-Forwarded-For:'.$this->caller.','.$this->useragent,
			'X-Caller:'.$this->caller
		);

		$ci = curl_init();
		// curl settings
		curl_setopt($ci, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
		curl_setopt($ci, CURLOPT_USERAGENT, $this->useragent);
		curl_setopt($ci, CURLOPT_CONNECTTIMEOUT, $this->connecttimeout);
		curl_setopt($ci, CURLOPT_TIMEOUT, $this->timeout);
		curl_setopt($ci, CURLOPT_AUTOREFERER, true);
		curl_setopt($ci, CURLOPT_RETURNTRANSFER, $this->return_transfer);
		curl_setopt($ci, CURLOPT_ENCODING, '');
		curl_setopt($ci, CURLOPT_SSL_VERIFYPEER, $this->ssl_verifypeer);
		curl_setopt($ci, CURLOPT_HEADERFUNCTION, array($this, 'getHeader'));
		curl_setopt($ci, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ci, CURLOPT_HEADER, false);

		switch ($method) {
		case 'POST':
			curl_setopt($ci, CURLOPT_POST, true);
			if (!empty($postfields)) {
				curl_setopt($ci, CURLOPT_POSTFIELDS, $postfields);
			}
			break;
		case 'DELETE':
			curl_setopt($ci, CURLOPT_CUSTOMREQUEST, 'DELETE');
			if (!empty($postfields)) {
				$url = "{$url}?{$postfields}";
			}
		}

		curl_setopt($ci, CURLOPT_URL, $url );

		$response = curl_exec($ci);
		//var_dump($response);
		$this->http_code = curl_getinfo($ci, CURLINFO_HTTP_CODE);
		$this->http_error = curl_error($ci);
		$this->http_info = array_merge($this->http_info, curl_getinfo($ci));
		$this->url = $url;

		curl_close ($ci);

		if ($response === false) {
			if ($this->format === 'json') {
				$ret = array('e' => array('code' => -1, 'desc' => $this->http_error));
				return json_encode($ret);
			}
		}
		return $response;
	}

	/**
	 * Get the header info to store.
	 *
	 * @return int
	 */
	function getHeader($ch, $header) {
		$i = strpos($header, ':');
		if (!empty($i)) {
			$key = str_replace('-', '_', strtolower(substr($header, 0, $i)));
			$value = trim(substr($header, $i + 2));
			$this->http_header[$key] = $value;
		}
		return strlen($header);
	}
}


/**
 * MCenter操作类
 * @version 1.0
 */
class MCenterClientModel {
    const ERR_AUTH_IP_NOT_ACCORD = 40117;

	private $auth = null;
	private $ticket = null;
    private $cookie_name = 'ci_mc_ticket';
    private $verify_ret = null;

	/**
	 * 构造函数
	 * 
	 * @access public
	 * @param mixed $app_key 应用APP KEY
	 * @param mixed $app_secret 应用APP SECRET
	 * @param mixed $ticket 认证返回的ticket
	 * @return void
	 */
	public function __construct() {
		// Set the super object to a local variable for use throughout the class
		//$this->CI =& get_instance();
        // 验证需要的配置
        if (!defined('MCENTER_APP_KEY') || !defined('MCENTER_APP_SECRET') ||
            !defined('MCENTER_API_URL') || !defined('MCENTER_APP_URL') ||
            MCENTER_API_URL == '' || MCENTER_APP_URL == '' ||
            MCENTER_APP_KEY == '' || MCENTER_APP_SECRET == '') {
            die('MCENTER_APP_KEY,MCENTER_APP_SECRET, \\
                MCENTER_API_URL,MCENTER_APP_URL was not found!');
        }
        if (defined('MCENTER_APP_COOKIE_NAME') && MCENTER_APP_COOKIE_NAME != '') {
            $this->cookie_name = MCENTER_APP_COOKIE_NAME;
        }
        //$this->ticket = $this->CI->input->get('ticket');
        $this->ticket = I('get.ticket');
        if ($this->ticket) {
            setcookie($this->cookie_name, $this->ticket, 0, '/');
        } else {
            //$this->ticket = $this->CI->input->cookie($this->cookie_name);
            $this->ticket = cookie($this->cookie_name);
        }
		$this->auth = new MCenterAuth(MCENTER_APP_KEY, MCENTER_APP_SECRET, $this->ticket);
	}

    /**
     * 处理Mcenter登录逻辑
     * 如果没有登录 跳转到mcenter登录页面
	 * http://wiki.1verge.net/webdev:ugc:codeigniter:libraries:mcenterclient#login
     **/
    public function login() {
        $params = array();
        if (function_exists('current_url')) {
            $params['backurl'] = current_url();
        } else {
            $params['backurl'] =  'http://'. $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        }
		//无ticket，则跳转到统一登录页，获取ticket
		if(empty($this->ticket)){
			//如果服务器端ticket还为空，则跳转到登录界面
			$params['redirect'] = 1;
			$this->auth->location('account/verify', $params);
			exit;
		}
		$ret = $this->auth->get('account/verify', $params);
        if (is_array($ret) && isset($ret['e']['code']) &&
            $ret['e']['code'] === 0 && !empty($this->ticket)) {
            setcookie($this->cookie_name, $this->ticket, 0, '/');
            $this->verify_ret = $ret;
        } else {
            setcookie($this->cookie_name, '', time()-3600, '/');
            $params['redirect'] = 1;
            $this->auth->location('account/verify', $params);
            exit;
        }
    }

    /**
     * 验证当前用户是否登录
     * @return boolean true表示已登录 否则返回false
	 * http://wiki.1verge.net/webdev:ugc:codeigniter:libraries:mcenterclient#is_login
     **/
    public function is_login() {
        $params['redirect'] = 0;
		$ret = $this->auth->get('account/verify', $params);
        if (is_array($ret) && isset($ret['e']['code']) &&
            $ret['e']['code'] === 0 && !empty($this->ticket)) {
            setcookie($this->cookie_name, $this->ticket, 0, '/');
            $this->verify_ret = $ret;
            return TRUE;
		}
        return FALSE;
    }

    /**
     * 验证用户是否已登录,会将mcenter接口返回的信息全部返回
     * @param array 调用account/verify的参数
     * @return array 接口返回信息
	 * http://wiki.1verge.net/webdev:ugc:codeigniter:libraries:mcenterclient#verify
     **/
	public function verify($params = array()) {
		return $this->auth->get('account/verify', $params);
	}

    /**
     * 登出当前系统
	 * http://wiki.1verge.net/webdev:ugc:codeigniter:libraries:mcenterclient#logout
     **/
	public function logout() {
		$params = array();
		$backurl = !empty($_REQUEST['backurl']) ? $_REQUEST['backurl'] : !empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : MCENTER_APP_URL;
		if (!empty($backurl)) {
			$params['backurl'] = trim($backurl);
		}
		setcookie($this->cookie_name, '', time()-3600, '/');
		$this->ticket = null;
		$this->auth->location('account/logout', $params);
	}

    /**
     * 获取当前登录用户的用户名
	 * http://wiki.1verge.net/webdev:ugc:codeigniter:libraries:mcenterclient#get_user_name
     **/
    public function get_user_name() {
        $verify_info = $this->get_verify_info();
        return isset($verify_info['operator_data']['real_name']) ?
            $verify_info['operator_data']['real_name'] : '';
    }

    /**
     * 获取当前登录的用户ID
	 * http://wiki.1verge.net/webdev:ugc:codeigniter:libraries:mcenterclient#get_user_id
     **/
    public function get_user_id() {
        $verify_info = $this->get_verify_info();
        return isset($verify_info['operator_id']) ?
            $verify_info['operator_id'] : '';
    }

	/**
     * 获取当前登录用户的登录用户名
	 * http://wiki.1verge.net/webdev:ugc:codeigniter:libraries:mcenterclient#get_login_name
     **/
    public function get_login_name() {
        $verify_info = $this->get_verify_info();
        return isset($verify_info['login_name']) ?
            $verify_info['login_name'] : '';
    }


    /**
     * 获取当前登录的用户信息
	 * http://wiki.1verge.net/webdev:ugc:codeigniter:libraries:mcenterclient#get_verify_info
     **/
    public function get_verify_info() {
        $verify_info = array();
        if (!empty($this->verify_ret['data'][0])) {
            $verify_info = $this->verify_ret['data'][0];
            if (isset($verify_info['operator_data'])) {
                $verify_info['operator_data'] = json_decode($verify_info['operator_data'], true);
            }
        }
        return $verify_info;
    }

	/**
	 * add by guohongwei@youku.com @20150123 for multi curl is_allow
	 * mcurl的is_allow 请求mcenter
	 * $res_arr 资源数组
	 * $action_arr 操作数组
	 * $other_params 其它参数数组
	 * 以上三者需要一一对应,若无其它参数传入none
	 * 例如 $res_arr = array("video","file")
	 *   $action_arr = array("view","view")
	 *   $other_params = array("none",array("vmenu"=>1))
	 */  
	public function multicurl_allow($res_arr,$action_arr,$check_all = true, $other_params = array()) {
		$params = array();
		foreach($res_arr as $key=>$res) {
			$params_item = array(
				'restype' => $res,
				'action' => $action_arr[$key],	
				'check_all' => $check_all,
			);
			if(is_array($other_params[$key])) {
				$params_item = array_merge($params_item,$other_params[$key]);
			}
			$call_key = $this->gen_check_key($res,$action_arr[$key],$other_params[$key]);
			$params[$call_key] = $params_item;
		}
		//@20150123 只支持post
		$ret = $this->auth->mpost('acl/is_allow',$params);
		//TODO process
		return $ret;
	}

	/**
	 * add by guohongwei@youku.com @20150123 for multi curl is_allow
	 * 生成restype_check_map key
	 */
	public function gen_check_key($res,$action,$other_params = null,$ismd5 = 0) {
		$key = '';
		if(!empty($other_params)) {
			if(is_string($other_params)) {
				$key = $res . "_" . $action . "_" . $other_params;
			}else if(is_array($other_params)) {
				$key = $res . "_" . $action . "_";
				foreach($other_params as $k=>$v) {
					$key .= $k . "_" . $v;
				}
			}
		}else {
			$key = $res . "_" . $action;
		}
		//$key过长时使用md5缩短		
		if($ismd5==1) {
			return md5($key);
		}
		return $key;
	}

    /**
	 * http://wiki.1verge.net/webdev:ugc:codeigniter:libraries:mcenterclient#is_allow
     **/
    public function is_allow($restype, $action, $resids = array(),
        $anc_resids = array(), $check_all = true, $other_params = array()) {
        $params = array(
            'restype' => $restype,
            'action' => $action,
            'check_all' => $check_all,
        );
        if (!empty($resids)) {
            $params['resids']  = implode(',', $resids);
        }
        if (!empty($anc_resids)) {
            $params['anc_resids']  = implode('.', $anc_resids);
		}
		if (!empty($other_params)) {
			$params = array_merge($params, $other_params);
		}
		$ret = $this->auth->get('acl/is_allow', $params);
        if (isset($ret['result']) && $ret['result'] == 1) {
            return TRUE;
        }
        return FALSE;
	}
	
	function is_allow2($restype, $action, $resids, $anc_resids = null, $check_all = true, $other_params = array()) {
		$params = array();
		$params['err_lang'] = 'cn';
		$params['restype'] = $restype;
		$params['action']  = $action;
		$params['resids']  = $resids;
		if (isset($anc_resids)) {
			$params['anc_resids'] = $anc_resids;
		}
		$params['check_all']  = $check_all ? 1: 0;
		if (!empty($other_params)) {
			$params = array_merge($params, $other_params);
		}
		$result = $this->auth->get('acl/is_allow', $params);
		return $result;
	}

	function multi_allow($restypes, $actions, $resids, $anc_resids = null, $other_params = array()) {
		$params = array();
		$params['err_lang'] = 'cn';
		$params['restypes'] = $restypes;
		$params['actions']  = $actions;
		$params['resids']  = $resids;
		if (isset($anc_resids)) {
			$params['anc_resids'] = $anc_resids;
		}
		if (!empty($other_params)) {
			$params = array_merge($params, $other_params);
		}
		$result = $this->auth->get('acl/multi_allow', $params);
		return $result;
	}

	public function write_logs($operator_id, $operator_name, $app_id, $res_type, $res_id, $action,
						$request_method, $request_path, $request_data, $original_uri = '', $pres_type = null, $pres_id = null, $result = null) {
		$params = array();
		$params['operator_id'] = $operator_id;
		$params['operator_name'] = $operator_name;
		$params['app_id'] = intval($app_id);
		$params['res_type'] = $res_type;
		$params['res_id'] = intval($res_id);
		$params['action'] = $action;
		$params['request_method'] = $request_method;
		$params['request_path'] = $request_path;
		$params['original_uri'] = $original_uri;
		$params['request_data'] = $request_data;
		if (isset($pres_type)) {
			$params['pres_type'] = $pres_type;
		}
		if (isset($pres_id)) {
			$params['pres_id'] = intval($pres_id);
		}
		if (isset($result)) {
			$params['result'] = intval($result);
		}
		return $this->auth->post('logs/write', $params);
	}

	public function write_mlogs($uid, $restype, $action, $resid, $method, $uri = '', $data = '', $result = null,
			$prestype = null, $presid = null, $bvalue = null, $cvalue = null, $exinfo = null, $custom_datas = array()) {
		$params = array();
		$params['uid'] = $uid;
		$params['restype'] = $restype;
		$params['resid'] = $resid;
		$params['action'] = $action;
		$params['method'] = $method;
		$params['uri'] = $uri;
		$params['data'] = $data;
		if (isset($result)) {
			$params['result'] = $result;
		}
		if (isset($prestype)) {
			$params['prestype'] = $prestype;
		}
		if (isset($presid)) {
			$params['presid'] = $presid;
		}
		if (isset($bvalue)) {
			$params['bvalue'] = json_encode($bvalue);
		}
		if (isset($cvalue)) {
			$params['cvalue'] = json_encode($cvalue);
		}
		if (isset($exinfo)) {
			$params['exinfo'] = json_encode($exinfo);
		}
		if (!empty($custom_datas) && is_array($custom_datas)) {
			foreach ($custom_datas as $key => $val) {
				if(is_array($val) || is_object($val)){ 
					$params[$key] = json_encode($val);
				}else{
					$params[$key] = $val;
				}
			}
		}
		return $this->auth->post('mlogs/write', $params);
	}

	public function app_get($id, $code = null, $key = null) {
		$params = array();
		if (!empty($id)) {
			$params['id'] = intval($id);
		} elseif (!empty($code)) {
			$params['code'] = trim($code);
		} elseif (!empty($key)) {
			$params['key'] = trim($key);
		}
		return $this->auth->get('app/get', $params);
	}

	public function app_multi_get($ids = null, $codes = null, $count = null, $page = null) {
		$params = array();
		if (!empty($ids)) {
			$params['ids'] = trim($ids);
		} elseif (!empty($codes)) {
			$params['codes'] = trim($codes);
		} else {
			if (isset($count)) {
				$params['count'] = intval($count);
			}
			if (isset($page)) {
				$params['page'] = intval($page);
			}
		}
		return $this->auth->get('app/multi_get', $params);
	}

	public function app_get_link_operators($app_id = null, $count = null, $page = null, $order_by = null) {
		$params = array();
		if (!empty($app_id)) {
			$params['app_id'] = trim($app_id);
        } 
        if (!empty($order_by)) {
            $params['order_by'] = trim($order_by);
        } 
        if (!empty($count)) {
            $params['count'] = intval($count);
        } 
        if (!empty($page)) {
            $params['page'] = intval($page);
        }
        return $this->auth->get('app/get_link_operators', $params);
    }

	public function restype_multi_get($ids = null, $codes = null, $count = null, $page = null) {
		$params = array();
		if (!empty($ids)) {
			$params['ids'] = trim($ids);
		} elseif (!empty($codes)) {
			$params['codes'] = trim($codes);
		} else {
			if (isset($count)) {
				$params['count'] = intval($count);
			}
			if (isset($page)) {
				$params['page'] = intval($page);
			}
		}
		return $this->auth->get('restype/multi_get', $params);
	}	
	/**
	 * operator api
	 */
	public function operator_get_link_roles($operator_id = null, $app_id = null){
		$params = array();
		if (!empty($app_id)) {
			$params['app_id'] = trim($app_id);
		}
		if (!empty($operator_id)) {
			$parmas['operetor_id'] = intval($operator_id);
		}
		return $this->auth->get('operator/get_link_roles', $params);
	}

    /**
	 * http://wiki.1verge.net/webdev:ugc:codeigniter:libraries:mcenterclient#request
     **/
    public function request($url, array $params = array(), $method = 'GET', $decode_json = MCenterAuth::DECODE_JSON_TO_ASSOC_ARRAY) {
        $method = strtolower($method);
        if (empty($method) || !in_array($method, array('get', 'post', 'delete'))) {
            $method = 'get';
        }
        $raw_decode_json = $this->auth->decode_json;
        $this->auth->decode_json = $decode_json;
        $ret = $this->auth->$method($url, $params);
        $this->auth->decode_json = $raw_decode_json;
        return $ret;
    }
}

?>
