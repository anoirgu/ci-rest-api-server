<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require_once APPPATH.'core/Api_Controller.php';

class Example extends Api_Controller
{
	public function __construct(){
		parent::__construct();
		
		// local setup
		$this->_setup(array(
			'response_format' => 'json',
			'response_schema' => array(
				'status'	=> 0, // 0 or 1
				'message'	=> '',
				'redirect'	=> '',
				'payload'	=> array()
			),
			'status_field' => 'status',
			'message_field' => 'message'
		));
		
		$this->_httpAllow('*', array('GET', 'OPTIONS'));
		$this->_httpAllow('create', 'POST');
		$this->_httpAllow('update', array('POST', 'PATCH'));
	}
	
	// optional
	public function _isAuthorized(){
		// return TRUE if authorization successfull
		//return true and parent::_isAuthorized(); // if parent class also has _isAuthorized()
		return true;
	}
	
	// optional
	public function _beforeDispatch(){
		// runs before method dispatch and before authorization and after constructor
		parent::_beforeDispatch(); // if parent class also has _beforeDispatch()
	}
	
	// optional
	public function _afterDispatch(){
		// runs after method dispatch
		parent::_afterDispatch(); // if parent class also has _afterDispatch();
	}
	
	
	public function index(){
		
		$this->_response(array(
			'status' => 1,
			'payload' => array(
				'users' => array(
					array('id'=>1,'name'=>'Sujeet', 'age'=>25),
					array('id'=>2,'name'=>'Suraj', 'age'=>23),
					array('id'=>3,'name'=>'Sudhir', 'age'=>24)
				)
			)
		));
	}
	
	
	public function update($id = NULL){
		if(empty($id)){
			
			$this->_response(array(
				'status' => 0,
				'message' => 'Could not update record'
			), Status::HTTP_BAD_REQUEST);
		}else{
			// do update
			
			$this->_response(array(
				'status' => 1,
				'message' => 'Record updated successfully'
			));
		}
	}
}