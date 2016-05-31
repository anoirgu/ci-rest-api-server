<?php defined('BASEPATH') OR exit('No direct script access allowed');

/* response type */
$config['api_config']['response_format'] = 'json'; // 'json', 'text/json', ...

/* response schema */
$config['api_config']['response_schema'] = array(
	'status'	=> 0, // 0 or 1
	'message'	=> '',
	'redirect'	=> '',
	'payload'	=> array()
);
$config['api_config']['status_field'] = 'status';
$config['api_config']['message_field'] = 'message';

/* message language */
$config['api_config']['message_language'] = 'english';

/* whether emulate http method or not */
$config['api_config']['emulate_http_method'] = true;

/* force https urls */
$config['api_config']['force_https'] = false;

/* enable jsonp */
$config['api_config']['jsonp'] = false;
$config['api_config']['jsonp_callback_field'] = 'callback';

/* root node name for XML response */
$config['api_config']['xml_basenode'] = 'xml';

/* CORS settings */
$config['api_config']['allow_cors'] = false;
$config['api_config']['allow_cors_any_origin'] = false;
$config['api_config']['allowed_cors_origins'] = array(); // array('http://host1.com', 'http://host1.com');
$config['api_config']['allowed_cors_headers'] = array(
	'Accept',
	'Content-Type',
	'X-Requested-With',
	'Origin',
	'Access-Control-Request-Method'
);
$config['api_config']['allowed_cors_methods'] = array('GET', 'OPTIONS', 'HEAD');
