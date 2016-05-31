<?php defined('BASEPATH') OR exit('No direct script access allowed');
require_once APPPATH.'core/api_controller/Api_Controller_Abstract.php';
/**
 * CodeIgniter Rest API Server
 * @author Sujeet <sujeetkv90@gmail.com>
 * @link https://github.com/sujeet-kumar/ci-rest-api-server
 */

/**
 * Base Api Controller
 */
class Api_Controller extends Api_Controller_Abstract
{
	public function __construct(){
		parent::__construct();
		
		// global setup
		$this->load->config('api_config');
		$this->_setup($this->config->item('api_config'));
		
		$this->_httpAllow('*', array('GET', 'OPTIONS'));
	}
	
	// optional
	public function _isAuthorized(){
		// return TRUE if authorization successfull
		return true;
	}
	
	// optional
	public function _beforeDispatch(){
		// runs before method dispatch and before authorization and after constructor
	}
	
	// optional
	public function _afterDispatch(){
		// runs after method dispatch
	}
}

/* End of file Api_Controller.php */